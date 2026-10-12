<?php

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Support\Str;

/**
 * MOCK payment gateway QRIS — hanya untuk pengembangan & testing lokal.
 *
 * PENTING:
 * - Ini BUKAN pembayaran sungguhan.
 * - QR yang dihasilkan hanya payload string mock, tidak dapat dibayar.
 * - Transaksi dapat "dibayar" via tombol simulasi di halaman status.
 * - Tidak boleh digunakan di produksi (set PAYMENT_GATEWAY ke provider resmi).
 */
final class MockQrisGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'mock';
    }

    public function createTransaction(Payment $payment, string $callbackUrl): array
    {
        $expiresAt = now()->addMinutes((int) config('payment.mock.ttl_minutes', 15));

        // Payload QR tiruan yang jelas bukan QRIS resmi.
        $qrPayload = sprintf(
            'MOCKQRIS|ref=%s|amount=%d|merchant=%s|ts=%d',
            $payment->provider_reference,
            $payment->amount,
            config('payment.mock.merchant_id', 'MOCK-MERCHANT'),
            now()->timestamp
        );

        return [
            'provider_reference' => $payment->provider_reference,
            'qr_payload' => $qrPayload,
            'qr_image_url' => null,
            'checkout_url' => null,
            'expires_at' => $expiresAt,
            'external_transaction_id' => null,
        ];
    }

    public function getStatus(Payment $payment): array
    {
        // Mock tidak memiliki sistem eksternal: status selalu mengikuti database.
        return [
            'status' => $payment->status->value,
            'external_transaction_id' => $payment->external_transaction_id,
            'paid_amount' => $payment->isSuccess() ? $payment->amount : null,
            'currency' => $payment->currency,
        ];
    }

    /**
     * Bangun event webhook tiruan untuk simulasi pembayaran (dev/test).
     *
     * @return array<string, mixed>
     */
    public static function buildMockWebhookPayload(Payment $payment, string $status = 'success'): array
    {
        return [
            'event_type' => 'payment.'.$status,
            'event_id' => 'MOCKEVT-'.Str::uuid()->toString(),
            'transaction' => [
                'external_id' => $payment->external_transaction_id ?? ('MOCK-'.Str::uuid()->toString()),
                'provider_reference' => $payment->provider_reference,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => $status,
                'paid_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Tanda tangani payload mock (dipakai test & simulasi).
     *
     * @param  array<string, mixed>  $payload
     */
    public static function signPayload(array $payload, ?string $secret): string
    {
        return hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES), $secret ?? '');
    }
}
