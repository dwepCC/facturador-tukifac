<?php
namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\System\Client;
use App\Models\System\Plan;
use App\Models\Tenant\Company;
use App\Models\Tenant\Configuration;
use App\Models\Tenant\AccountPayment;
use App\Models\System\ClientPayment;
use App\Models\System\PaymentOrder;
use App\Http\Resources\Tenant\AccountPaymentCollection;
use Carbon\Carbon;
use Culqi\Culqi;
use Culqi\CulqiException;
use Hyn\Tenancy\Environment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\Tenant\CulqiEmail;
use stdClass;
use App\Models\System\Configuration as ConfigurationAdmin;
use App\Services\Tenant\TenantReadThroughCache;



use Exception;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    /** Clave de sesión para cachear la respuesta de info_plan (por usuario; se invalida al cerrar sesión). */
    private function infoPlanSessionKey(): string
    {
        return 'tenant_ui_info_plan_v1_u' . (auth()->id() ?? 0);
    }

    private function forgetCachedInfoPlan(): void
    {
        session()->forget($this->infoPlanSessionKey());
        TenantReadThroughCache::forgetInfoPlan();
    }

    public function index()
    {
        return view('tenant.account.configuration' );
    }

    public function tables()
    {
        $plans = Plan::all();
        $configuration = Configuration::first();


        return compact('plans', 'configuration');
    }

    public function paymentIndex()
    {
        $configuration = ConfigurationAdmin::first();
        $token_public_culqui = $configuration->token_public_culqui;
        $token_private_culqui = $configuration->token_private_culqui;

        return view('tenant.account.payment_index', compact("token_public_culqui", "token_private_culqui"));
    }

    public function paymentRecords()
    {
        $records = AccountPayment::all();
        return new AccountPaymentCollection($records);

    }

    public function downloadReceipt($id)
    {
        $payment = AccountPayment::findOrFail($id);

        if (empty($payment->receipt_pdf)) {
            abort(404);
        }

        if (!Storage::disk('public')->exists($payment->receipt_pdf)) {
            abort(404);
        }

        return Storage::disk('public')->download(
            $payment->receipt_pdf,
            'comprobante_pago_' . $payment->id . '.pdf'
        );
    }

    public function updatePlan(Request $request)
    {
        try{

            $company = Company::active();
            $client = Client::where('number', $company->number)->first();
            $configuration = Configuration::first();

            $configuration->plan = Plan::find($request->plan_id);
            $configuration->save();

            $client->plan_id = $request->plan_id;
            $client->save();

            $this->forgetCachedInfoPlan();

            return [
                'success' => true,
                'message' => 'Cliente Actualizado satisfactoriamente'
            ];

        }catch(Exception $e)
        {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

    }

    public function paymentCulqui(Request $request)
    {


            $configuration = ConfigurationAdmin::first();
            $token_private_culqui = $configuration->token_private_culqui;

            if(!$token_private_culqui)
            {
                return [
                    'success' => false,
                    'message' =>  'token private culqi no defined'
                ];
            }

            $user = auth()->user();

            $SECRET_API_KEY = $token_private_culqui;
            $culqi = new Culqi(array('api_key' => $SECRET_API_KEY));


            try{

                $charge = $culqi->Charges->create(
                    array(
                        "amount" => $request->precio,
                        "currency_code" => "PEN",
                        "email" => $request->email,
                        "description" =>  $request->producto,
                        "source_id" => $request->token,
                        "installments" => $request->installments
                      )
                );

            }catch(Exception $e)
            {
              return [
                  'success' => false,
                  'message' =>  $e->getMessage()
              ];
            }

            /**
             * Todo
             *  definir estados de pago en accunpayment
             */

            $account_payment = AccountPayment::find($request->id_payment_account);
            $account_payment->state = 1; // 1 ees pagado, 2 es pendiente
            $account_payment->date_of_payment_real = date('Y-m-d');
            $account_payment->save();


            $system_client_payment =  ClientPayment::find($account_payment->reference_id);
            $system_client_payment->state = 1;
            if (isset($system_client_payment->status)) {
                $system_client_payment->status = 2;
            }
            $system_client_payment->save();

            if (!empty($system_client_payment->payment_order_id)) {
                $order = PaymentOrder::find($system_client_payment->payment_order_id);
                if ($order) {
                    $order->date_of_payment = now()->toDateTimeString();
                    $order->order_state_id = 2;
                    $order->save();
                }
            }

            $client = Client::with(['hostname', 'period'])->find($system_client_payment->client_id);
            if ($client) {
                $client->locked_tenant = false;
                if ($client->ending_billing_cycle) {
                    $months = optional($client->period)->months ? $client->period->months : 1;
                    $client->ending_billing_cycle = Carbon::parse($client->ending_billing_cycle)->addMonths($months);
                }
                $client->save();
            }

            $configurationTenant = Configuration::first();
            if ($configurationTenant) {
                $configurationTenant->locked_tenant = false;
                $configurationTenant->save();
            }


            $customer_email = $request->email;
            $document = new stdClass;
            $document->client = $user->name;
            $document->product = $request->producto;
            $document->total = $request->precio_culqi;
            $document->items = json_decode($request->items, true);
            $email = $customer_email;
            $mailable =new CulqiEmail($document);
            $id =  $document->id;
            $model = __FILE__."::".__LINE__;
            $sendIt = EmailController::SendMail($email, $mailable, $id, $model);
            /*
            Configuration::setConfigSmtpMail();
        $array_email = explode(',', $customer_email);
        if (count($array_email) > 1) {
            foreach ($array_email as $email_to) {
                $email_to = trim($email_to);
                if(!empty($email_to)) {
                    Mail::to($email_to)->send(new CulqiEmail($document));
                }
            }
        } else {
            Mail::to($customer_email)->send(new CulqiEmail($document));
        }*/

            $this->forgetCachedInfoPlan();

            return [
                'success' => true,
                'culqui' => $charge,
                'message' => 'Pago efectuado correctamente'
            ];
    }

//tukifac
    public function infoPlan()
    {
        $sessionKey = $this->infoPlanSessionKey();
        if (session()->has($sessionKey)) {
            return session($sessionKey);
        }

        $response = TenantReadThroughCache::remember(
            TenantReadThroughCache::KEY_INFO_PLAN,
            TenantReadThroughCache::TTL_INFO_PLAN_SECONDS,
            fn () => $this->buildInfoPlanPayload()
        );

        session()->put($sessionKey, $response);

        return $response;
    }

    /**
     * Carga plan / vencimiento desde BD central (sin sesión ni Redis).
     */
    private function buildInfoPlanPayload(): array
    {
        $client = null;
        $hostname = app(Environment::class)->hostname();
        if ($hostname) {
            $client = Client::with(['plan'])->where('hostname_id', $hostname->id)->first();
        }

        if (! $client) {
            $company = Company::active();
            if ($company && $company->number) {
                $client = Client::with(['plan'])->where('number', $company->number)->first();
            }
        }

        $today = Carbon::now()->startOfDay();

        $nextDueDate = null;
        $activeOrder = null;

        if ($client) {
            $activeOrder = PaymentOrder::query()
                ->where('client_id', $client->id)
                ->whereIn('order_state_id', [1, 3, 5, 6])
                ->orderBy('date_of_due', 'asc')
                ->orderBy('id', 'asc')
                ->first();

            if ($client->ending_billing_cycle) {
                $nextDueDate = Carbon::parse($client->ending_billing_cycle)->startOfDay();
            }
        }

        // Inicializar variables para días
        $daysOverdue = 0;
        $daysRemaining = 0;
        $paymentDateText = 'Al corriente';
        $statusPlan = 'Estás al día en tus pagos';
        $hasPendingPayment = false;
        $daysIndicatorClass = ''; // Clase CSS para el color del indicador

        if ($nextDueDate) {
            $paymentDateText = $nextDueDate->format('d/m/Y');
            $hasPendingPayment = true;

            if ($activeOrder) {
                if ((int) $activeOrder->order_state_id === 5) {
                    $statusPlan = 'Pago en verificación';
                    $daysIndicatorClass = 'text-info';
                } elseif ((int) $activeOrder->order_state_id === 6) {
                    $statusPlan = 'Pago rechazado';
                    $daysIndicatorClass = 'text-danger';
                }
            }

            if ($nextDueDate->greaterThan($today)) {
                if ($activeOrder && ((int) $activeOrder->order_state_id === 1 || (int) $activeOrder->order_state_id === 3)) {
                    $statusPlan = 'Pago pendiente';
                } elseif (! $activeOrder) {
                    $statusPlan = 'Estás al día en tus pagos';
                }
                $daysRemaining = $today->diffInDays($nextDueDate);
                $daysOverdue = 0;

                if (empty($daysIndicatorClass)) {
                    $daysIndicatorClass = $daysRemaining > 5 ? 'text-success' : 'text-warning';
                }
            } else {
                $daysOverdue = $today->diffInDays($nextDueDate);
                $daysRemaining = 0;

                if (empty($daysIndicatorClass)) {
                    $daysIndicatorClass = $daysOverdue > 0 ? 'text-danger' : 'text-warning';
                }

                if (! $activeOrder) {
                    $statusPlan = $daysOverdue > 0 ? 'Pago vencido' : 'Pago pendiente';
                } elseif ((int) $activeOrder->order_state_id === 1 || (int) $activeOrder->order_state_id === 3) {
                    $statusPlan = $daysOverdue > 0 ? 'Pago vencido' : 'Pago pendiente';
                }
            }
        }

        // Función auxiliar para limpiar UTF-8 y eliminar caracteres mal formados
        $cleanUtf8 = function ($string) {
            if (is_null($string) || ! is_string($string)) {
                return is_string($string) ? $string : '';
            }
            // Asegurar que sea UTF-8 válido, eliminando caracteres inválidos
            if (! mb_check_encoding($string, 'UTF-8')) {
                $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8//IGNORE');
            }
            // Filtrar caracteres de control no válidos
            $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);

            return $string;
        };

        $planName = 'Sin plan';
        if ($client && $client->plan && isset($client->plan->name)) {
            $planName = $cleanUtf8($client->plan->name);
        } else {
            $configurationTenant = Configuration::first();
            if ($configurationTenant && $configurationTenant->plan && isset($configurationTenant->plan->name)) {
                $planName = $cleanUtf8($configurationTenant->plan->name);
            }
        }

        $systemConfig = ConfigurationAdmin::first();
        $reminderDays = (int) ($systemConfig->day_before_due ?? 3);
        if ($reminderDays <= 0) {
            $reminderDays = 3;
        }

        $showPaymentReminder = false;
        if ($hasPendingPayment && $daysRemaining > 0 && $daysRemaining <= $reminderDays) {
            $showPaymentReminder = true;
        }

        $activeOrderStateId = $activeOrder ? (int) $activeOrder->order_state_id : null;

        return [
            'success' => true,
            'plan_name' => $planName,
            'status_plan' => $cleanUtf8($statusPlan),
            'payment_date' => $cleanUtf8($paymentDateText),
            'days_overdue' => (int) $daysOverdue,
            'days_remaining' => (int) $daysRemaining,
            'has_pending_payment' => $hasPendingPayment,
            'days_indicator_class' => $daysIndicatorClass,
            'reminder_days' => $reminderDays,
            'show_payment_reminder' => $showPaymentReminder,
            'order_state_id' => $activeOrderStateId,
        ];
    }

    public function paymentManual(Request $request)
    {
        try {
            // Validar solo los datos básicos (sin exists)
            $request->validate([
                'id_payment_account' => 'required|integer',
                'payment_voucher' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Buscar el pago en la base de datos del tenant
            $account_payment = AccountPayment::find($request->id_payment_account);
            
            if (!$account_payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pago no encontrado'
                ], 404);
            }

            // Procesar la imagen del comprobante
            if ($request->hasFile('payment_voucher')) {
                $image = $request->file('payment_voucher');
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                
                // Guardar la imagen en storage
                $imagePath = $image->storeAs('payment_vouchers', $imageName, 'public');
                
                // Obtener la URL completa de la imagen
                $imageUrl = asset('storage/' . $imagePath);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se recibió el archivo del comprobante'
                ], 400);
            }

            // Actualizar el pago en el tenant (sin guardar la imagen aquí)
            //$account_payment->state = 1;
            $account_payment->date_of_payment_real = now();
            $account_payment->payment_method_type_id = '1';
            $account_payment->reference_payment = $imageUrl;
            $account_payment->reference = 'Pago enviado para verificación';
            $account_payment->save();

            // Actualizar el estado en client_payment (base de datos central) y guardar la imagen
            $system_client_payment = ClientPayment::find($account_payment->reference_id);
            if ($system_client_payment) {
                $system_client_payment->state = 0; 
                if (isset($system_client_payment->status)) {
                    $system_client_payment->status = 1;
                }
                $system_client_payment->reference = $imageUrl;
                $system_client_payment->date_of_payment = now();
                $system_client_payment->save();

                if (!empty($system_client_payment->payment_order_id)) {
                    $order = PaymentOrder::find($system_client_payment->payment_order_id);
                    if ($order) {
                        $order->order_state_id = 5;
                        $order->save();
                    }
                }

                $client = Client::with(['hostname', 'period'])->find($system_client_payment->client_id);
                if ($client) {
                    $client->locked_tenant = false;

                    if (!empty($system_client_payment->payment_order_id)) {
                        $order = PaymentOrder::find($system_client_payment->payment_order_id);
                        if ($order && $order->date_of_due) {
                            $months = optional($client->period)->months ? $client->period->months : 1;
                            $client->ending_billing_cycle = Carbon::parse($order->date_of_due)->addMonths($months);
                        }
                    }

                    $client->save();
                }

                $configurationTenant = Configuration::first();
                if ($configurationTenant) {
                    $configurationTenant->locked_tenant = false;
                    $configurationTenant->save();
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el registro de pago en la base de datos central'
                ], 404);
            }

            $this->forgetCachedInfoPlan();

            return response()->json([
                'success' => true,
                'message' => 'Pago manual registrado correctamente y enviado para aprobación',
                'payment_id' => $account_payment->id,
                'proof_path' => $imagePath,
                'proof_url' => $imageUrl
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación ',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago manual: ' . $e->getMessage()
            ], 500);
        }
    }
//end tukifac
}
