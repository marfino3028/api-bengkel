<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = (string) config('midtrans.server_key');
        Config::$isProduction = (bool) config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function enabled(): bool
    {
        return ! empty(config('midtrans.server_key'));
    }

    /**
     * Membuat transaksi Snap & mengembalikan token + redirect URL.
     *
     * @param  array{first_name?:string,email?:string,phone?:string}  $customer
     * @return array{token:string,redirect_url:string}
     */
    public function createSnap(string $orderId, int $amount, array $customer): array
    {
        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            'customer_details' => $customer,
        ];

        $token = Snap::getSnapToken($params);

        $base = config('midtrans.is_production')
            ? 'https://app.midtrans.com/snap/v2/vtweb/'
            : 'https://app.sandbox.midtrans.com/snap/v2/vtweb/';

        return ['token' => $token, 'redirect_url' => $base.$token];
    }

    /**
     * Verifikasi tanda tangan webhook Midtrans.
     */
    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.config('midtrans.server_key'));

        return hash_equals($expected, $signatureKey);
    }

    /**
     * Apakah status transaksi berarti sudah dibayar.
     */
    public function isPaid(string $transactionStatus, ?string $fraudStatus): bool
    {
        return in_array($transactionStatus, ['settlement', 'capture'], true)
            && ($fraudStatus === null || $fraudStatus === 'accept');
    }
}
