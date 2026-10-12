<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;

class AdminTransactionController extends Controller
{
    /**
     * Monitoring transaksi pembayaran.
     */
    public function index(Request $request)
    {
        $payments = Payment::query()
            ->with(['bill.student', 'bill.academicTerm', 'user'])
            ->when($request->filled('status') && in_array($request->status, PaymentStatus::values(), true),
                fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->q.'%';
                $query->where(fn ($q) => $q
                    ->where('external_transaction_id', 'like', $term)
                    ->orWhere('idempotency_key', 'like', $term)
                    ->orWhereHas('bill', fn ($b) => $b->where('bill_number', 'like', $term)
                        ->orWhereHas('student', fn ($s) => $s->where('nim', 'like', $term))));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'success_amount' => (int) Payment::where('status', PaymentStatus::Success->value)->sum('amount'),
            'success_count' => Payment::where('status', PaymentStatus::Success->value)->count(),
            'pending_count' => Payment::where('status', PaymentStatus::Pending->value)->count(),
            'failed_count' => Payment::where('status', PaymentStatus::Failed->value)->count(),
        ];

        return view('admin.transactions.index', [
            'payments' => $payments,
            'summary' => $summary,
            'filters' => $request->only(['q', 'status']),
        ]);
    }

    public function show(Payment $payment)
    {
        $payment->load(['bill.student', 'bill.academicTerm', 'user', 'events']);

        return view('admin.transactions.show', [
            'payment' => $payment,
        ]);
    }

    /**
     * Periksa status transaksi ke provider saat webhook terlambat.
     */
    public function checkStatus(Payment $payment)
    {
        try {
            app(PaymentService::class)->syncFromGateway($payment);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('status_type', 'error')
                ->with('status_message', 'Gagal memeriksa status ke provider. Coba lagi nanti.');
        }

        return back()->with('status_type', 'success')
            ->with('status_message', 'Status transaksi disinkronkan dengan provider: '.$payment->refresh()->status->label().'.');
    }
}
