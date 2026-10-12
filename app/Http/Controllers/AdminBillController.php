<?php

namespace App\Http\Controllers;

use App\Enums\BillStatus;
use App\Enums\BillType;
use App\Enums\StudentStatus;
use App\Models\AcademicTerm;
use App\Models\Bill;
use App\Models\Student;
use App\Services\BillService;
use Illuminate\Http\Request;

class AdminBillController extends Controller
{
    public function __construct(private readonly BillService $billService) {}

    public function index(Request $request)
    {
        $bills = Bill::query()
            ->with(['student', 'academicTerm'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->q.'%';
                $query->where(fn ($q) => $q
                    ->where('bill_number', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhereHas('student', fn ($s) => $s
                        ->where('nim', 'like', $term)
                        ->orWhere('full_name', 'like', $term)));
            })
            ->when($request->filled('status') && in_array($request->status, BillStatus::values(), true),
                fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('term'), fn ($query) => $query->where('academic_term_id', (int) $request->term))
            ->when($request->filled('cohort'), fn ($query) => $query
                ->whereHas('student', fn ($s) => $s->where('cohort_year', (int) $request->cohort)))
            ->when($request->filled('prodi'), fn ($query) => $query
                ->whereHas('student', fn ($s) => $s->where('program_study', $request->prodi)))
            ->latest('due_date')
            ->paginate(15)
            ->withQueryString();

        $terms = AcademicTerm::orderByDesc('academic_year')->orderBy('semester')->get();
        $cohorts = Student::distinct()->orderByDesc('cohort_year')->pluck('cohort_year');
        $programs = Student::distinct()->orderBy('program_study')->pluck('program_study');

        $summary = [
            'total' => (int) Bill::whereNot('status', BillStatus::Cancelled->value)->sum('amount'),
            'unpaid' => (int) Bill::whereIn('status', [BillStatus::Unpaid->value, BillStatus::PartiallyPaid->value])->get()->sum(fn (Bill $b) => $b->remainingAmount()),
        ];

        return view('admin.bills.index', [
            'bills' => $bills,
            'terms' => $terms,
            'cohorts' => $cohorts,
            'programs' => $programs,
            'filters' => $request->only(['q', 'status', 'term', 'cohort', 'prodi']),
            'summary' => $summary,
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.bills.create', [
            'students' => Student::where('student_status', StudentStatus::Active->value)
                ->orderBy('nim')->get(['id', 'nim', 'full_name']),
            'terms' => AcademicTerm::orderByDesc('academic_year')->orderBy('semester')->get(),
            'types' => BillType::labels(),
            'prefillStudent' => $request->filled('student_id')
                ? Student::find((int) $request->student_id)
                : null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'academic_term_id' => ['required', 'integer', 'exists:academic_terms,id'],
            'bill_type' => ['required', 'in:ukt,other'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000000'],
            'due_date' => ['required', 'date', 'after:2000-01-01', 'before:2100-01-01'],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'amount.min' => 'Nominal tagihan harus lebih dari nol.',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $term = AcademicTerm::findOrFail($validated['academic_term_id']);

        try {
            $bill = $this->billService->create($student, $term, $validated, $request->user());
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->with('status_type', 'error')->with('status_message', $exception->getMessage());
        }

        return redirect()
            ->route('admin.bills.index')
            ->with('status_type', 'success')
            ->with('status_message', 'Tagihan '.$bill->bill_number.' berhasil dibuat.');
    }

    public function edit(Bill $bill)
    {
        abort_if($bill->status === BillStatus::Paid, 403, 'Tagihan yang sudah lunas tidak dapat diubah.');

        $bill->load(['student', 'academicTerm']);

        return view('admin.bills.edit', [
            'bill' => $bill,
        ]);
    }

    public function update(Request $request, Bill $bill)
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000000'],
            'due_date' => ['required', 'date', 'after:2000-01-01', 'before:2100-01-01'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->billService->update($bill, $validated, $request->user());
        } catch (\InvalidArgumentException $exception) {
            return back()->with('status_type', 'error')->with('status_message', $exception->getMessage());
        }

        return redirect()
            ->route('admin.bills.index')
            ->with('status_type', 'success')
            ->with('status_message', 'Tagihan '.$bill->bill_number.' diperbarui.');
    }

    public function cancel(Request $request, Bill $bill)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->billService->cancel($bill, $request->user(), $validated['reason']);
        } catch (\InvalidArgumentException $exception) {
            return back()->with('status_type', 'error')->with('status_message', $exception->getMessage());
        }

        return redirect()
            ->route('admin.bills.index')
            ->with('status_type', 'success')
            ->with('status_message', 'Tagihan '.$bill->bill_number.' dibatalkan.');
    }

    public function show(Bill $bill)
    {
        $bill->load(['student.user', 'academicTerm', 'payments']);

        return view('admin.bills.show', [
            'bill' => $bill,
        ]);
    }
}
