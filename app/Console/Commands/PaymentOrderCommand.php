<?php

namespace App\Console\Commands;

use App\Http\Controllers\System\PaymentOrderController;
use App\Models\System\Client;
use App\Models\System\ClientPayment;
use App\Models\System\Configuration;
use App\Models\System\PaymentMethodType;
use App\Models\System\PaymentOrder;
use Carbon\Carbon;
use Hyn\Tenancy\Environment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PaymentOrderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:payments {--verify : Ejecuta la verificación de órdenes vencidas y bloqueo de tenants (sin depender de la hora)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comando para la creación de ordenes de pago rapidas';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $config = Configuration::first();
        
        if (!$config || !$config->active_cron) {
            $this->info('Cron de ordenes de pago desactivado');
            return;
        }

        $now = now();
        
        // OPTIMIZACIÓN: Usar chunk para procesar clientes en lotes y evitar cargar todos en memoria
        Client::where('locked_tenant', false)
            ->whereNotNull('ending_billing_cycle')
            ->chunk(100, function ($clients) use ($config) {
                foreach ($clients as $client) {
                    $this->createOrderPayment($client, $config);
                }
            });

        $midnight = $now->format('H:i');
        if ($this->option('verify') || $midnight === '00:00') {
            $this->verifiedOrder();
        }
    }

    private function createOrderPayment(Client $client, Configuration $config)
    {
        $day_notification = $client->dayNotification();
        $range_start= now()->startOfMonth();
        $range_end= now()->endOfMonth();
        $now = now();

        $order_payment = PaymentOrder::whereBetween('date_of_due', [$range_start, $range_end])
                        ->where('client_id', $client->id)
                        ->first();

        if ($order_payment && $order_payment->created_by === 'Sistema') {
            $this->createClientPaymentAndMirrorToTenant($client, $order_payment->id);
        }


        $ending_service = Carbon::parse($client->ending_billing_cycle);
        $today_notification = Carbon::createFromDate($ending_service->year, $ending_service->month, $day_notification);
        $hour_notification = Carbon::today()->setTimeFromTimeString($config->hour_generate_payment_order);
        if ( ($today_notification->isSameDay($now) && Carbon::now()->isSameHour($hour_notification) )&& !$order_payment) {
            $id = $client->createPayemtnOrder(); // Crear orden de pago
            $this->createClientPaymentAndMirrorToTenant($client, $id);
                if ($config->send_notification_cron) {
                    app(PaymentOrderController::class)->notify($id);
                }
        }
    }

    private function createClientPaymentAndMirrorToTenant(Client $client, int $payment_order_id): void
    {
        $payment_order = PaymentOrder::find($payment_order_id);
        if (!$payment_order) {
            return;
        }

        $payment_method_type_id = (int) (PaymentMethodType::query()->orderBy('id')->value('id') ?? 0);
        if ($payment_method_type_id <= 0) {
            return;
        }

        $client_payment = ClientPayment::query()->where('payment_order_id', $payment_order_id)->first();

        if (!$client_payment) {
            $client_payment = ClientPayment::query()
                ->where('client_id', $client->id)
                ->whereDate('date_of_payment', $payment_order->date_of_due)
                ->where('state', false)
                ->first();
        }

        if (!$client_payment) {
            $client_payment = ClientPayment::query()->create([
                'client_id' => $client->id,
                'payment_order_id' => $payment_order_id,
                'date_of_payment' => $payment_order->date_of_due,
                'payment_method_type_id' => $payment_method_type_id,
                'has_card' => false,
                'card_brand_id' => null,
                'reference' => null,
                'payment' => $payment_order->amount,
                'state' => false,
                'status' => 0,
            ]);
        } else {
            $needs_update = false;
            if (empty($client_payment->payment_order_id)) {
                $client_payment->payment_order_id = $payment_order_id;
                $needs_update = true;
            }
            if (!isset($client_payment->status)) {
                $client_payment->status = 0;
                $needs_update = true;
            }
            if ($needs_update) {
                $client_payment->save();
            }
        }

        $tenancy = app(Environment::class);
        $tenancy->tenant($client->hostname->website);

        $exists_in_tenant = DB::connection('tenant')->table('account_payments')
            ->where('reference_id', $client_payment->id)
            ->exists();

        if ($exists_in_tenant) {
            return;
        }

        DB::connection('tenant')->table('account_payments')->insert([
            'date_of_payment' => $client_payment->date_of_payment,
            'reference_id' => $client_payment->id,
            'payment_method_type_id' => $client_payment->payment_method_type_id,
            'has_card' => (bool) $client_payment->has_card,
            'card_brand_id' => $client_payment->card_brand_id,
            'reference' => $client_payment->reference,
            'payment' => $client_payment->payment,
            'state' => 0,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);
    }

    private function verifiedOrder()
    {
        $today = Carbon::now()->startOfDay();
        $range_start = now()->startOfMonth();
        $range_end = now()->endOfMonth();

        Client::where('locked_tenant', false)
            ->whereNotNull('ending_billing_cycle')
            ->whereDate('ending_billing_cycle', '<', $today->toDateString())
            ->chunk(100, function ($clients) {
                foreach ($clients as $client) {
                    $existingOrderId = PaymentOrder::query()
                        ->where('client_id', $client->id)
                        ->whereDate('date_of_due', $client->ending_billing_cycle)
                        ->orderByDesc('id')
                        ->value('id');

                    if (!$existingOrderId) {
                        $existingOrderId = $client->createPayemtnOrder();
                    }

                    if ($existingOrderId) {
                        $this->createClientPaymentAndMirrorToTenant($client, (int) $existingOrderId);
                    }
                }
            });

        $order_payments = PaymentOrder::whereBetween('date_of_due', [$range_start, $range_end])
            ->where('order_state_id', 1)
            ->get();

        foreach ($order_payments as $order_payment) {
            $due = Carbon::parse($order_payment->date_of_due)->startOfDay();
            if (($order_payment->created_by === 'Sistema') && ($today->greaterThan($due) && (int) $order_payment->order_state_id === 1)) {
                $client = $order_payment->client;
                if ($client) {
                    $client->locked_tenant = true;
                    $client->save();

                    if ($client->hostname && $client->hostname->website) {
                        $tenancy = app(Environment::class);
                        $tenancy->tenant($client->hostname->website);
                        DB::connection('tenant')->table('configurations')->where('id', 1)->update(['locked_tenant' => true]);
                    }
                }

                $order_payment->order_state_id = 3;
                $order_payment->save();
            }
        }

    }
}
