<?php

namespace Tests\Feature\Payment;

use App\Enums\BillStatus;
use App\Enums\PaymentStatus;
use App\Models\AcademicTerm;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Student;
use App\Models\User;
use App\Services\Payment\MockQrisGateway;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $studentUser;

    private Student $student;

    private Bill $bill;

    protected function setUp(): void
    {
        parent::setUp();

        $this->studentUser = User::factory()
            ->student()
            ->has(Student::factory(['nim' => '20230001']), 'student')
            ->create();

        $this->student = $this->studentUser->student;
        $term = AcademicTerm::factory()->create();

        $this->bill = Bill::factory()
            ->for($this->student)
            ->for($term)
            ->create([
                'bill_number' => 'BILL-TEST-0001',
                'amount' => 5_000_000,
                'status' => BillStatus::Unpaid->value,
            ]);
    }

    public function test_mahasiswa_dapat_membuat_transaksi_pending(): void
    {
        $response = $this->actingAs($this->studentUser)
            ->post(route('student.payments.store'), ['bill_id' => $this->bill->id]);

        $response->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'bill_id' => $this->bill->id,
            'status' => PaymentStatus::Pending->value,
            'amount' => 5_000_000, // nominal dari database, bukan input
        ]);

        $this->bill->refresh();
        $this->assertEquals(BillStatus::Unpaid, $this->bill->status);
    }

    public function test_nominal_diambil_dari_database_bukan_input_frontend(): void
    {
        // Coba mengirim amount palsu — harus diabaikan.
        $this->actingAs($this->studentUser)->post(route('student.payments.store'), [
            'bill_id' => $this->bill->id,
            'amount' => 1000,
        ]);

        $this->assertDatabaseHas('payments', [
            'bill_id' => $this->bill->id,
            'amount' => 5_000_000,
        ]);
    }

    public function test_webhook_sukses_mengubah_status_pembayaran_dan_tagihan(): void
    {
        $payment = $this->createPendingPayment();
        $payload = MockQrisGateway::buildMockWebhookPayload($payment);

        $response = $this->postSignedWebhook($payload);

        $response->assertOk();
        $this->assertEquals(PaymentStatus::Success, $payment->refresh()->status);
        $this->assertEquals(BillStatus::Paid, $this->bill->refresh()->status);
        $this->assertNotNull($payment->refresh()->paid_at);
    }

    public function test_webhook_palsu_tanpa_signature_ditolak(): void
    {
        $payment = $this->createPendingPayment();
        $payload = MockQrisGateway::buildMockWebhookPayload($payment);

        $response = $this->postJson(route('webhook.payment'), $payload); // tanpa header

        $response->assertStatus(401);
        $this->assertEquals(PaymentStatus::Pending, $payment->refresh()->status);
    }

    public function test_webhook_dengan_signature_salah_ditolak(): void
    {
        $payment = $this->createPendingPayment();
        $payload = MockQrisGateway::buildMockWebhookPayload($payment);

        $response = $this->postSignedWebhook($payload, signature: str_repeat('a', 64));

        $response->assertStatus(401);
        $this->assertEquals(PaymentStatus::Pending, $payment->refresh()->status);
    }

    public function test_webhook_tanpa_secret_terkonfigurasi_ditolak(): void
    {
        config(['payment.mock.webhook_secret' => null]);

        $response = $this->post(route('webhook.payment'), ['transaction' => ['external_id' => 'x']]);

        $response->assertStatus(503);
    }

    public function test_webhook_duplikat_tidak_memproses_ulang(): void
    {
        $payment = $this->createPendingPayment();
        $payload = MockQrisGateway::buildMockWebhookPayload($payment);

        $this->postSignedWebhook($payload)->assertOk();
        $this->postSignedWebhook($payload)->assertOk(); // duplikat

        // Hanya satu event dengan provider_event_id yang sama.
        $this->assertEquals(
            1,
            PaymentEvent::where('provider_event_id', $payload['event_id'])->count()
        );
        $this->assertEquals(1, $payment->events()->count());
    }

    public function test_webhook_nominal_tidak_sesuai_ditolak(): void
    {
        $payment = $this->createPendingPayment();
        $payload = MockQrisGateway::buildMockWebhookPayload($payment);
        $payload['transaction']['amount'] = 999; // beda dari tagihan

        $response = $this->postSignedWebhook($payload);

        $response->assertStatus(422);
        $this->assertEquals(PaymentStatus::Pending, $payment->refresh()->status);
        // Event mismatch tercatat untuk ditelusuri.
        $this->assertDatabaseHas('payment_events', ['event_type' => 'amount_mismatch']);
    }

    public function test_webhook_mata_uang_tidak_sesuai_ditolak(): void
    {
        $payment = $this->createPendingPayment();
        $payload = MockQrisGateway::buildMockWebhookPayload($payment);
        $payload['transaction']['currency'] = 'USD';

        $response = $this->postSignedWebhook($payload);

        $response->assertStatus(422);
        $this->assertEquals(PaymentStatus::Pending, $payment->refresh()->status);
    }

    public function test_webhook_transaksi_tidak_dikenal_tercatat(): void
    {
        $payload = [
            'event_type' => 'payment.success',
            'event_id' => 'EVT-UNKNOWN-'.uniqid(),
            'transaction' => [
                'external_id' => 'TIDAK-ADA',
                'amount' => 1000,
                'currency' => 'IDR',
                'status' => 'success',
            ],
        ];

        $response = $this->postSignedWebhook($payload);

        $response->assertStatus(404);
        $this->assertDatabaseHas('payment_events', [
            'event_type' => 'unknown_transaction',
            'provider_event_id' => $payload['event_id'],
        ]);
    }

    public function test_transaksidapat_kedaluwarsa(): void
    {
        $payment = $this->createPendingPayment();
        $payment->update(['expires_at' => now()->subMinutes(1)]);

        app(PaymentService::class)->expireStalePayments();

        $this->assertEquals(PaymentStatus::Expired, $payment->refresh()->status);
        $this->assertEquals(BillStatus::Unpaid, $this->bill->refresh()->status);
    }

    public function test_status_final_tidak_bisa_diubah_ke_pending(): void
    {
        $payment = $this->createPendingPayment();
        app(PaymentService::class)->processStatusEvent($payment, [
            'status' => PaymentStatus::Failed->value,
            'event_id' => 'EVT-F-1',
        ]);

        app(PaymentService::class)->processStatusEvent($payment->refresh(), [
            'status' => PaymentStatus::Pending->value, // event basi
            'event_id' => 'EVT-F-2',
        ]);

        $this->assertEquals(PaymentStatus::Failed, $payment->refresh()->status);
    }

    public function test_idempotensi_satu_tagihan_satu_transaksi_pending(): void
    {
        $this->actingAs($this->studentUser)->post(route('student.payments.store'), ['bill_id' => $this->bill->id]);
        $this->actingAs($this->studentUser)->post(route('student.payments.store'), ['bill_id' => $this->bill->id]);

        $this->assertEquals(
            1,
            Payment::where('bill_id', $this->bill->id)->where('status', PaymentStatus::Pending->value)->count()
        );
    }

    public function test_simulasi_mock_melunasi_tagihan_di_lingkungan_testing(): void
    {
        $this->actingAs($this->studentUser)->post(route('student.payments.store'), ['bill_id' => $this->bill->id]);
        $payment = Payment::where('bill_id', $this->bill->id)->first();

        $response = $this->actingAs($this->studentUser)
            ->post(route('student.payments.simulate', $payment));

        $response->assertRedirect();
        $this->assertEquals(PaymentStatus::Success, $payment->refresh()->status);
        $this->assertEquals(BillStatus::Paid, $this->bill->refresh()->status);
    }

    public function test_sisa_tagihan_dihitung_dari_pembayaran_sukses(): void
    {
        $this->assertEquals(5_000_000, $this->bill->remainingAmount());

        $payment = $this->createPendingPayment();
        app(PaymentService::class)->processStatusEvent($payment, [
            'status' => PaymentStatus::Success->value,
            'event_id' => 'EVT-OK-1',
        ]);

        $this->bill->refresh();
        $this->assertEquals(0, $this->bill->remainingAmount());
        $this->assertEquals(5_000_000, $this->bill->paidAmount());
        $this->assertEquals(BillStatus::Paid, $this->bill->status);
    }

    private function createPendingPayment(): Payment
    {
        $result = app(PaymentService::class)->createOrReusePayment($this->bill, $this->studentUser);

        return $result['payment'];
    }

    /**
     * Kirim payload webhook sebagai raw JSON dengan signature HMAC
     * yang dihitung atas konten yang persis sama.
     *
     * @param  array<string, mixed>  $payload
     */
    private function postSignedWebhook(array $payload, ?string $signature = null): TestResponse
    {
        $content = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature ??= hash_hmac('sha256', $content, config('payment.mock.webhook_secret'));

        return $this->call(
            'POST',
            route('webhook.payment'),
            [],
            [],
            [],
            ['HTTP_X-Webhook-Signature' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $content
        );
    }
}
