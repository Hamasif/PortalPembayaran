<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Http\Request;

class StudentPaymentController extends Controller
{
    /**
     * Riwayat transaksi milik mahasiswa yang sedang login.
     */
    public function history(Request $request)
    {
        $student = $request->user()->student;

        $payments = Payment::query()
            ->whereHas('bill', fn ($query) => $query->where('student_id', $student->id))
            ->with(['bill.academicTerm'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('student.payments.history', [
            'payments' => $payments,
        ]);
    }

    /**
     * Bukti pembayaran (hanya transaksi sukses milik sendiri).
     */
    public function receipt(Request $request, Payment $payment)
    {
        $payment->load(['bill.academicTerm', 'bill.student']);

        // Proteksi kepemilikan data.
        abort_if($payment->bill->student_id !== $request->user()->student->id, 404);
        abort_if($payment->status !== PaymentStatus::Success, 404, 'Bukti pembayaran hanya tersedia untuk transaksi terverifikasi.');

        return view('student.payments.receipt', [
            'payment' => $payment,
        ]);
    }
}
