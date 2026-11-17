<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Sembako','Makanan','Sayur & Buah','Produk UMKM'] as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
        User::firstOrCreate(
            ['phone' => '081234567890'],
            ['name' => 'WARDES Super Admin', 'email' => 'admin@wardes.local',
             'password' => 'WardesAdmin!2026', 'role' => 'admin']
        );
    }
}
