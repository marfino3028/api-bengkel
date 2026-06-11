<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::query()->with(['items', 'user']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('vehicle_plate', 'like', "%{$search}%");
            });
        }

        return BookingResource::collection($query->latest()->paginate(15)->withQueryString());
    }

    public function show(Booking $booking)
    {
        return new BookingResource($booking->load(['items', 'user']));
    }

    public function updateStatus(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,confirmed,in_progress,completed,cancelled'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $booking->update($data);

        app(\App\Services\NotificationService::class)->bookingStatusChanged($booking);

        return new BookingResource($booking->load(['items', 'user']));
    }

    public function addItem(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'item_type' => ['required', 'in:service,part'],
            'item_id' => ['nullable', 'integer'],
            'name' => ['required_without:item_id', 'nullable', 'string', 'max:255'],
            'price' => ['required_without:item_id', 'nullable', 'numeric', 'min:0'],
            'qty' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($data, $booking) {
            $name = $data['name'] ?? null;
            $price = $data['price'] ?? 0;

            if ($data['item_type'] === 'part' && ! empty($data['item_id'])) {
                $product = Product::lockForUpdate()->findOrFail($data['item_id']);
                if ($product->stock < $data['qty']) {
                    throw ValidationException::withMessages([
                        'qty' => ["Stok {$product->name} tidak mencukupi (sisa {$product->stock})."],
                    ]);
                }
                $product->decrement('stock', $data['qty']);
                $name = $product->name;
                $price = $product->price;
            } elseif ($data['item_type'] === 'service' && ! empty($data['item_id'])) {
                $service = Service::findOrFail($data['item_id']);
                $name = $service->name;
                $price = $service->price;
            }

            $booking->items()->create([
                'item_type' => $data['item_type'],
                'item_id' => $data['item_id'] ?? null,
                'name' => $name,
                'price' => $price,
                'qty' => $data['qty'],
                'subtotal' => $price * $data['qty'],
            ]);

            $booking->recalculateTotals();
        });

        return new BookingResource($booking->load(['items', 'user']));
    }

    public function removeItem(Booking $booking, BookingItem $item)
    {
        abort_unless($item->booking_id === $booking->id, 404);

        DB::transaction(function () use ($booking, $item) {
            if ($item->item_type === 'part' && $item->item_id) {
                Product::where('id', $item->item_id)->increment('stock', $item->qty);
            }
            $item->delete();
            $booking->recalculateTotals();
        });

        return new BookingResource($booking->load(['items', 'user']));
    }

    public function updatePayment(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'payment_status' => ['required', 'in:unpaid,paid'],
            'payment_method' => ['nullable', 'in:cash,transfer'],
        ]);

        $booking->update([
            'payment_status' => $data['payment_status'],
            'payment_method' => $data['payment_method'] ?? $booking->payment_method,
            'paid_at' => $data['payment_status'] === 'paid' ? Carbon::now() : null,
        ]);

        if ($data['payment_status'] === 'paid') {
            app(\App\Services\NotificationService::class)
                ->paymentPaid($booking->booking_code, $booking->customer_phone, $booking->grand_total);
        }

        return new BookingResource($booking->load(['items', 'user']));
    }
}
