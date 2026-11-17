<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'=>'required|string|max:255',
            'phone'=>'required|string|max:20|unique:users,phone',
            'email'=>'nullable|email|max:255|unique:users,email',
            'password'=>'required|string|min:8|confirmed',
            'role'=>['sometimes', Rule::in(['buyer','seller'])],
        ]);
        $user = User::create($data + ['role'=>$data['role'] ?? 'buyer']);
        return response()->json(['message'=>'Registrasi berhasil.','user'=>$user,'token'=>$user->createToken('wardes-client')->plainTextToken], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['phone'=>'required|string','password'=>'required|string']);
        $user = User::where('phone',$data['phone'])->first();
        if (!$user || !Hash::check($data['password'],$user->password)) {
            return response()->json(['message'=>'Nomor HP atau password salah.'], 422);
        }
        $user->tokens()->delete();
        return response()->json(['user'=>$user,'token'=>$user->createToken('wardes-client')->plainTextToken]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message'=>'Berhasil logout.']);
    }

    public function me(Request $request) { return response()->json(['user'=>$request->user()->load('store')]); }

    public function saveFcmToken(Request $request)
    {
        $data = $request->validate(['fcm_token'=>'required|string|max:10000']);
        $request->user()->update($data);
        return response()->json(['message'=>'FCM token tersimpan.']);
    }
}
