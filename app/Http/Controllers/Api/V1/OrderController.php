<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Payment;
use App\Services\PaymentGatewayService;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class OrderController extends Controller
{
    public function checkout(Request $request, PaymentGatewayService $gateway, FcmService $fcm)
    {
        $data = $request->validate([
            'store_id'=>'required|exists:stores,id',
            'items'=>'required|array|min:1',
            'items.*.product_id'=>'required|integer|distinct|exists:products,id',
            'items.*.quantity'=>'required|integer|min:1|max:1000',
            'delivery_method'=>['required',Rule::in(['local_courier','pickup'])],
            'delivery_address'=>'required_if:delivery_method,local_courier|nullable|string',
            'delivery_lat'=>'nullable|numeric|between:-90,90',
            'delivery_lng'=>'nullable|numeric|between:-180,180',
            'notes'=>'nullable|string|max:2000',
            'payment_method'=>['required',Rule::in(['cod','qris','bank_transfer','ewallet'])],
        ]);

        $store = \App\Models\Store::findOrFail($data['store_id']);
        if (!$store->is_verified || !$store->is_open) return response()->json(['message'=>'Warung belum terverifikasi atau sedang tutup.'],422);
        if ($data['payment_method']==='cod' && !$store->allow_cod) return response()->json(['message'=>'Warung tidak menerima COD.'],422);

        try {
            [$order, $payment] = DB::transaction(function () use ($request,$data,$store) {
                $subtotal = 0; $locked = [];
                foreach ($data['items'] as $item) {
                    $product = Product::whereKey($item['product_id'])->lockForUpdate()->firstOrFail();
                    if ($product->store_id !== $store->id || !$product->is_active) {
                        throw new RuntimeException('Semua produk harus aktif dan berasal dari warung yang sama.');
                    }
                    if ($product->stock < $item['quantity']) throw new RuntimeException("Stok produk {$product->name} tidak mencukupi.");
                    $subtotal += (float)$product->price * $item['quantity'];
                    $locked[] = [$product,$item['quantity']];
                }
                $shipping = $data['delivery_method']==='pickup' ? 0 : (float)config('services.wardes_payment.default_shipping',5000);
                $total = $subtotal + $shipping;
                if ($data['payment_method']==='cod' && $request->user()->is_cod_blocked) {
                    throw new RuntimeException('Akun Anda diblokir dari pembayaran COD.');
                }
                if ($data['payment_method']==='cod' && $total > 500000) {
                    throw new RuntimeException('Batas maksimal transaksi COD adalah Rp500.000.');
                }
                $invoice = 'WRD-'.now()->format('Ymd').'-'.Str::upper(Str::random(4));
                while (Order::where('invoice_number',$invoice)->exists()) {
                    $invoice = 'WRD-'.now()->format('Ymd').'-'.Str::upper(Str::random(4));
                }
                $order = Order::create([
                    'invoice_number'=>$invoice,'buyer_id'=>$request->user()->id,'store_id'=>$store->id,
                    'subtotal_price'=>$subtotal,'shipping_cost'=>$shipping,'total_price'=>$total,
                    'delivery_method'=>$data['delivery_method'],'delivery_address'=>$data['delivery_address'] ?? null,
                    'delivery_lat'=>$data['delivery_lat'] ?? null,'delivery_lng'=>$data['delivery_lng'] ?? null,
                    'order_status'=>'pending','notes'=>$data['notes'] ?? null,
                ]);
                foreach ($locked as [$product,$qty]) {
                    $order->items()->create(['product_id'=>$product->id,'quantity'=>$qty,'price_at_purchase'=>$product->price]);
                    $product->decrement('stock',$qty);
                }
                $gatewayName = $data['payment_method']==='cod' ? 'manual_cod' : config('services.wardes_payment.provider');
                if (!in_array($gatewayName,['midtrans','xendit','manual_cod'],true)) throw new RuntimeException('Payment provider tidak valid.');
                $payment = Payment::create([
                    'order_id'=>$order->id,'payment_method'=>$data['payment_method'],
                    'payment_gateway'=>$gatewayName,'payment_status'=>'unpaid',
                ]);
                return [$order,$payment];
            });

            if ($data['payment_method'] !== 'cod') {
                try {
                    $details = $gateway->createPayment($order->load('buyer'),$data['payment_method']);
                    $payment->update($details);
                } catch (\\Throwable $gatewayError) {
                    // Provider failure: cancel the just-created order and return reserved stock.
                    DB::transaction(function () use ($order, $payment) {
                        $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->first();
                        if ($lockedOrder && $lockedOrder->order_status === 'pending') {
                            foreach ($lockedOrder->items as $item) {
                                $item->product()->lockForUpdate()->first()?->increment('stock', $item->quantity);
                            }
                            $lockedOrder->update(['order_status' => 'cancelled']);
                            $payment->update(['payment_status' => 'failed']);
                        }
                    });
                    report($gatewayError);
                    return response()->json(['message'=>'Penyedia pembayaran sedang tidak tersedia. Stok telah dikembalikan; silakan coba lagi.'], 502);
                }
            }
            $fcm->sendNewOrder($store->user, $order->invoice_number);
            return response()->json([
                'message'=>'Pesanan berhasil dibuat.','order'=>$order->load('items.product','store'),
                'payment'=>$payment->fresh(),
            ],201);
        } catch (RuntimeException $e) {
            return response()->json(['message'=>$e->getMessage()],422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message'=>'Checkout gagal diproses. Silakan coba kembali.'],500);
        }
    }

    public function mine(Request $request)
    {
        return response()->json(Order::with(['store','items.product','payment'])
            ->where('buyer_id',$request->user()->id)->latest()->paginate(20));
    }

    public function show(Request $request, Order $order)
    {
        abort_unless($order->buyer_id===$request->user()->id || $request->user()->role==='admin' ||
            $request->user()->store?->id===$order->store_id,403);
        return response()->json($order->load(['store','items.product','payment']));
    }
}
