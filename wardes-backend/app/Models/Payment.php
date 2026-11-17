<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['order_id','payment_method','payment_gateway','external_id','payment_url','payment_status','paid_at'];
    protected function casts(): array { return ['paid_at'=>'datetime']; }
    public function order() { return $this->belongsTo(Order::class); }
}
