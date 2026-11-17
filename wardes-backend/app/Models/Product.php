<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['store_id','category_id','name','description','price','stock','unit','image_url','is_active'];
    protected function casts(): array
    {
        return ['price'=>'decimal:2','stock'=>'integer','is_active'=>'boolean'];
    }
    public function store() { return $this->belongsTo(Store::class); }
    public function category() { return $this->belongsTo(Category::class); }
    public function orderItems() { return $this->hasMany(OrderItem::class); }
}
