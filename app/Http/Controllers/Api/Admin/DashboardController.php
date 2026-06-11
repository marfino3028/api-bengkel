<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ProductResource;
use App\Models\Booking;
use App\Models\Order;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        $bookingRevenue = Booking::where('payment_status', 'paid')->sum('grand_total');
        $orderRevenue = Order::where('payment_status', 'paid')->sum('total');

        $bookingRevenueMonth = Booking::where('payment_status', 'paid')
            ->where('paid_at', '>=', $startOfMonth)->sum('grand_total');
        $orderRevenueMonth = Order::where('payment_status', 'paid')
            ->where('paid_at', '>=', $startOfMonth)->sum('total');

        return response()->json([
            'data' => [
                'counts' => [
                    'products' => Product::count(),
                    'services' => Service::count(),
                    'customers' => User::where('role', 'customer')->count(),
                    'bookings_pending' => Booking::where('status', 'pending')->count(),
                    'orders_pending' => Order::where('status', 'pending')->count(),
                    'bookings_total' => Booking::count(),
                    'orders_total' => Order::count(),
                ],
                'revenue' => [
                    'total' => (float) ($bookingRevenue + $orderRevenue),
                    'this_month' => (float) ($bookingRevenueMonth + $orderRevenueMonth),
                ],
                'recent_bookings' => BookingResource::collection(
                    Booking::with('items')->latest()->take(5)->get()
                ),
                'recent_orders' => OrderResource::collection(
                    Order::with('items')->latest()->take(5)->get()
                ),
                'low_stock' => ProductResource::collection(
                    Product::where('stock', '<=', 5)->orderBy('stock')->take(5)->get()
                ),
            ],
        ]);
    }
}
