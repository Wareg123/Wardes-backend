<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); $table->string('invoice_number')->unique();
            $table->foreignId('buyer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->decimal('subtotal_price',12,2); $table->decimal('shipping_cost',12,2)->default(0);
            $table->decimal('total_price',12,2);
            $table->enum('delivery_method',['local_courier','pickup']);
            $table->text('delivery_address')->nullable(); $table->decimal('delivery_lat',10,8)->nullable();
            $table->decimal('delivery_lng',11,8)->nullable();
            $table->enum('order_status',['pending','confirmed','processing','delivering','completed','cancelled'])->default('pending');
            $table->text('notes')->nullable(); $table->timestamps();
            $table->index(['buyer_id','created_at']); $table->index(['store_id','order_status']);
        });
    }
    public function down(): void { Schema::dropIfExists('orders'); }
};
