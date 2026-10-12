<?php

namespace App\Services\Payment;

use InvalidArgumentException;

/**
 * Memilih driver payment gateway berdasarkan konfigurasi.
 * Driver 'mock' hanya untuk pengembangan lokal.
 */
final class PaymentGatewayManager
{
    public function driver(): PaymentGatewayInterface
    {
        $name = config('payment.default', 'mock');

        return match ($name) {
            'mock' => app(MockQrisGateway::class),
            // Provider resmi didaftarkan di sini setelah implementasi
            // adapter berdasarkan dokumentasi resminya, contoh:
            // 'midtrans' => app(MidtransQrisGateway::class),
            default => throw new InvalidArgumentException(
                "Payment gateway '{$name}' belum didukung. Konfigurasikan PAYMENT_GATEWAY=mock ".
                'atau implementasikan adapter provider resmi.'
            ),
        };
    }

    public function isMock(): bool
    {
        return config('payment.default', 'mock') === 'mock';
    }
}
