<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Throwable;

/**
 * Webhook payment provider.
 *
 * Keamanan:
 * - CSRF dikecualikan untuk jalur /webhooks/* (diverifikasi via signature).
 * - Signature HMAC-SHA256 atas RAW body dengan secret terpisah dari
 *   kredensial lain, dibandingkan dengan hash_equals (timing-safe).
 * - Event disimpan sebagai payment_events (provider_event_id unique)
 *   sehingga webhook duplikat tidak memproses ulang.
 * - Nominal, mata uang, dan referensi transaksi dicocokkan dengan database.
 */
class WebhookController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService) {}

    public function payment(Request $request)
    {
        $secret = config('payment.mock.webhook_secret');

        if (empty($secret)) {
            // Tanpa secret, webhook DITOLAK — jangan pernah memproses
            // pembayaran dari sumber yang tidak terverifikasi.
            return response()->json(['message' => 'Webhook secret belum dikonfigurasi.'], 503);
        }

        $rawBody = $request->getContent();
        $signature = (string) $request->header('X-Webhook-Signature', '');

        $expected = hash_hmac('sha256', $rawBody, $secret);

        if ($signature === '' || ! hash_equals($expected, $signature)) {
            // Jangan bocorkan detail — cukup tolak.
            return response()->json(['message' => 'Signature tidak valid.'], 401);
        }

        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            return response()->json(['message' => 'Payload tidak valid.'], 400);
        }

        $transaction = $payload['transaction'] ?? null;
        $externalId = $transaction['external_id'] ?? ($payload['external_id'] ?? null);

        if ($externalId === null) {
            return response()->json(['message' => 'Referensi transaksi tidak ditemukan.'], 400);
        }

        $payment = Payment::query()
            ->where('external_transaction_id', $externalId)
            ->first();

        if ($payment === null) {
            // Transaksi tak dikenal: catat event tanpa payment agar dapat ditelusuri.
            PaymentEvent::create([
                'payment_id' => null,
                'provider_event_id' => $payload['event_id'] ?? null,
                'event_type' => 'unknown_transaction',
                'payload_hash' => hash('sha256', $rawBody),
                'metadata' => ['received_at' => now()->toIso8601String()],
            ]);

            return response()->json(['message' => 'Transaksi tidak dikenal.'], 404);
        }

        try {
            $this->paymentService->processStatusEvent($payment, [
                'status' => $transaction['status'] ?? ($payload['status'] ?? ''),
                'external_transaction_id' => $externalId,
                'paid_amount' => $transaction['amount'] ?? null,
                'currency' => $transaction['currency'] ?? null,
                'event_id' => $payload['event_id'] ?? null,
                'event_type' => $payload['event_type'] ?? 'webhook',
                'metadata' => [
                    'source' => 'webhook',
                    'received_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            // 422: event valid tetapi gagal diproses — provider akan retry.
            return response()->json(['message' => 'Gagal memproses event.'], 422);
        }

        // Selalu 200 untuk event yang berhasil / duplikat (idempoten).
        return response()->json(['message' => 'OK']);
    }
}
