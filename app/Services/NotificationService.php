<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Order;
use Illuminate\Support\Carbon;

class NotificationService
{
    public function __construct(private WhatsAppService $wa) {}

    private function rp($n): string
    {
        return 'Rp '.number_format((float) $n, 0, ',', '.');
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Menunggu',
            'confirmed' => 'Dikonfirmasi',
            'processing' => 'Diproses',
            'in_progress' => 'Sedang Dikerjakan',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => $status,
        };
    }

    public function bookingCreated(Booking $b): void
    {
        $when = Carbon::parse($b->scheduled_at)->translatedFormat('d M Y H:i');
        $this->wa->send(
            $b->customer_phone,
            "Halo {$b->customer_name}, booking servis *{$b->booking_code}* untuk {$b->vehicle_brand} {$b->vehicle_model} ({$b->vehicle_plate}) telah kami terima.\nJadwal: {$when}\nStatus: Menunggu konfirmasi.\nTerima kasih telah mempercayakan motormu pada kami! 🏍️"
        );
        $this->wa->notifyAdmin("🔔 Booking baru *{$b->booking_code}* dari {$b->customer_name} ({$b->customer_phone}) — {$b->vehicle_brand} {$b->vehicle_model}, jadwal {$when}.");
    }

    public function bookingStatusChanged(Booking $b): void
    {
        $this->wa->send(
            $b->customer_phone,
            "Update booking *{$b->booking_code}*: status berubah menjadi *{$this->statusLabel($b->status)}*.\nTotal: {$this->rp($b->grand_total)}"
        );
    }

    public function orderCreated(Order $o): void
    {
        $this->wa->send(
            $o->customer_phone,
            "Halo {$o->customer_name}, pesanan sparepart *{$o->order_code}* sebesar *{$this->rp($o->total)}* berhasil dibuat.\nStatus: Menunggu.\nTerima kasih! 🛒"
        );
        $this->wa->notifyAdmin("🔔 Pesanan baru *{$o->order_code}* dari {$o->customer_name} — {$this->rp($o->total)}.");
    }

    public function orderStatusChanged(Order $o): void
    {
        $this->wa->send(
            $o->customer_phone,
            "Update pesanan *{$o->order_code}*: status menjadi *{$this->statusLabel($o->status)}*."
        );
    }

    public function paymentPaid(string $code, string $customerPhone, $amount): void
    {
        $this->wa->send(
            $customerPhone,
            "✅ Pembayaran untuk *{$code}* sebesar *{$this->rp($amount)}* telah kami terima. Terima kasih!"
        );
    }
}
