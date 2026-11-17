<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\StoreController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\SellerOrderController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Http\Controllers\Api\V1\CategoryController;

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/webhooks/payment-gateway', [WebhookController::class, 'handle']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/stores/nearby', [StoreController::class, 'nearby']);
    Route::get('/stores/{store}', [StoreController::class, 'show']);
    Route::get('/stores/{store}/products', [ProductController::class, 'byStore']);
    Route::get('/products', [ProductController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/fcm-token', [AuthController::class, 'saveFcmToken']);

        Route::post('/orders/checkout', [OrderController::class, 'checkout']);
        Route::get('/orders', [OrderController::class, 'mine']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);

        Route::middleware('role:seller')->prefix('seller')->group(function () {
            Route::get('/store', [StoreController::class, 'myStore']);
            Route::put('/store', [StoreController::class, 'upsert']);
            Route::get('/products', [ProductController::class, 'myProducts']);
            Route::post('/products', [ProductController::class, 'store']);
            Route::put('/products/{product}', [ProductController::class, 'update']);
            Route::delete('/products/{product}', [ProductController::class, 'destroy']);
            Route::get('/orders', [SellerOrderController::class, 'index']);
            Route::patch('/orders/{order}/status', [SellerOrderController::class, 'updateStatus']);
        });

        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::get('/dashboard', [AdminController::class, 'dashboard']);
            Route::get('/stores/pending', [AdminController::class, 'pendingStores']);
            Route::patch('/stores/{store}/verify', [AdminController::class, 'verifyStore']);
            Route::patch('/users/{user}/cod-block', [AdminController::class, 'toggleCodBlock']);
        });
    });
});
