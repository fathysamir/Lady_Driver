<?php
namespace App\Http\Controllers;

use App\Models\FawryTransaction;
use App\Services\FawryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApiController extends Controller
{
    protected $fawry;
    public function __construct(FawryService $fawry)
    {
        $this->fawry = $fawry;
    }
    public function sendResponse($data, $message = null, $code = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    public function sendError($data = null, $message = null, $code = 400): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }
    public function check_banned()
    {
        if (auth()->user()->status == 'banned') {
            return 'this account is banned, the ban will be lifted soon';
        } else {
            return true;
        }
    }

    public function createPayment(Request $request)
    {

        $v = Validator::make($request->all(), [
            'paymentMethod'         => 'required|string|in:PayAtFawry,PayUsingCC,FawryWallet',
            'amount'                => 'required|numeric|min:0.01',
            'customerMobile'        => 'required|string',
            'customerEmail'         => 'required|email',
            'chargeItems'           => 'required|array|min:1',
            'customerProfileId'     => 'nullable|exists:users,id',
            'customerName'          => 'nullable|string',
            'description'           => 'nullable|string',

            // Card
            'cardNumber'            => 'required_if:paymentMethod,PayUsingCC|nullable|string',
            'cardExpiryYear'        => 'required_if:paymentMethod,PayUsingCC|nullable|string',
            'cardExpiryMonth'       => 'required_if:paymentMethod,PayUsingCC|nullable|string',
            'cvv'                   => 'required_if:paymentMethod,PayUsingCC|nullable|string',
            'returnUrl'             => 'required_if:paymentMethod,PayUsingCC,FawryWallet|nullable|url',

            'walletMobile'          => 'required_if:paymentMethod,FawryWallet|nullable|string',
            'walletProviderService' => 'required_if:paymentMethod,FawryWallet|nullable|string',
        ]);

        // if ($v->fails()) {
        //     return response()->json(['error' => $v->errors()->all()], 422);
        // }
        if ($v->fails()) {

            $errors = implode(" / ", $v->errors()->all());

            return $this->sendError(null, $errors, 400);
        }
       
        $merchantRefNum = auth()->user()->id . '_md-' . Str::random(10) . '-' . time();
        $amount         = $request->amount;
        $method         = $request->paymentMethod;

        // ====== Build signature depending on method ======
        switch ($method) {
            case 'PayAtFawry':
                $sig = $this->fawry->makeReferenceSignature(
                    $merchantRefNum,
                    $request->customerProfileId ?? '',
                    $method,
                    floatval($amount)
                );
                break;

            case 'PayUsingCC':
                $sig = $this->fawry->make3DSCardSignature(
                    $merchantRefNum,
                    $request->customerProfileId ?? '',
                    $method,
                    $amount,
                    $request->cardNumber,
                    $request->cardExpiryYear,
                    $request->cardExpiryMonth,
                    $request->cvv,
                    $request->returnUrl
                );
                break;

            case 'FawryWallet':
                $sig = $this->fawry->makeWalletSignature(
                    $merchantRefNum,
                    $request->customerProfileId ?? '',
                    $method,
                    $amount,
                    $request->walletMobile
                );
                break;

            default:
                return $this->sendError(null, 'Unsupported payment method', 400);
        }

        // ====== Build payload ======
        $payload = [
            'merchantCode'    => config('services.fawry.merchant_code'),
            'merchantRefNum'  => $merchantRefNum,
            'customerMobile'  => $request->customerMobile,
            'customerEmail'   => $request->customerEmail,
            'customerName'    => $request->customerName ?? '',
            'amount'          => $this->fawry->fmtAmount($amount),
            'chargeItems'     => $request->chargeItems,
            'signature'       => $sig,
            'paymentMethod'   => $method,
            'description'     => $request->description ?? 'Payment',
            'orderWebHookUrl' => route('api.fawry.webhook'),
        ];

        // extra fields for Card
        if ($method === 'PayUsingCC') {
            $payload = array_merge($payload, [
                'cardNumber'      => $request->cardNumber,
                'cardExpiryYear'  => $request->cardExpiryYear,
                'cardExpiryMonth' => $request->cardExpiryMonth,
                'cvv'             => $request->cvv,
                'returnUrl'       => $request->returnUrl,
                'enable3DS'       => true,
            ]);
        }

        // extra fields for Wallet
        if ($method === 'FawryWallet') {
            $payload = array_merge($payload, [
                'walletMobile'          => $request->walletMobile,
                'walletProviderService' => $request->walletProviderService,
                'returnUrl'             => $request->returnUrl,
            ]);
        }

        // ====== Store local transaction ======
        $trx = FawryTransaction::create([
            'user_id'        => auth()->id() ?? null,
            'merchant_ref'   => $merchantRefNum,
            'amount'         => $amount,
            'payment_method' => $method,
            'status'         => 'PENDING',
        ]);

        try {
            // call correct method
            if ($method === 'PayAtFawry') {
                $resp = $this->fawry->createReferenceCharge($payload);
            } elseif ($method === 'PayUsingCC') {
                $resp = $this->fawry->create3DSCardCharge($payload);
            } else {
                $resp = $this->fawry->createWalletCharge($payload);
            }

            $trx->reference_number = $resp['referenceNumber'] ?? null;
            $trx->response         = $resp;
            $trx->status           = $resp['orderStatus'] ?? $resp['statusDescription'] ?? $trx->status;
            $trx->save();

            return $this->sendResponse($resp, 'Success Payment', 200);

        } catch (\Throwable $e) {
            Log::error("Fawry createPayment error: " . $e->getMessage());
            return $this->sendError($e->getMessage(), 'Failed to create payment', 500);
        }
    }

}
