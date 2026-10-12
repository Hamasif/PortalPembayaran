<?php

namespace App\Services\Payment;

use App\Models\Payment;

/**
 * Kontrak payment gateway QRIS.
 *
 * Implementasi untuk provider resmi HARUS mengikuti dokumentasi resmi
 * penyedia — jangan mengarang endpoint, signature, atau format payload.
 */
interface PaymentGatewayInterface
{
    /**
     * Buat transaksi baru di provider.
     *
     * @param  Payment  $payment  Payment pending dengan amount dari database.
     * @param  string  $callbackUrl  URL webhook provider.
     * @return array{provider_reference: string, qr_payload: ?string, qr_image_url: ?string, checkout_url: ?string, expires_at: ?\DateTimeInterface, external_transaction_id: ?string}
     */
    public function createTransaction(Payment $payment, string $callbackUrl): array;

    /**
     * Cek status transaksi ke provider (fallback saat webhook terlambat).
     *
     * @return array{status: string, external_transaction_id: ?string, paid_amount: ?int, currency: ?string}
     */
    public function getStatus(Payment $payment): array;

    /**
     * Nama provider (untuk log & metadata).
     */
    public function name(): string;
}
