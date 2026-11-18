<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'orders_total'=>Order::count(),
            'gross_total'=>(float)Order::whereHas('payment',fn($q)=>$q->where('payment_status','paid'))->sum('total_price'),
            'cod'=>['count'=>Order::whereHas('payment',fn($q)=>$q->where('payment_method','cod'))->count(),
                'paid'=>(float)Order::whereHas('payment',fn($q)=>$q->where('payment_method','cod')->where('payment_status','paid'))->sum('total_price')],
            'digital'=>['count'=>Order::whereHas('payment',fn($q)=>$q->whereIn('payment_method',['qris','bank_transfer','ewallet']))->count(),
                'paid'=>(float)Order::whereHas('payment',fn($q)=>$q->whereIn('payment_method',['qris','bank_transfer','ewallet'])->where('payment_status','paid'))->sum('total_price')],
            'pending_store_verification'=>Store::where('is_verified',false)->count(),
        ]);
    }

    public function pendingStores()
    {
        return response()->json(Store::with('user')->where('is_verified',false)->latest()->paginate(25));
    }

    public function verifyStore(Store $store)
    {
        $store->update(['is_verified'=>true]);
        return response()->json(['message'=>'Warung berhasil diverifikasi.','store'=>$store]);
    }

    public function toggleCodBlock(Request $request, User $user)
    {
        $data = $request->validate(['is_cod_blocked'=>'required|boolean']);
        $user->update($data);
        return response()->json(['message'=>'Status COD akun diperbarui.','user'=>$user->only(['id','name','phone','is_cod_blocked'])]);
    }
}
