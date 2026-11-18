<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('phone', 20)->unique();
            $table->string('email')->nullable()->unique(); $table->string('password');
            $table->enum('role', ['buyer','seller','admin'])->default('buyer');
            $table->boolean('is_cod_blocked')->default(false); $table->text('fcm_token')->nullable();
            $table->rememberToken(); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('users'); }
};
