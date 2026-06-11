<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Service;
use App\Support\CodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::query()
            ->where('user_id', $request->user()->id)
            ->with('items')
            ->latest()
            ->paginate(10);

        return BookingResource::collection($bookings);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vehicle_brand' => ['required', 'string', 'max:100'],
            'vehicle_model' => ['required', 'string', 'max:100'],
            'vehicle_plate' => ['required', 'string', 'max:20'],
            'vehicle_year' => ['nullable', 'string', 'max:10'],
            'scheduled_at' => ['required', 'date', 'after_or_equal:today'],
            'complaint' => ['required', 'string', 'max:2000'],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer', 'exists:services,id'],
        ]);

        $user = $request->user();

        $booking = DB::transaction(function () use ($data, $user) {
            $booking = Booking::create([
                'booking_code' => CodeGenerator::booking(),
                'user_id' => $user->id,
                'customer_name' => $user->name,
                'customer_phone' => $user->phone ?? '-',
                'vehicle_brand' => $data['vehicle_brand'],
                'vehicle_model' => $data['vehicle_model'],
                'vehicle_plate' => strtoupper($data['vehicle_plate']),
                'vehicle_year' => $data['vehicle_year'] ?? null,
                'scheduled_at' => $data['scheduled_at'],
                'complaint' => $data['complaint'],
                'status' => 'pending',
                'payment_status' => 'unpaid',
            ]);

            $services = Service::whereIn('id', $data['service_ids'])->where('is_active', true)->get();

            foreach ($services as $service) {
                $booking->items()->create([
                    'item_type' => 'service',
                    'item_id' => $service->id,
                    'name' => $service->name,
                    'price' => $service->price,
                    'qty' => 1,
                    'subtotal' => $service->price,
                ]);
            }

            $booking->recalculateTotals();

            return $booking;
        });

        return new BookingResource($booking->load('items'));
    }

    public function show(Request $request, string $code)
    {
        $booking = Booking::query()
            ->where('booking_code', $code)
            ->where('user_id', $request->user()->id)
            ->with('items')
            ->firstOrFail();

        return new BookingResource($booking);
    }

    public function cancel(Request $request, string $code)
    {
        $booking = Booking::query()
            ->where('booking_code', $code)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($booking->status !== 'pending') {
            return response()->json([
                'message' => 'Booking hanya bisa dibatalkan saat masih menunggu konfirmasi.',
            ], 422);
        }

        $booking->update(['status' => 'cancelled']);

        return new BookingResource($booking->load('items'));
    }
}
