<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Order;
use Illuminate\Support\Carbon;

class CodeGenerator
{
    public static function booking(): string
    {
        $date = Carbon::now()->format('Ymd');
        $seq = Booking::query()->whereDate('created_at', Carbon::today())->count() + 1;

        return sprintf('BK-%s-%04d', $date, $seq);
    }

    public static function order(): string
    {
        $date = Carbon::now()->format('Ymd');
        $seq = Order::query()->whereDate('created_at', Carbon::today())->count() + 1;

        return sprintf('ORD-%s-%04d', $date, $seq);
    }
}
