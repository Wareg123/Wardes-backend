<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    public function nearby(Request $request)
    {
        $data = $request->validate([
            'lat'=>'required|numeric|between:-90,90',
            'lng'=>'required|numeric|between:-180,180',
            'radius'=>'sometimes|numeric|min:0.1|max:100',
        ]);
        $radius = (float)($data['radius'] ?? 10);
        $lat = (float)$data['lat']; $lng = (float)$data['lng'];
        $stores = Store::query()->with('products.category')->where('is_verified',true)->where('is_open',true)
            ->selectRaw('stores.*, (6371 * acos(LEAST(1, GREATEST(-1,
                cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) +
                sin(radians(?)) * sin(radians(latitude))
            )))) AS distance_km', [$lat,$lng,$lat])
            ->having('distance_km','<=',$radius)->orderBy('distance_km')->paginate(20);
        return response()->json($stores);
    }

    public function show(Store $store)
    {
        abort_unless($store->is_verified,404);
        return response()->json($store->load('products.category'));
    }

    public function myStore(Request $request)
    {
        return response()->json($request->user()->store);
    }

    public function upsert(Request $request)
    {
        $data = $request->validate([
            'store_name'=>'required|string|max:255','description'=>'nullable|string',
            'address'=>'required|string','latitude'=>'required|numeric|between:-90,90',
            'longitude'=>'required|numeric|between:-180,180','is_open'=>'sometimes|boolean',
            'allow_cod'=>'sometimes|boolean',
        ]);
        $store = Store::firstOrNew(['user_id'=>$request->user()->id]);
        $isNew = !$store->exists;
        $store->fill($data);
        if ($isNew) $store->is_verified = false;
        $store->save();
        return response()->json(['message'=>'Profil warung disimpan.','store'=>$store]);
    }
}
