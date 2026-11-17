<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SellerOrderController extends Controller
{
    public function index(Request $request)
    {
        $store = $request->user()->store;
        if (!$store) return response()->json(['message'=>'Profil warung belum dibuat.'],422);
        return response()->json(Order::with(['buyer','items.product','payment'])
            ->where('store_id',$store->id)->latest()->paginate(25));
    }

    public function updateStatus(Request $request, Order $order)
    {
        abort_unless($request->user()->store?->id===$order->store_id,403);
        $data = $request->validate(['order_status'=>['required',Rule::in(['confirmed','processing','delivering','completed','cancelled'])]]);
        try {
            DB::transaction(function () use ($order,$data) {
                $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                $allowed = [
                    'pending'=>['confirmed','cancelled'],
                    'confirmed'=>['processing','cancelled'],
                    'processing'=>['delivering','completed','cancelled'],
                    'delivering'=>['completed','cancelled'],
                    'completed'=>[],
                    'cancelled'=>[],
                ];
                $next = $data['order_status'];
                if (!in_array($next,$allowed[$order->order_status] ?? [],true)) {
                    throw new RuntimeException('Perubahan status pesanan tidak valid.');
                }
                $order->update(['order_status'=>$next]);
                if ($next==='completed' && $order->payment?->payment_method==='cod') {
                    $order->payment->update(['payment_status'=>'paid','paid_at'=>now()]);
                }
                if ($next==='cancelled' && $order->payment?->payment_status==='unpaid') {
                    foreach ($order->items as $item) {
                        $item->product()->lockForUpdate()->first()?->increment('stock',$item->quantity);
                    }
                    $order->payment?->update(['payment_status'=>'failed']);
                }
            });
            return response()->json(['message'=>'Status pesanan diperbarui.','order'=>$order->fresh()->load('payment')]);
        } catch (RuntimeException $e) {
            return response()->json(['message'=>$e->getMessage()],422);
        }
    }
}
