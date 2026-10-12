<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Bill;
use App\Models\Payment;
use App\Services\Payment\MockQrisGateway;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;

class StudentPaymentFlowController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * Buat transaksi QRIS untuk tagihan milik mahasiswa yang login.
     * Nominal diambil dari database, bukan dari input.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bill_id' => ['required', 'integer', 'exists:bills,id'],
        ]);

        $bill = Bill::findOrFail($validated['bill_id']);

        // Proteksi kepemilikan data di backend.
        abort_if($bill->student_id !== $request->user()->student->id, 404);

        try {
            $result = $this->paymentService->createOrReusePayment($bill, $request->user());
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            return back()->with('status_type', 'error')->with('status_message', $exception->getMessage());
        }

        return redirect()
            ->route('student.payments.show', $result['payment'])
            ->with('status_type', $result['created'] ? 'success' : 'info')
            ->with('status_message', $result['created']
                ? 'Transaksi QRIS dibuat. Selesaikan pembayaran sebelum kedaluwarsa.'
                : 'Anda memiliki transaksi yang masih menunggu pembayaran.');
    }

    /**
     * Halaman QRIS & status pembayaran.
     */
    public function show(Request $request, Payment $payment)
    {
        $payment->load(['bill.academicTerm', 'bill.student']);

        // Proteksi kepemilikan data.
        abort_if($payment->bill->student_id !== $request->user()->student->id, 404);

        // Sinkronkan status bila masih pending (webhook bisa terlambat).
        if ($payment->isPending()) {
            $this->paymentService->syncFromGateway($payment);
            $payment->refresh();
        }

        $qrPayload = null;
        if ($payment->isPending()) {
            $result = $this->gatewayManager->driver()->createTransaction($payment, route('webhook.payment'));
            $qrPayload = $result['qr_payload'];
        }

        // Nomor Virtual Account CONTOH (mode mock) — tidak terhubung ke bank sungguhan.
        // Pada provider resmi, nomor VA diambil dari respons gateway.
        $vaNumber = sprintf('8808%012d', $payment->id);
        $vaDisplay = str_replace(' ', '-', trim(wordwrap($vaNumber, 4, ' ', true)));

        return view('student.payments.show', [
            'payment' => $payment,
            'qrPayload' => $qrPayload,
            'isMock' => $this->gatewayManager->isMock() && app()->environment(['local', 'testing']),
            'vaNumber' => $vaNumber,
            'vaDisplay' => $vaDisplay,
        ]);
    }

    /**
     * SIMULASI pembayaran — hanya tersedia pada gateway mock di lingkungan
     * lokal/testing. Tidak tersedia di produksi.
     */
    public function simulate(Request $request, Payment $payment)
    {
        abort_unless($this->gatewayManager->isMock() && app()->environment(['local', 'testing']), 404);
        abort_if($payment->bill->student_id !== $request->user()->student->id, 404);

        if ($payment->status !== PaymentStatus::Pending) {
            return back()->with('status_type', 'info')
                ->with('status_message', 'Transaksi ini sudah final (status: '.$payment->status->label().').');
        }

        $payload = MockQrisGateway::buildMockWebhookPayload($payment);

        try {
            $this->paymentService->processStatusEvent($payment, [
                'status' => PaymentStatus::Success->value,
                'external_transaction_id' => $payload['transaction']['external_id'],
                'paid_amount' => $payload['transaction']['amount'],
                'currency' => $payload['transaction']['currency'],
                'event_id' => $payload['event_id'],
                'event_type' => $payload['event_type'],
                'metadata' => ['source' => 'mock_simulation'],
            ]);
        } catch (\RuntimeException $exception) {
            return back()->with('status_type', 'error')->with('status_message', $exception->getMessage());
        }

        return redirect()
            ->route('student.payments.show', $payment->refresh())
            ->with('status_type', 'success')
            ->with('status_message', 'Simulasi pembayaran berhasil — transaksi terverifikasi.');
    }
}
