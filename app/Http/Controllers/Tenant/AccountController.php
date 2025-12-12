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
        $records = AccountPayment::all();
        $payments = $records;

        // Encontrar el próximo pago pendiente (state = false)
        $pendingPayment = $payments->first(function ($payment) {
            return $payment['state'] === false || $payment['state'] == 0;
        });

        // Inicializar variables para días
        $daysOverdue = 0;
        $daysRemaining = 0;
        $paymentDateText = 'Al corriente';
        $statusPlan = 'Estás al día en tus pagos';

        if ($pendingPayment) {
            $statusPlan = 'Pendiente de pago';
            
            // Verificar si date_of_payment existe y no está vacío
            if (!empty($pendingPayment['date_of_payment'])) {
                try {
                    // Parsear la fecha en formato Y-m-d H:i:s (formato de base de datos)
                    $paymentDate = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $pendingPayment['date_of_payment']);
                    $today = \Carbon\Carbon::now();
                    
                    // Formatear la fecha para mostrar (d/m/Y)
                    $paymentDateText = $paymentDate->format('d/m/Y');
                    
                    if ($paymentDate->greaterThan($today)) {
                        // Días faltantes para el pago
                        $daysRemaining = $today->diffInDays($paymentDate);
                        $daysOverdue = 0;
                    } else {
                        // Días vencidos
                        $daysOverdue = $today->diffInDays($paymentDate);
                        $daysRemaining = 0;
                        $statusPlan = 'Pago vencido';
                    }
                } catch (\Exception $e) {
                    // Si hay error en el formato de fecha, usar la fecha original
                    $paymentDateText = $pendingPayment['date_of_payment'];
                    $daysOverdue = 0;
                    $daysRemaining = 0;
                }
            }
        }

        // Formatear datos para la vista
        $response = [
            'success' => true,
            'plan_name' => $plan->name,
            'status_plan' => $statusPlan,
            'payment_date' => $paymentDateText,
            'days_overdue' => $daysOverdue,
            'days_remaining' => $daysRemaining,
            'has_pending_payment' => (bool)$pendingPayment
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
