<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Support\CodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->with('items')
            ->latest()
            ->paginate(10);

        return OrderResource::collection($orders);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'fulfillment' => ['required', 'in:pickup,delivery'],
            'shipping_address' => ['required_if:fulfillment,delivery', 'nullable', 'string', 'max:500'],
            'payment_method' => ['required', 'in:cash,transfer'],
            'payment_gateway' => ['nullable', 'in:manual,midtrans'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        $order = DB::transaction(function () use ($data, $user) {
            $order = Order::create([
                'order_code' => CodeGenerator::order(),
                'user_id' => $user->id,
                'customer_name' => $user->name,
                'customer_phone' => $user->phone ?? '-',
                'fulfillment' => $data['fulfillment'],
                'shipping_address' => $data['shipping_address'] ?? null,
                'subtotal' => 0,
                'shipping_cost' => 0,
                'total' => 0,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $data['payment_method'],
                'payment_gateway' => $data['payment_gateway'] ?? 'manual',
                'notes' => $data['notes'] ?? null,
            ]);

            $subtotal = 0;

            foreach ($data['items'] as $row) {
                $product = Product::where('id', $row['product_id'])->where('is_active', true)->lockForUpdate()->first();

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => ['Produk tidak tersedia.'],
                    ]);
                }

                if ($product->stock < $row['qty']) {
                    throw ValidationException::withMessages([
                        'items' => ["Stok {$product->name} tidak mencukupi (sisa {$product->stock})."],
                    ]);
                }

                $lineSubtotal = $product->price * $row['qty'];
                $subtotal += $lineSubtotal;

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $product->price,
                    'qty' => $row['qty'],
                    'subtotal' => $lineSubtotal,
                ]);

                $product->decrement('stock', $row['qty']);
            }

            $order->update([
                'subtotal' => $subtotal,
                'total' => $subtotal + $order->shipping_cost,
            ]);

            return $order;
        });

        app(\App\Services\NotificationService::class)->orderCreated($order);

        return new OrderResource($order->load('items'));
    }

    public function show(Request $request, string $code)
    {
        $order = Order::query()
            ->where('order_code', $code)
            ->where('user_id', $request->user()->id)
            ->with('items')
            ->firstOrFail();

        return new OrderResource($order);
    }

    public function cancel(Request $request, string $code)
    {
        $order = Order::query()
            ->where('order_code', $code)
            ->where('user_id', $request->user()->id)
            ->with('items')
            ->firstOrFail();

        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Pesanan hanya bisa dibatalkan saat masih menunggu.',
            ], 422);
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                if ($item->product_id) {
                    Product::where('id', $item->product_id)->increment('stock', $item->qty);
                }
            }
            $order->update(['status' => 'cancelled']);
        });

        return new OrderResource($order->load('items'));
    }
}
