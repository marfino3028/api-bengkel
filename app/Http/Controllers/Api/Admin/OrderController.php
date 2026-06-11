<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()->with(['items', 'user']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        return OrderResource::collection($query->latest()->paginate(15)->withQueryString());
    }

    public function show(Order $order)
    {
        return new OrderResource($order->load(['items.product', 'user']));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,processing,completed,cancelled'],
        ]);

        // Mengembalikan stok bila dibatalkan dari status non-cancel.
        if ($data['status'] === 'cancelled' && $order->status !== 'cancelled') {
            DB::transaction(function () use ($order) {
                foreach ($order->items as $item) {
                    if ($item->product_id) {
                        Product::where('id', $item->product_id)->increment('stock', $item->qty);
                    }
                }
                $order->update(['status' => 'cancelled']);
            });
        } else {
            $order->update($data);
        }

        return new OrderResource($order->load(['items', 'user']));
    }

    public function updatePayment(Request $request, Order $order)
    {
        $data = $request->validate([
            'payment_status' => ['required', 'in:unpaid,paid'],
            'payment_method' => ['nullable', 'in:cash,transfer'],
        ]);

        $order->update([
            'payment_status' => $data['payment_status'],
            'payment_method' => $data['payment_method'] ?? $order->payment_method,
            'paid_at' => $data['payment_status'] === 'paid' ? Carbon::now() : null,
        ]);

        return new OrderResource($order->load(['items', 'user']));
    }
}
