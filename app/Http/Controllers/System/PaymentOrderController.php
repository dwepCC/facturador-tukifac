<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Resources\System\PaymentOrderCollection;
use App\Models\System\Client;
use App\Models\System\ClientPayment;
use App\Models\System\Configuration;
use App\Models\System\PaymentMethodType;
use App\Models\System\PaymentOrder;
use App\Models\System\PaymentOrderState;
use App\Models\System\Plan;
use App\Models\System\PlanPeriod;
use Carbon\Carbon;
use Hyn\Tenancy\Environment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentOrderController extends Controller
{
    public function index()
    {
        return view('system.payments.index');
    }

    public function tables()
    {
        $clients = Client::all()->transform(function($row) {
            return [
                'id' => $row->id,
                'name' => $row->name,
            ];
        });
        $status = PaymentOrderState::all();
        $active_cron = Configuration::first()->active_cron;
        $clientPlans = Client::all()->transform(function($row) {
            $payments = PaymentOrder::paymentsTotals($row->id)->get();
            $payments = PaymentOrderState::all()->transform(function($row) use ($payments) {
                $total = $payments->where('id', $row->id)->first();
                return (object) [
                    'id' => $row->id,
                    'name' => strtoupper($row->name),
                    'total' => $total ? $total->total : 0,
                ];
            });
            $ending = Carbon::now()->diffAsCarbonInterval(Carbon::parse($row->ending_billing_cycle));           
            $result = [];
            if ($ending->y) $result[] = $ending->y . ' años';
            if ($ending->m) $result[] = $ending->m . ' meses';
            if ($ending->d) $result[] = $ending->d . ' días';
            if ($ending->h) $result[] = $ending->h . ' horas';
            if ($ending->i) $result[] = $ending->i . ' minutos';

            $ending = implode(' ', array_slice($result, 0, 2));
            return [
                'id' => $row->id,
                'name' => $row->name,
                'number' => $row->number,
                'price' => $row->getPricePlan(),
                'contact_email' => $row->contact_email,
                'phone_ws' => $row->phone_ws,
                'client_name' => $row->client_name,
                'plan_id' => $row->plan_id,
                'plan_period_id' => $row->plan_period_id,
                'ending_billing_cycle' => $row->ending_billing_cycle,
                'start_billing_cycle' => $row->start_billing_cycle,
                'plan' => [
                    'name' => $row->plan ? $row->plan->name : 'Sin plan',
                    'price' => $row->getPricePlan(),
                    'period' => $row->period ? $row->period->name : 'Mensual',
                    'active' => $row->activeService(),
                    'date_of_ending' => $row->ending_billing_cycle ? Carbon::parse($row->ending_billing_cycle)->format('Y-m-d') : '',
                    'diff_date_of_ending' => $ending,
                ],
                'phone_ws' => $row->phone_ws,
                'created_at' => $row->created_at->format('Y-m-d'),  
                'diff_time_creation' => Carbon::parse($row->created_at)->diffForHumans(),
                'pays' => $payments
            ];
        });

        $clients->prepend(['id' => null, 'name' => 'Todos']);

        return compact('clients', 'status', 'clientPlans', 'active_cron');
    }

    public function records(Request $request)
    {
        $paymen_orders = PaymentOrder::query();
        $payments = PaymentOrder::paymentsTotals()
        ->get();

        $payments = PaymentOrderState::all()->transform(function($row) use ($payments) {
            $total = $payments->where('id', $row->id)->first();
            return (object) [
                'id' => $row->id,
                'name' => $row->name,
                'total' => $total ? $total->total : 0,
            ];
        });

        if ($request->client_id) {
            $paymen_orders->where('client_id', $request->client_id);
        }
        if ($request->order_state_id) {
            $paymen_orders->where('order_state_id', $request->order_state_id);
        }

        if ($request->date_start || $request->date_end) {
            $date_start = $request->date_start ? Carbon::parse($request->date_start)->format('Y-m-d') : now()->format('Y-m-d');
            $date_end = $request->date_end ? Carbon::parse($request->date_end)->format('Y-m-d') : now()->format('Y-m-d');
            $paymen_orders->whereBetween('date_of_due', [$date_start, $date_end]);
            
        }

        return (new PaymentOrderCollection($paymen_orders->paginate(config('tenant.items_per_page'))))->additional([
            'pays' =>  $payments
        ]);
    }


    public function record($id, Request $request)
    {
        
        
    }

    public function create(Request $request) 
    {
        $validated = $request->validate([
            'client_id' => 'required',
            'amount' => 'required|numeric|min:0',
            'date_of_due' => 'required|date',
            'description' => 'nullable|string',
            'notify' => 'boolean'
        ]);

        $order = PaymentOrder::where('client_id', $validated['client_id'])
                    ->whereIn('order_state_id', [1, 3, 5, 6])
                    ->first();

        if ($order) {
            return [
                'success' => false,
                'message' => 'El cliente ya tiene una orden de pago pendiente',
            ];
        }
        $or = null;

        DB::beginTransaction();

        $or = PaymentOrder::create([
            'order' => str_pad((PaymentOrder::count() + 1), 6, '0', STR_PAD_LEFT),
            'client_id' => $validated['client_id'],
            'amount' => $validated['amount'],
            'date_of_due' => $validated['date_of_due'],
            'description' => $validated['description'] ?? null,
            'order_state_id' => 1,
            'notifications' => 0,
            'created_by' => 'Manual' 
        ]);
        
        DB::commit();

        $client = Client::with('hostname')->find($validated['client_id']);
        if ($client && $client->hostname) {
            $client->locked_tenant = true;
            $client->save();

            $payment_method_type_id = (int) (PaymentMethodType::query()->orderBy('id')->value('id') ?? 0);
            if ($payment_method_type_id > 0) {
                $client_payment = ClientPayment::query()->where('payment_order_id', $or->id)->first();
                if (!$client_payment) {
                    $client_payment = ClientPayment::query()->create([
                        'client_id' => $client->id,
                        'payment_order_id' => $or->id,
                        'date_of_payment' => $or->date_of_due,
                        'payment_method_type_id' => $payment_method_type_id,
                        'has_card' => false,
                        'card_brand_id' => null,
                        'reference' => null,
                        'payment' => $or->amount,
                        'state' => false,
                        'status' => 0,
                    ]);
                }

                $tenancy = app(Environment::class);
                $tenancy->tenant($client->hostname->website);
                DB::connection('tenant')->table('configurations')->where('id', 1)->update(['locked_tenant' => $client->locked_tenant]);

                $exists_in_tenant = DB::connection('tenant')->table('account_payments')
                    ->where('reference_id', $client_payment->id)
                    ->exists();

                if (!$exists_in_tenant) {
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
            }
        }

        if ($validated['notify'] && $or) {
            $this->notify($or->id);
        }

        return [
            'success' => true,  
            'message' => 'Orden de pago creada con éxito',
        ];

    }

    public function pays(int $id)
    {
        $model = PaymentOrder::find($id);
        if (!$model) {
            return [
                'success' => false,
                'message' => 'Orden de pago no encontrada',
            ];
        }
        $model->date_of_payment = now()->toDateTimeString();
        $model->order_state_id = 2;
        $model->save();

        $client = Client::with(['hostname', 'period'])->find($model->client_id);
        if ($client && $client->hostname) {
            $client_payment = ClientPayment::query()->where('payment_order_id', $model->id)->first();
            if ($client_payment) {
                $client_payment->state = 1;
                if (isset($client_payment->status)) {
                    $client_payment->status = 2;
                }
                $client_payment->save();

                $tenancy = app(Environment::class);
                $tenancy->tenant($client->hostname->website);
                DB::connection('tenant')->table('account_payments')
                    ->where('reference_id', $client_payment->id)
                    ->update([
                        'state' => 1,
                        'date_of_payment_real' => now()->toDateString(),
                    ]);
            }

            $client->locked_tenant = false;

            if (!empty($client->ending_billing_cycle)) {
                $months = optional($client->period)->months ? $client->period->months : 1;
                $client->ending_billing_cycle = Carbon::parse($client->ending_billing_cycle)->addMonths($months);
            }
            $client->save();

            $tenancy = app(Environment::class);
            $tenancy->tenant($client->hostname->website);
            DB::connection('tenant')->table('configurations')->where('id', 1)->update(['locked_tenant' => false]);
        }


        return [
            'success' => true,
            'message' => 'Orden de pago pagada con éxito',
        ];
    }

    public function cancel($id, Request $request)
    {
        $model = PaymentOrder::find($id);
        if (!$model) {
            return [
                'success' => false,
                'message' => 'Orden de pago no encontrada',
            ];
        }

        $model->order_state_id = 4;
        $model->save();

        $client = Client::with('hostname')->find($model->client_id);
        if ($client && $client->hostname) {
            $client_payment = ClientPayment::query()->where('payment_order_id', $model->id)->first();
            if ($client_payment) {
                $client_payment->state = 0;
                if (isset($client_payment->status)) {
                    $client_payment->status = 3;
                }
                $client_payment->reference = null;
                $client_payment->save();

                $tenancy = app(Environment::class);
                $tenancy->tenant($client->hostname->website);
                DB::connection('tenant')->table('account_payments')
                    ->where('reference_id', $client_payment->id)
                    ->update([
                        'state' => 0,
                        'reference_payment' => null,
                        'date_of_payment_real' => null,
                    ]);
            }

            $client->locked_tenant = false;
            $client->save();
            $tenancy = app(Environment::class);
            $tenancy->tenant($client->hostname->website);
            DB::connection('tenant')->table('configurations')->where('id', 1)->update(['locked_tenant' => false]);
        }

        return [
            'success' => true,
            'message' => 'Orden de pago anulada con éxito',
        ];
    }


    public function updateTable(Request $request)
    {
        $model = PaymentOrder::find($request->id);
        if (!$model) {
            return [
                'success' => false,
                'message' => 'Orden de pago no encontrada',
            ];
        }

        $original_due_date = $model->date_of_due ? $model->date_of_due->format('Y-m-d') : null;
        $client = Client::with('hostname')->find($model->client_id);
        $client_payment = ClientPayment::query()->where('payment_order_id', $model->id)->first();

        if (!$client_payment && $client) {
            $payment_method_type_id = (int) (PaymentMethodType::query()->orderBy('id')->value('id') ?? 0);
            if ($payment_method_type_id > 0) {
                $client_payment = ClientPayment::query()->create([
                    'client_id' => $client->id,
                    'payment_order_id' => $model->id,
                    'date_of_payment' => $model->date_of_due,
                    'payment_method_type_id' => $payment_method_type_id,
                    'has_card' => false,
                    'card_brand_id' => null,
                    'reference' => null,
                    'payment' => $model->amount,
                    'state' => false,
                    'status' => 0,
                ]);
            }
        }

        if ($request->date_of_due) {
            if (Carbon::parse($request->date_of_due)->greaterThan($model->date_of_due)) {
                $model->date_of_due = $request->date_of_due;
                if ($client && $client->hostname) {
                    $client->locked_tenant = false;
                    $client->save();
                    $tenancy = app(Environment::class);
                    $tenancy->tenant($client->hostname->website);
                    DB::connection('tenant')->table('configurations')->where('id', 1)->update(['locked_tenant' => false]);
                }

                $model->order_state_id = 1;
            } else if (Carbon::parse($request->date_of_due)->lessThan($model->date_of_due))
            {
                $model->date_of_due = $request->date_of_due;
                if ($client && $client->hostname) {
                    $client->locked_tenant = true;
                    $client->save();
                }
                $model->order_state_id = 3;
                if ($client && $client->hostname) {
                    $tenancy = app(Environment::class);
                    $tenancy->tenant($client->hostname->website);
                    DB::connection('tenant')->table('configurations')->where('id', 1)->update(['locked_tenant' => true]);
                }
            }
        }
        if ($request->price) {
            $model->amount = $request->price;
        }

        $model->save();

        if ($client_payment) {
            $date_due_changed = (bool) $request->date_of_due && $request->date_of_due !== $original_due_date;
            $client_payment->payment = $model->amount;
            $client_payment->date_of_payment = $model->date_of_due;
            if ($date_due_changed) {
                $client_payment->state = 0;
                if (isset($client_payment->status)) {
                    $client_payment->status = 0;
                }
                $client_payment->reference = null;
            }
            $client_payment->save();

            if ($client && $client->hostname) {
                $tenancy = app(Environment::class);
                $tenancy->tenant($client->hostname->website);

                $exists_in_tenant = DB::connection('tenant')->table('account_payments')
                    ->where('reference_id', $client_payment->id)
                    ->exists();

                if ($exists_in_tenant) {
                    $update = [
                        'date_of_payment' => $client_payment->date_of_payment,
                        'payment' => $client_payment->payment,
                    ];
                    if ($date_due_changed) {
                        $update['state'] = 0;
                        $update['reference_payment'] = null;
                        $update['date_of_payment_real'] = null;
                    }
                    DB::connection('tenant')->table('account_payments')
                        ->where('reference_id', $client_payment->id)
                        ->update($update);
                } else {
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
            }
        }

        return [
            'success' => true,
            'message' => 'Orden de pago actualizada con éxito',
        ];
    }

    public function notify($id)
    {
        $model = PaymentOrder::find($id);

        $response = $model->client->notifyOrder($id);
        if ($response['success']) {
            $model->notifications = $model->notifications + 1;
            $model->date_of_notification = now()->toDateTimeString();
            $model->save();
        }
        return $response;

    }

    public function updateClient($id, Request $request)
    {
        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'plan_id' => 'required|integer|exists:plans,id',
            'plan_period_id' => 'required|integer|exists:plan_periods,id',
            'phone_ws' => 'nullable|string|max:20',
            'ending_billing_cycle' => 'nullable|date',
            'start_billing_cycle' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) use ($id, $request) {
                    if (is_null($value)) {
                        return;
                    }

                    $client = Client::find($id);
                    $start_billing_cycle = Carbon::parse($value)->startOfDay();
                    if (!isset($client->ending_billing_cycle)) {
                        return;
                    }
                    $ending_billing_cycle = $client->ending_billing_cycle->startOfDay();
                    if (isset($request->ending_billing_cycle)) {
                        $ending_billing_cycle = Carbon::parse($request->ending_billing_cycle)->startOfDay();
                    }

                    if ($start_billing_cycle->day > $ending_billing_cycle->day) {
                        $fail('La fecha de activación debe ser anterior a la fecha de finalización del servicio');
                    }
                },
            ],
            'price' => 'numeric|min:0',
        ]);

        Client::find($id)->update([
            'client_name' => $validated['client_name'],
            'price' => $validated['price'],
            'contact_email' => $validated['contact_email'],
            'plan_id' => $validated['plan_id'],
            'plan_period_id' => $validated['plan_period_id'],
            'phone_ws' => $validated['phone_ws'] ?? null,
            'ending_billing_cycle' => isset($validated['ending_billing_cycle']) ? $validated['ending_billing_cycle'] : null,
            'start_billing_cycle' => isset($validated['start_billing_cycle']) ? $validated['start_billing_cycle'] : null,
        ]);
        
        return [
            'success' => true,
            'message' => 'Cliente actualizado con éxito',
        ];
    }

    public function clientTables(Request $request)
    {
        $plans = Plan::all()->transform(function($row) {
            return [
                'id' => $row->id,
                'name' => $row->name,
                'price' => $row->price,
                'currency_type_id' => $row->currency_type_id,
            ];
        });
        $periods = PlanPeriod::all()->transform(function($row) {
            return [
                'id' => $row->id,
                'name' => $row->name,
                'months' => $row->months,
            ];
        });

        return compact('plans', 'periods');
    }

}
