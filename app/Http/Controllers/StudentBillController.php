<?php

namespace App\Http\Controllers;

use App\Enums\BillStatus;
use App\Models\Bill;
use Illuminate\Http\Request;

class StudentBillController extends Controller
{
    /**
     * Daftar tagihan milik mahasiswa yang sedang login.
     * Query selalu dibatasi pada student_id pemilik — mengubah parameter
     * URL tidak dapat menampilkan tagihan mahasiswa lain.
     */
    public function index(Request $request)
    {
        $student = $request->user()->student;

        $bills = $student->bills()
            ->with(['academicTerm', 'payments'])
            ->when($request->filled('status') && in_array($request->status, BillStatus::values(), true),
                fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('term'),
                fn ($query) => $query->where('academic_term_id', (int) $request->term))
            ->orderBy('due_date')
            ->paginate(10)
            ->withQueryString();

        $terms = $student->bills()->with('academicTerm')->get()
            ->pluck('academicTerm')->unique('id')->values();

        return view('student.bills.index', [
            'bills' => $bills,
            'terms' => $terms,
            'filters' => $request->only(['status', 'term']),
        ]);
    }

    /**
     * Detail tagihan — HANYA milik mahasiswa yang sedang login.
     */
    public function show(Request $request, Bill $bill)
    {
        $bill->load(['academicTerm', 'payments' => fn ($query) => $query->latest()]);

        // Proteksi kepemilikan data di backend.
        abort_if($bill->student_id !== $request->user()->student->id, 404);

        return view('student.bills.show', [
            'bill' => $bill,
            'payments' => $bill->payments,
        ]);
    }
}
