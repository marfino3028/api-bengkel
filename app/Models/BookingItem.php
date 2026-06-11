<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingItem extends Model
{
    protected $fillable = [
        'booking_id', 'item_type', 'item_id', 'name', 'price', 'qty', 'subtotal',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'qty' => 'integer',
        'subtotal' => 'decimal:2',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
