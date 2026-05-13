<?php
namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\ClientPaymentRequest;
use App\Http\Resources\System\ClientPaymentCollection;
use App\Models\System\Client;
use App\Models\System\CardBrand;
use App\Models\System\ClientPayment;
use App\Models\System\PaymentMethodType;
use App\Models\System\PaymentOrder;
use Carbon\Carbon;
use Hyn\Tenancy\Environment;
use Illuminate\Support\Facades\DB;

class ClientPaymentController extends Controller
{
    public function records($client_id)
    {
        $records = ClientPayment::where('client_id', $client_id)->get();

        return new ClientPaymentCollection($records);
    }

    public function tables()
    {
        return [
            'payment_method_types' => PaymentMethodType::all(),
            'card_brands' => CardBrand::all(),
        ];
    }

    public function client($client_id)
    {
        $client = Client::find($client_id);

        $total_paid = collect($client->payments)->where('state', true)->sum('payment');
        $total = collect($client->payments)->sum('payment');
        $total_difference = round($total - $total_paid, 2);

        return [
            'name' => $client->name,
            'pricing' => $client->plan->pricing,
            'total_paid' => $total_paid,
            'total' => $total,
            'total_difference' => $total_difference,
            'ending_billing_cycle' => $client->ending_billing_cycle
                ? $client->ending_billing_cycle->format('Y-m-d')
                : null,
        ];
    }

    public function store(ClientPaymentRequest $request)
    {
        $client = Client::with('hostname')->findOrFail($request->client_id);

        $this->applyEndingBillingCycleFromRequest($request, $client);
        $client->refresh();

        $paymentMethodType = PaymentMethodType::find($request->payment_method_type_id);
        $hasCard = (bool) optional($paymentMethodType)->has_card;

        $id = $request->input('id');
        if ($id) {
            $record = ClientPayment::query()
                ->where('id', $id)
                ->where('client_id', $client->id)
                ->firstOrFail();
            if ($record->state) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede editar un pago ya registrado como pagado.',
                ], 422);
            }
        } else {
            $record = new ClientPayment();
            $record->client_id = $client->id;
        }

        $record->fill([
            'date_of_payment' => $request->date_of_payment,
            'payment_method_type_id' => $request->payment_method_type_id,
            'card_brand_id' => $hasCard ? $request->card_brand_id : null,
            'reference' => $request->reference,
            'payment' => $request->payment,
        ]);
        $record->has_card = $hasCard;
        $record->save();

        $this->syncTenantAccountPayment($client, $record);

        return [
            'success' => true,
            'message' => $id ? 'Pago editado con éxito' : 'Pago programado con éxito',
        ];
    }

    /**
     * Alinea órdenes y pagos programados del sistema con la nueva fecha de fin de ciclo (solo pendientes).
     */
    protected function applyEndingBillingCycleFromRequest(ClientPaymentRequest $request, Client $client): void
    {
        if (!$request->filled('ending_billing_cycle')) {
            return;
        }

        $new = Carbon::parse($request->ending_billing_cycle)->startOfDay()->toDateString();
        $old = $client->ending_billing_cycle
            ? $client->ending_billing_cycle->copy()->startOfDay()->toDateString()
            : null;

        if ($old === $new) {
            return;
        }

        $client->ending_billing_cycle = $new;
        $client->save();

        if (!$old) {
            return;
        }

        $orderIds = PaymentOrder::query()
            ->where('client_id', $client->id)
            ->where('order_state_id', 1)
            ->whereDate('date_of_due', $old)
            ->pluck('id');

        if ($orderIds->isEmpty()) {
            return;
        }

        PaymentOrder::query()->whereIn('id', $orderIds)->update(['date_of_due' => $new]);

        $linkedPaymentIds = ClientPayment::query()
            ->where('client_id', $client->id)
            ->whereIn('payment_order_id', $orderIds)
            ->where('state', false)
            ->pluck('id');

        ClientPayment::query()
            ->whereIn('id', $linkedPaymentIds)
            ->update(['date_of_payment' => $new]);

        foreach ($linkedPaymentIds as $cpId) {
            $cp = ClientPayment::find($cpId);
            if ($cp) {
                $this->syncTenantAccountPayment($client, $cp);
            }
        }
    }

    /**
     * Refleja client_payments en account_payments del tenant (mismo reference_id = id central).
     */
    protected function syncTenantAccountPayment(Client $client, ClientPayment $record): void
    {
        if (!$client->hostname || !$client->hostname->website) {
            return;
        }

        $tenancy = app(Environment::class);
        $tenancy->tenant($client->hostname->website);

        $dateOfPayment = $record->date_of_payment instanceof Carbon
            ? $record->date_of_payment->toDateString()
            : Carbon::parse($record->date_of_payment)->toDateString();

        $basePayload = [
            'date_of_payment' => $dateOfPayment,
            'payment_method_type_id' => $record->payment_method_type_id,
            'has_card' => (bool) $record->has_card,
            'card_brand_id' => $record->card_brand_id,
            'reference' => $record->reference,
            'payment' => $record->payment,
            'updated_at' => now()->toDateTimeString(),
        ];

        $exists = DB::connection('tenant')->table('account_payments')
            ->where('reference_id', $record->id)
            ->exists();

        if ($exists) {
            $update = $basePayload;
            if ($record->state) {
                $update['state'] = 1;
            } else {
                $update['state'] = 0;
            }
            DB::connection('tenant')->table('account_payments')
                ->where('reference_id', $record->id)
                ->update($update);
            return;
        }

        DB::connection('tenant')->table('account_payments')->insert(array_merge($basePayload, [
            'reference_id' => $record->id,
            'state' => $record->state ? 1 : 0,
            'created_at' => now()->toDateTimeString(),
        ]));
    }

    public function destroy($id)
    {
        $item = ClientPayment::findOrFail($id);

        if ($item->state) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un pago ya registrado como pagado.',
            ], 422);
        }

        $client = Client::with('hostname')->findOrFail($item->client_id);

        if ($client->hostname && $client->hostname->website) {
            $tenancy = app(Environment::class);
            $tenancy->tenant($client->hostname->website);
            DB::connection('tenant')->table('account_payments')
                ->where('reference_id', $item->id)
                ->delete();
        }

        $item->delete();

        return [
            'success' => true,
            'message' => 'Pago eliminado con éxito',
        ];
    }

    public function cancel_payment($client_payment_id)
    {
        $client_payment = ClientPayment::find($client_payment_id);
        if (!$client_payment) {
            return response()->json([
                'success' => false,
                'message' => 'Pago no encontrado.',
            ], 404);
        }
        $client_payment->state = true;
        $client_payment->save();

        $client = Client::with('hostname')->findOrFail($client_payment->client_id);
        if ($client->hostname && $client->hostname->website) {
            $tenancy = app(Environment::class);
            $tenancy->tenant($client->hostname->website);
            $this->syncTenantAccountPayment($client, $client_payment);
            DB::connection('tenant')->table('account_payments')
                ->where('reference_id', $client_payment->id)
                ->update([
                    'date_of_payment_real' => date('Y-m-d'),
                    'reference' => 'Su pago ha sido verificado correctamente',
                ]);
        }

        return [
            'success' => true,
            'message' => 'Monto pagado',
        ];
    }
}
