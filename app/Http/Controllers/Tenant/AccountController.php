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
use App\Http\Resources\Tenant\AccountPaymentCollection;
use Culqi\Culqi;
use Culqi\CulqiException;
use Illuminate\Support\Facades\Mail;
use App\Mail\Tenant\CulqiEmail;
use stdClass;
use App\Models\System\Configuration as ConfigurationAdmin;



use Exception;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
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
            $system_client_payment->save();


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

            return [
                'success' => true,
                'culqui' => $charge,
                'message' => 'Pago efectuado correctamente'
            ];
    }

//tukifac
    public function infoPlan()
    {
        $configuration = Configuration::first();
        $plan = $configuration->plan;
        
        $today = \Carbon\Carbon::now()->startOfDay();
        
        // Buscar el próximo pago pendiente: sin fecha real de pago Y sin estado pagado
        // Ordenar por fecha de pago ascendente para obtener el próximo pendiente
        $nextPayment = AccountPayment::where('state', false)
            ->whereNull('date_of_payment_real')
            ->orderBy('date_of_payment', 'asc')
            ->first();

        // Inicializar variables para días
        $daysOverdue = 0;
        $daysRemaining = 0;
        $paymentDateText = 'Al corriente';
        $statusPlan = 'Estás al día en tus pagos';
        $hasPendingPayment = false;
        $daysIndicatorClass = ''; // Clase CSS para el color del indicador

        if ($nextPayment) {
            // Obtener la fecha de pago (ya es un objeto Carbon por el cast del modelo)
            $paymentDate = \Carbon\Carbon::parse($nextPayment->date_of_payment)->startOfDay();
            
            // Formatear la fecha para mostrar (d/m/Y)
            $paymentDateText = $paymentDate->format('d/m/Y');
            
            if ($paymentDate->greaterThan($today)) {
                // El pago está en el futuro, el cliente está al día
                $statusPlan = 'Estás al día en tus pagos';
                $daysRemaining = $today->diffInDays($paymentDate);
                $daysOverdue = 0;
                $hasPendingPayment = true; // Hay un pago futuro pendiente
                
                // Determinar el color según días restantes (sistema de semáforo)
                if ($daysRemaining > 5) {
                    $daysIndicatorClass = 'text-success'; // Verde: más de 5 días
                } else {
                    $daysIndicatorClass = 'text-warning'; // Naranja/Amarillo: 5 días o menos
                }
            } else {
                // El pago ya venció, está pendiente o vencido
                $daysOverdue = $today->diffInDays($paymentDate);
                $daysRemaining = 0;
                $hasPendingPayment = true;
                $daysIndicatorClass = 'text-danger'; // Rojo: días vencidos
                
                if ($daysOverdue > 0) {
                    $statusPlan = 'Pago vencido';
                } else {
                    // Es hoy
                    $statusPlan = 'Pago pendiente';
                    $daysIndicatorClass = 'text-warning'; // Naranja si es hoy
                }
            }
        }

        // Función auxiliar para limpiar UTF-8 y eliminar caracteres mal formados
        $cleanUtf8 = function($string) {
            if (is_null($string) || !is_string($string)) {
                return is_string($string) ? $string : '';
            }
            // Asegurar que sea UTF-8 válido, eliminando caracteres inválidos
            if (!mb_check_encoding($string, 'UTF-8')) {
                $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8//IGNORE');
            }
            // Filtrar caracteres de control no válidos
            $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
            return $string;
        };

        // Obtener el nombre del plan de forma segura
        $planName = ($plan && isset($plan->name)) ? $cleanUtf8($plan->name) : 'Sin plan';

        // Formatear datos para la vista asegurando codificación UTF-8 correcta
        $response = [
            'success' => true,
            'plan_name' => $planName,
            'status_plan' => $cleanUtf8($statusPlan),
            'payment_date' => $cleanUtf8($paymentDateText),
            'days_overdue' => (int)$daysOverdue,
            'days_remaining' => (int)$daysRemaining,
            'has_pending_payment' => $hasPendingPayment,
            'days_indicator_class' => $daysIndicatorClass // Clase CSS para el color
        ];

        return $response;
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
            $account_payment->save();

            // Actualizar el estado en client_payment (base de datos central) y guardar la imagen
            $system_client_payment = ClientPayment::find($account_payment->reference_id);
            if ($system_client_payment) {
                $system_client_payment->state = 0; 
                $system_client_payment->reference = $imageUrl;
                $system_client_payment->date_of_payment = now();
                $system_client_payment->save();
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el registro de pago en la base de datos central'
                ], 404);
            }

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
