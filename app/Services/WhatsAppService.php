<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function enabled(): bool
    {
        return ! empty(config('whatsapp.token'));
    }

    /**
     * Kirim pesan WhatsApp. Aman dipanggil meski belum dikonfigurasi (no-op).
     */
    public function send(?string $phone, string $message): void
    {
        if (! $this->enabled() || empty($phone)) {
            return;
        }

        $target = $this->normalize($phone);
        if ($target === '') {
            return;
        }

        try {
            Http::timeout(5)
                ->withHeaders(['Authorization' => (string) config('whatsapp.token')])
                ->asForm()
                ->post((string) config('whatsapp.api_url'), [
                    'target' => $target,
                    'message' => $message,
                ]);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp gagal terkirim: '.$e->getMessage());
        }
    }

    /**
     * Kirim juga ke nomor admin (bila diset).
     */
    public function notifyAdmin(string $message): void
    {
        $admin = config('whatsapp.admin_number');
        if (! empty($admin)) {
            $this->send($admin, $message);
        }
    }

    private function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }
        if (str_starts_with($digits, '62')) {
            return $digits;
        }
        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }
}
