<?php
namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\ApiController;
use App\Models\FawryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends ApiController
{

    public function fawryWebhook(Request $request)
    {
        $data = $request->all();
        Log::info('Fawry Webhook received', $data);

        $merchantCode = config('services.fawry.merchant_code');
        $securityKey  = config('services.fawry.secure_key');

        // بناء الـ hash زي ما فوري موثقاه
        $stringToHash = $merchantCode
            . ($data['orderAmount'] ?? '')
            . ($data['fawryRefNumber'] ?? '')
            . ($data['merchantRefNumber'] ?? '')
            . ($data['orderStatus'] ?? '')
            . ($data['paymentMethod'] ?? '')
            . $securityKey;

        $expectedSignature = hash('sha256', $stringToHash);

        if (! (strtolower($expectedSignature) === strtolower($data['messageSignature'] ?? ''))) {
            Log::warning('Fawry Webhook invalid signature', [
                'expected' => $expectedSignature,
                'received' => $data['messageSignature'] ?? null,
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $merchantRefNum = $data['merchantRefNumber'] ?? null;
        $fawryRefNumber = $data['fawryRefNumber'] ?? null;
        $orderStatus    = $data['orderStatus'] ?? null;
        $paymentAmount  = $data['orderAmount'] ?? null;

        if (! $merchantRefNum) {
            return response()->json(['error' => 'Missing merchantRefNumber'], 400);
        }

        $trx = FawryTransaction::where('merchant_ref', $merchantRefNum)->first();

        if (! $trx) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        // update transaction
        $trx->reference_number = $fawryRefNumber ?? $trx->reference_number;
        $trx->status           = $orderStatus ?? $trx->status;
        $trx->response         = $data;
        $trx->amount           = $paymentAmount ?? $trx->amount;
        $trx->save();

        return response()->json(['success' => true]);
    }

    public function returnUrl(Request $request)
    {

        $data = [
            'merchantRefNum'  => $request->input('merchantRefNum') ?? $request->input('merchantRefNumber'),
            'referenceNumber' => $request->input('referenceNumber') ?? $request->input('fawryRefNumber'),
            'amount'          => $request->input('paymentAmount') ?? $request->input('amount'),
            'orderStatus'     => $request->input('orderStatus') ?? $request->input('status'),
            'message'         => $request->input('message') ?? null,
        ];

        // يمكنك هنا أيضاً طلب التحقق من حالة الدفع عبر API (optional)
        // ثم إرسال النتيجة للعرض

        return view('payments.return', [
            'status'          => $data['orderStatus'] ?? null,
            'merchantRefNum'  => $data['merchantRefNum'] ?? null,
            'referenceNumber' => $data['referenceNumber'] ?? null,
            'amount'          => $data['amount'] ?? null,
            'message'         => $data['message'] ?? null,
        ]);
    }

}
