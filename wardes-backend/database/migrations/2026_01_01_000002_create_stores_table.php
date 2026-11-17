<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stores', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('store_name'); $table->text('description')->nullable(); $table->text('address');
            $table->decimal('latitude',10,8); $table->decimal('longitude',11,8);
            $table->boolean('is_open')->default(true); $table->boolean('is_verified')->default(false);
            $table->boolean('allow_cod')->default(true); $table->timestamps();
            $table->index(['is_verified','is_open']);
        });
    }
    public function down(): void { Schema::dropIfExists('stores'); }
};
