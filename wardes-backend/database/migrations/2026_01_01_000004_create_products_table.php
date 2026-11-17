<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id(); $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name'); $table->text('description')->nullable();
            $table->decimal('price',12,2); $table->unsignedInteger('stock')->default(0);
            $table->enum('unit',['kg','pcs','ikat','bungkus']); $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps();
            $table->index(['store_id','is_active']); $table->index('category_id');
        });
    }
    public function down(): void { Schema::dropIfExists('products'); }
};
