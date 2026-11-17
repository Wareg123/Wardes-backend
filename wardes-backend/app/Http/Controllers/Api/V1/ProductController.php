<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $q = Product::query()->with(['store','category'])
            ->where('is_active',true)->whereHas('store',fn($s)=>$s->where('is_verified',true)->where('is_open',true));
        if ($request->filled('q')) $q->where('name','like','%'.$request->string('q').'%');
        if ($request->filled('category_id')) $q->where('category_id',$request->integer('category_id'));
        return response()->json($q->latest()->paginate(24));
    }

    public function byStore($storeId)
    {
        return response()->json(Product::with('category')->where('store_id',$storeId)->where('is_active',true)->paginate(24));
    }

    public function myProducts(Request $request)
    {
        $store = $request->user()->store;
        if (!$store) return response()->json(['message'=>'Lengkapi profil warung terlebih dahulu.'],422);
        return response()->json($store->products()->with('category')->latest()->paginate(30));
    }

    public function store(Request $request)
    {
        $store = $request->user()->store;
        if (!$store) return response()->json(['message'=>'Lengkapi profil warung terlebih dahulu.'],422);
        $data = $request->validate([
            'category_id'=>'required|exists:categories,id','name'=>'required|string|max:255',
            'description'=>'nullable|string','price'=>'required|numeric|min:0.01',
            'stock'=>'required|integer|min:0','unit'=>['required',Rule::in(['kg','pcs','ikat','bungkus'])],
            'image_url'=>'nullable|url|max:2048','image'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'is_active'=>'sometimes|boolean',
        ]);
        if ($request->hasFile('image')) {
            $data['image_url'] = Storage::disk('public')->url($request->file('image')->store('wardes/products','public'));
        }
        unset($data['image']);
        return response()->json($store->products()->create($data),201);
    }

    public function update(Request $request, Product $product)
    {
        abort_unless($request->user()->store?->id === $product->store_id,403);
        $data = $request->validate([
            'category_id'=>'sometimes|exists:categories,id','name'=>'sometimes|string|max:255',
            'description'=>'nullable|string','price'=>'sometimes|numeric|min:0.01',
            'stock'=>'sometimes|integer|min:0','unit'=>['sometimes',Rule::in(['kg','pcs','ikat','bungkus'])],
            'image_url'=>'nullable|url|max:2048','image'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'is_active'=>'sometimes|boolean',
        ]);
        if ($request->hasFile('image')) {
            if ($product->image_url && str_contains($product->image_url, '/storage/')) {
                $oldPath = ltrim(parse_url($product->image_url, PHP_URL_PATH) ?: '', '/');
                if (str_starts_with($oldPath, 'storage/')) Storage::disk('public')->delete(substr($oldPath, 8));
            }
            $data['image_url'] = Storage::disk('public')->url($request->file('image')->store('wardes/products','public'));
        }
        unset($data['image']);
        $product->update($data);
        return response()->json($product->fresh());
    }

    public function destroy(Request $request, Product $product)
    {
        abort_unless($request->user()->store?->id === $product->store_id,403);
        $product->update(['is_active'=>false]);
        return response()->json(['message'=>'Produk dinonaktifkan.']);
    }
}
