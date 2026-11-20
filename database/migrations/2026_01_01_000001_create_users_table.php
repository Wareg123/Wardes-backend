
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->unique();
            $table->enum('role', ['buyer', 'seller', 'admin'])->default('buyer');
            $table->boolean('is_cod_blocked')->default(false);
            $table->text('fcm_token')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropColumn([
                'phone',
                'role',
                'is_cod_blocked',
                'fcm_token',
            ]);
        });
    }
};
