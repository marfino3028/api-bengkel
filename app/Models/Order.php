<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_code', 'user_id', 'customer_name', 'customer_phone',
        'fulfillment', 'shipping_address', 'subtotal', 'shipping_cost', 'total',
        'status', 'payment_status', 'payment_method', 'paid_at', 'notes',
        'payment_gateway', 'snap_token', 'payment_reference',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
