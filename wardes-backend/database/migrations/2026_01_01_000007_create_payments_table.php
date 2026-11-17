<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payments', function (Blueprint $table) {
            $table->id(); $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('payment_method',['cod','qris','bank_transfer','ewallet']);
            $table->enum('payment_gateway',['midtrans','xendit','manual_cod']);
            $table->string('external_id')->nullable()->unique(); $table->text('payment_url')->nullable();
            $table->enum('payment_status',['unpaid','paid','expired','failed'])->default('unpaid');
            $table->timestamp('paid_at')->nullable(); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('payments'); }
};
