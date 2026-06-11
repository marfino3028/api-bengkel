<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Http\Resources\OrderResource;
use App\Models\Booking;
use App\Models\Order;
use App\Services\MidtransService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private MidtransService $midtrans,
        private NotificationService $notifier,
    ) {}

    public function payOrder(Request $request, string $code)
    {
        $order = Order::where('order_code', $code)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return $this->createPayment(
            payable: $order,
            code: $order->order_code,
            amount: (int) round($order->total),
            user: $request->user(),
            resource: fn () => new OrderResource($order->load('items')),
        );
    }

    public function payBooking(Request $request, string $code)
    {
        $booking = Booking::where('booking_code', $code)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return $this->createPayment(
            payable: $booking,
            code: $booking->booking_code,
            amount: (int) round($booking->grand_total),
            user: $request->user(),
            resource: fn () => new BookingResource($booking->load('items')),
        );
    }

    private function createPayment($payable, string $code, int $amount, $user, \Closure $resource)
    {
        if (! $this->midtrans->enabled()) {
            return response()->json([
                'message' => 'Pembayaran online (Midtrans) belum dikonfigurasi. Gunakan pembayaran manual.',
            ], 422);
        }

        if ($payable->payment_status === 'paid') {
            return response()->json(['message' => 'Transaksi ini sudah lunas.'], 422);
        }

        if (in_array($payable->status, ['cancelled'], true)) {
            return response()->json(['message' => 'Transaksi sudah dibatalkan.'], 422);
        }

        if ($amount <= 0) {
            return response()->json(['message' => 'Nominal tidak valid.'], 422);
        }

        // order_id unik per percobaan bayar; disimpan untuk dicocokkan saat webhook.
        $reference = $code.'-'.Carbon::now()->timestamp;

        try {
            $snap = $this->midtrans->createSnap($reference, $amount, [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '',
            ]);
        } catch (\Throwable $e) {
            Log::error('Midtrans createSnap gagal: '.$e->getMessage());

            return response()->json(['message' => 'Gagal membuat transaksi pembayaran.'], 502);
        }

        $payable->update([
            'payment_gateway' => 'midtrans',
            'snap_token' => $snap['token'],
            'payment_reference' => $reference,
        ]);

        return response()->json([
            'snap_token' => $snap['token'],
            'redirect_url' => $snap['redirect_url'],
            'client_key' => config('midtrans.client_key'),
            'is_production' => (bool) config('midtrans.is_production'),
            'data' => $resource(),
        ]);
    }

    /**
     * Webhook notifikasi Midtrans (publik, diverifikasi via signature).
     */
    public function notification(Request $request)
    {
        $orderId = (string) $request->input('order_id');
        $statusCode = (string) $request->input('status_code');
        $grossAmount = (string) $request->input('gross_amount');
        $signature = (string) $request->input('signature_key');
        $transactionStatus = (string) $request->input('transaction_status');
        $fraudStatus = $request->input('fraud_status');

        if (! $this->midtrans->verifySignature($orderId, $statusCode, $grossAmount, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $order = Order::where('payment_reference', $orderId)->first();
        $booking = $order ? null : Booking::where('payment_reference', $orderId)->first();
        $payable = $order ?? $booking;

        if (! $payable) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }

        if ($this->midtrans->isPaid($transactionStatus, $fraudStatus)) {
            $payable->update([
                'payment_status' => 'paid',
                'payment_gateway' => 'midtrans',
                'paid_at' => Carbon::now(),
            ]);
            if ($order) {
                $order->update(['status' => $order->status === 'pending' ? 'processing' : $order->status]);
            }
            $this->notifier->paymentPaid(
                $order ? $payable->order_code : $payable->booking_code,
                $payable->customer_phone,
                $order ? $payable->total : $payable->grand_total,
            );
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'], true)) {
            // Biarkan unpaid; pelanggan bisa mencoba bayar lagi.
            $payable->update(['payment_status' => 'unpaid']);
        }

        return response()->json(['message' => 'ok']);
    }
}
