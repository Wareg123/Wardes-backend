<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentGatewayService
{
    public function createPayment(Order $order, string $method): array
    {
        $provider = config('services.wardes_payment.provider');
        return match ($provider) {
            'midtrans' => $this->midtrans($order, $method),
            'xendit' => $this->xendit($order, $method),
            default => throw new RuntimeException('PAYMENT_PROVIDER harus midtrans atau xendit.'),
        };
    }

    private function midtrans(Order $order, string $method): array
    {
        $key = config('services.midtrans.server_key');
        if (!$key) throw new RuntimeException('MIDTRANS_SERVER_KEY belum dikonfigurasi.');
        $base = config('services.midtrans.base_url');
        $payload = [
            'transaction_details'=>['order_id'=>$order->invoice_number,'gross_amount'=>(int)round($order->total_price)],
            'customer_details'=>['first_name'=>$order->buyer->name,'phone'=>$order->buyer->phone],
            'item_details'=>[
                ['id'=>'ORDER-'.$order->id,'price'=>(int)round($order->subtotal_price),'quantity'=>1,'name'=>'Belanja WARDES'],
                ['id'=>'SHIPPING-'.$order->id,'price'=>(int)round($order->shipping_cost),'quantity'=>1,'name'=>'Ongkos kirim'],
            ],
            'enabled_payments'=>$this->midtransMethods($method),
        ];
        $response = Http::withBasicAuth($key, '')
            ->acceptJson()->post(rtrim($base,'/').'/snap/v1/transactions', $payload)->throw()->json();
        if (empty($response['token']) || empty($response['redirect_url'])) {
            throw new RuntimeException('Respons Snap Midtrans tidak lengkap.');
        }
        return ['gateway'=>'midtrans','external_id'=>$response['token'],'payment_url'=>$response['redirect_url']];
    }

    private function midtransMethods(string $method): array
    {
        return match ($method) {
            'qris' => ['qris'],
            'bank_transfer' => ['bank_transfer','permata_va','bca_va','bni_va','bri_va'],
            'ewallet' => ['gopay','shopeepay','dana','ovo'],
            default => [],
        };
    }

    private function xendit(Order $order, string $method): array
    {
        $key = config('services.xendit.secret_key');
        if (!$key) throw new RuntimeException('XENDIT_SECRET_KEY belum dikonfigurasi.');
        $payload = [
            'external_id'=>$order->invoice_number,
            'amount'=>(float)$order->total_price,
            'description'=>'Pembayaran pesanan WARDES '.$order->invoice_number,
            'currency'=>'IDR',
            'customer'=>['given_names'=>$order->buyer->name,'mobile_number'=>$order->buyer->phone],
            'success_redirect_url'=>config('app.url').'/payment/success',
            'failure_redirect_url'=>config('app.url').'/payment/failed',
        ];
        $response = Http::withBasicAuth($key, '')->acceptJson()
            ->post('https://api.xendit.co/v2/invoices', $payload)->throw()->json();
        if (empty($response['id']) || empty($response['invoice_url'])) {
            throw new RuntimeException('Respons Invoice Xendit tidak lengkap.');
        }
        return ['gateway'=>'xendit','external_id'=>$response['id'],'payment_url'=>$response['invoice_url']];
    }

    public function validMidtransSignature(array $payload): bool
    {
        $key = config('services.midtrans.server_key');
        if (!$key || empty($payload['signature_key'])) return false;
        $expected = hash('sha512',
            ($payload['order_id'] ?? '').($payload['status_code'] ?? '').($payload['gross_amount'] ?? '').$key
        );
        return hash_equals($expected, $payload['signature_key']);
    }
}
