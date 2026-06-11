<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'booking_code', 'user_id', 'customer_name', 'customer_phone',
        'vehicle_brand', 'vehicle_model', 'vehicle_plate', 'vehicle_year',
        'scheduled_at', 'complaint', 'admin_notes', 'status',
        'service_total', 'parts_total', 'grand_total',
        'payment_status', 'payment_method', 'paid_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'paid_at' => 'datetime',
        'service_total' => 'decimal:2',
        'parts_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(BookingItem::class);
    }

    public function recalculateTotals(): void
    {
        $this->service_total = $this->items()->where('item_type', 'service')->sum('subtotal');
        $this->parts_total = $this->items()->where('item_type', 'part')->sum('subtotal');
        $this->grand_total = $this->service_total + $this->parts_total;
        $this->save();
    }
}
