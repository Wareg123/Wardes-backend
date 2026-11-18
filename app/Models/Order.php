<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'invoice_number','buyer_id','store_id','subtotal_price','shipping_cost','total_price',
        'delivery_method','delivery_address','delivery_lat','delivery_lng','order_status','notes'
    ];
    protected function casts(): array
    {
        return ['subtotal_price'=>'decimal:2','shipping_cost'=>'decimal:2','total_price'=>'decimal:2',
            'delivery_lat'=>'decimal:8','delivery_lng'=>'decimal:8'];
    }
    public function buyer() { return $this->belongsTo(User::class, 'buyer_id'); }
    public function store() { return $this->belongsTo(Store::class); }
    public function items() { return $this->hasMany(OrderItem::class); }
    public function payment() { return $this->hasOne(Payment::class); }
}
