<?php

namespace App\Services\Payment;

use App\Enums\BillStatus;
use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Orkestrasi pembayaran dengan aturan keamanan:
 * - Nominal SELALU dari database (bill->remainingAmount), bukan input frontend.
 * - Idempotensi: satu tagihan hanya boleh punya satu transaksi pending.
 * - Semua perubahan status dalam database transaction.
 * - MVP TIDAK mendukung pembayaran parsial: nominal pembayaran = sisa tagihan.
 */
final class PaymentService
{
    public function __construct(private readonly PaymentGatewayInterface $gateway) {}

    /**
     * Buat (atau pakai kembali) transaksi pending untuk sebuah tagihan.
     *
     * @return array{payment: Payment, created: bool}
     */
    public function createOrReusePayment(Bill $bill, User $user): array
    {
        if (! $bill->isPayable()) {
            throw new \InvalidArgumentException('Tagihan ini tidak dapat dibayar.');
        }

        // Idempotensi: transaksi pending yang masih aktif dipakai ulang.
        $existing = $bill->payments()
            ->where('user_id', $user->id)
            ->where('status', PaymentStatus::Pending->value)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($existing !== null) {
            return ['payment' => $existing, 'created' => false];
        }

        $amount = $bill->remainingAmount();

        return DB::transaction(function () use ($bill, $user, $amount): array {
            $payment = Payment::create([
                'bill_id' => $bill->id,
                'user_id' => $user->id,
                'idempotency_key' => (string) Str::uuid(),
                'provider_reference' => 'PAY-'.strtoupper(Str::random(16)),
                'amount' => $amount,
                'currency' => config('portal.currency', 'IDR'),
                'payment_method' => 'qris',
                'status' => PaymentStatus::Pending->value,
                'expires_at' => now()->addMinutes((int) config('portal.qris_ttl_minutes', 15)),
            ]);

            $callbackUrl = route('webhook.payment');
            $gatewayResult = $this->gateway->createTransaction($payment, $callbackUrl);

            $payment->forceFill([
                'external_transaction_id' => $gatewayResult['external_transaction_id'] ?? $payment->idempotency_key,
                'expires_at' => $gatewayResult['expires_at'] ?? $payment->expires_at,
                'provider_reference' => $gatewayResult['provider_reference'],
            ])->save();

            AuditLog::record($user->id, 'payment.created', 'payment', $payment->id, [
                'bill_number' => $bill->bill_number,
                'amount' => $payment->amount,
                'gateway' => $this->gateway->name(),
            ]);

            return ['payment' => $payment->refresh(), 'created' => true];
        });
    }

    /**
     * Proses callback status dari provider (webhook / simulasi).
     * Aman dipanggil berulang (idempoten) — cocokkan nominal, mata uang,
     * dan referensi sebelum mengubah status.
     *
     * @param  array{status: string, external_transaction_id: ?string, paid_amount: ?int, currency: ?string, event_id?: ?string, event_type?: string, metadata?: array<string,mixed>}  $event
     */
    public function processStatusEvent(Payment $payment, array $event): void
    {
        // 1. Idempotensi: event dengan ID sama tidak diproses ulang.
        if (! empty($event['event_id'])) {
            $exists = PaymentEvent::where('provider_event_id', $event['event_id'])->exists();
            if ($exists) {
                return;
            }
        }

        // 2. Verifikasi nominal & mata uang (jika provider mengirim).
        if (isset($event['paid_amount']) && $event['paid_amount'] !== null) {
            if ((int) $event['paid_amount'] !== $payment->amount) {
                $this->recordEvent($payment, 'amount_mismatch', $event, processed: false);

                throw new \RuntimeException('Nominal pembayaran tidak sesuai tagihan.');
            }
        }

        if (isset($event['currency']) && $event['currency'] !== null
            && strtoupper($event['currency']) !== $payment->currency) {
            $this->recordEvent($payment, 'currency_mismatch', $event, processed: false);

            throw new \RuntimeException('Mata uang transaksi tidak sesuai.');
        }

        DB::transaction(function () use ($payment, $event): void {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            $newStatus = PaymentStatus::tryFrom($event['status'] ?? '');

            if ($newStatus === null) {
                throw new \RuntimeException('Status transaksi tidak dikenal.');
            }

            // Jangan menurunkan status dari yang sudah final.
            if (! $payment->isPending() && $newStatus === PaymentStatus::Pending) {
                $this->recordEvent($payment, 'ignored_stale_event', $event, processed: false);

                return;
            }

            // Transaksi yang sudah final (sukses/failed/expired) tidak diubah lagi
            // oleh event status yang lebih lama, kecuali duplikat sukses.
            if (! $payment->isPending() && $payment->status === $newStatus) {
                $this->recordEvent($payment, 'duplicate_status', $event, processed: true);

                return;
            }

            $payment->forceFill([
                'status' => $newStatus->value,
                'paid_at' => $newStatus === PaymentStatus::Success ? now() : $payment->paid_at,
            ])->save();

            if ($newStatus === PaymentStatus::Success) {
                $this->updateBillAfterSuccess($payment);
            }

            $this->recordEvent($payment, $event['event_type'] ?? 'payment.'.$newStatus->value, $event, processed: true);

            AuditLog::record(null, 'payment.'.$newStatus->value, 'payment', $payment->id, [
                'bill_number' => $payment->bill->bill_number,
                'amount' => $payment->amount,
                'source' => $event['metadata']['source'] ?? 'webhook',
            ]);
        });
    }

    /**
     * Sinkronkan status dari provider (fallback webhook terlambat).
     */
    public function syncFromGateway(Payment $payment): void
    {
        $status = $this->gateway->getStatus($payment);

        if ($payment->isPending() && $status['status'] !== PaymentStatus::Pending->value) {
            $this->processStatusEvent($payment, [
                'status' => $status['status'],
                'external_transaction_id' => $status['external_transaction_id'],
                'paid_amount' => $status['paid_amount'],
                'currency' => $status['currency'],
                'event_type' => 'status_check',
                'metadata' => ['source' => 'manual_sync'],
            ]);
        }

        // Kedaluwarsa berdasarkan waktu jika provider belum mengirim event.
        if ($payment->isExpiredByTime()) {
            $this->processStatusEvent($payment, [
                'status' => PaymentStatus::Expired->value,
                'event_type' => 'payment.expired',
                'metadata' => ['source' => 'ttl_check'],
            ]);
        }
    }

    /**
     * Kedaluwarsakan transaksi pending yang lewat masa berlaku (scheduler).
     */
    public function expireStalePayments(): int
    {
        $stale = Payment::query()
            ->where('status', PaymentStatus::Pending->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;

        foreach ($stale as $payment) {
            $this->processStatusEvent($payment, [
                'status' => PaymentStatus::Expired->value,
                'event_type' => 'payment.expired',
                'metadata' => ['source' => 'scheduler'],
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Perbarui status tagihan setelah pembayaran sukses.
     * Pembayaran sukses TIDAK PERNAH bisa dikembalikan menjadi unpaid
     * di sini — koreksi hanya via proses admin yang diaudit.
     */
    private function updateBillAfterSuccess(Payment $payment): void
    {
        $bill = $payment->bill()->lockForUpdate()->first();

        if ($bill === null || $bill->status === BillStatus::Paid) {
            return;
        }

        $paidTotal = $bill->payments()
            ->where('status', PaymentStatus::Success->value)
            ->sum('amount');

        if ($paidTotal >= $bill->amount) {
            $bill->forceFill(['status' => BillStatus::Paid->value])->save();
        } else {
            $bill->forceFill(['status' => BillStatus::PartiallyPaid->value])->save();
        }
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function recordEvent(Payment $payment, string $type, array $event, bool $processed): void
    {
        PaymentEvent::create([
            'payment_id' => $payment->id,
            'provider_event_id' => $event['event_id'] ?? null,
            'event_type' => $type,
            'payload_hash' => hash('sha256', json_encode($event['metadata'] ?? [], JSON_UNESCAPED_SLASHES)),
            'metadata' => $event['metadata'] ?? null, // hanya metadata aman
            'processed_at' => $processed ? now() : null,
        ]);
    }
}
