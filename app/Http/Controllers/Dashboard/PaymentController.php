<?php
namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\ApiController;
use App\Models\FawryTransaction;
use App\Services\FawryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends ApiController
{
    protected $fawry;
    public function __construct(FawryService $fawry)
    {
        $this->fawry = $fawry;
    }

    public function fawryWebhook(Request $request)
    {
        $data = $request->all();
        Log::info('Fawry Webhook received', $data);

        // ✅ Verify signature
        if (! $this->fawry->verifyWebhookSignature($data)) {
            Log::warning('Fawry Webhook invalid signature', $data);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $merchantRefNum  = $data['merchantRefNumber'] ?? null;
        $referenceNumber = $data['referenceNumber'] ?? null;
        $orderStatus     = $data['orderStatus'] ?? null;
        $paymentAmount   = $data['orderAmount'] ?? null;

        if (! $merchantRefNum) {
            return response()->json(['error' => 'Missing merchantRefNumber'], 400);
        }

        $trx = FawryTransaction::where('merchant_ref', $merchantRefNum)->first();

        if (! $trx) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        // update transaction
        $trx->reference_number = $referenceNumber ?? $trx->reference_number;
        $trx->status           = $orderStatus ?? $trx->status;
        $trx->response         = $data;
        $trx->amount           = $paymentAmount ?? $trx->amount;
        $trx->save();

        return response()->json(['success' => true]);
    }

    
}
