<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = ['user_id','store_name','description','address','latitude','longitude','is_open','is_verified','allow_cod'];
    protected function casts(): array
    {
        return ['latitude'=>'decimal:8','longitude'=>'decimal:8','is_open'=>'boolean','is_verified'=>'boolean','allow_cod'=>'boolean'];
    }
    public function user() { return $this->belongsTo(User::class); }
    public function products() { return $this->hasMany(Product::class); }
    public function orders() { return $this->hasMany(Order::class); }
}
