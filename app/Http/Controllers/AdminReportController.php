<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\AcademicTerm;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Support\Csv;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    /**
     * Laporan transaksi + ekspor CSV.
     */
    public function index(Request $request)
    {
        $payments = Payment::query()
            ->where('status', PaymentStatus::Success->value)
            ->with(['bill.student', 'bill.academicTerm'])
            ->when($request->filled('from'), fn ($query) => $query->whereDate('paid_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('paid_at', '<=', $request->to))
            ->when($request->filled('term'), fn ($query) => $query
                ->whereHas('bill', fn ($b) => $b->where('academic_term_id', (int) $request->term)))
            ->orderByDesc('paid_at')
            ->paginate(20)
            ->withQueryString();

        $total = (int) Payment::query()
            ->where('status', PaymentStatus::Success->value)
            ->when($request->filled('from'), fn ($query) => $query->whereDate('paid_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('paid_at', '<=', $request->to))
            ->when($request->filled('term'), fn ($query) => $query
                ->whereHas('bill', fn ($b) => $b->where('academic_term_id', (int) $request->term)))
            ->sum('amount');

        $terms = AcademicTerm::orderByDesc('academic_year')->get();

        return view('admin.reports.index', [
            'payments' => $payments,
            'total' => $total,
            'terms' => $terms,
            'filters' => $request->only(['from', 'to', 'term']),
        ]);
    }

    /**
     * Ekspor laporan ke CSV dengan proteksi formula injection.
     */
    public function export(Request $request)
    {
        $payments = Payment::query()
            ->where('status', PaymentStatus::Success->value)
            ->with(['bill.student', 'bill.academicTerm'])
            ->when($request->filled('from'), fn ($query) => $query->whereDate('paid_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('paid_at', '<=', $request->to))
            ->orderBy('paid_at')
            ->limit(10000)
            ->get();

        AuditLog::record($request->user()->id, 'report.exported', 'payment', null, [
            'count' => $payments->count(),
            'from' => $request->from,
            'to' => $request->to,
        ]);

        return Csv::streamDownload(
            'laporan-transaksi-'.now()->format('Ymd-His').'.csv',
            ['nomor_tagihan', 'nim', 'nama', 'prodi', 'periode', 'nominal', 'metode', 'tanggal_bayar', 'id_transaksi_provider'],
            $payments->map(fn (Payment $payment) => [
                $payment->bill->bill_number,
                $payment->bill->student->nim,
                $payment->bill->student->full_name,
                $payment->bill->student->program_study,
                $payment->bill->academicTerm->label(),
                $payment->amount,
                $payment->payment_method,
                optional($payment->paid_at)->format('Y-m-d H:i:s'),
                $payment->external_transaction_id ?? '-',
            ])
        );
    }
}
