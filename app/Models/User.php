<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'phone', 'email', 'password', 'role', 'is_cod_blocked', 'fcm_token'];
    protected $hidden = ['password', 'remember_token', 'fcm_token'];
    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_cod_blocked' => 'boolean'];
    }
    public function store() { return $this->hasOne(Store::class); }
    public function orders() { return $this->hasMany(Order::class, 'buyer_id'); }
}
