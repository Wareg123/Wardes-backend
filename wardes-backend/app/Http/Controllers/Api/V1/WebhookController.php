<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request, PaymentGatewayService $gateway)
    {
        $payload = $request->all();
        $provider = config('services.wardes_payment.provider');

        if ($provider==='midtrans') {
            if (!$gateway->validMidtransSignature($payload)) {
                return response()->json(['message'=>'Signature tidak valid.'],403);
            }
            $invoice = $payload['order_id'] ?? null;
            $status = $payload['transaction_status'] ?? '';
            $fraud = $payload['fraud_status'] ?? '';
            $paid = in_array($status,['settlement','capture'],true) && ($fraud==='' || $fraud==='accept');
            $failed = in_array($status,['deny','cancel','expire'],true);
        } elseif ($provider==='xendit') {
            $token = config('services.xendit.callback_token');
            if (!$token || !hash_equals($token,(string)$request->header('x-callback-token'))) {
                return response()->json(['message'=>'Callback token tidak valid.'],403);
            }
            $invoice = $payload['external_id'] ?? null;
            $status = strtoupper($payload['status'] ?? '');
            $paid = $status==='PAID';
            $failed = in_array($status,['EXPIRED','FAILED'],true);
        } else {
            return response()->json(['message'=>'Provider tidak dikenal.'],422);
        }

        if (!$invoice) return response()->json(['message'=>'Invoice tidak ditemukan.'],422);
        DB::transaction(function () use ($invoice,$paid,$failed,$payload) {
            $payment = Payment::whereHas('order',fn($q)=>$q->where('invoice_number',$invoice))
                ->lockForUpdate()->first();
            if (!$payment) {
                Log::warning('Webhook payment invoice unknown',['invoice'=>$invoice]);
                return;
            }
            if ($payment->payment_status==='paid') return;
            if ($paid) $payment->update(['payment_status'=>'paid','paid_at'=>now()]);
            elseif ($failed) $payment->update(['payment_status'=>strtolower(($payload['transaction_status'] ?? $payload['status'] ?? 'failed'))==='expire' ? 'expired':'failed']);
        });
        return response()->json(['received'=>true]);
    }
}
