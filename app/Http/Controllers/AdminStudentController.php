<?php

namespace App\Http\Controllers;

use App\Enums\BillStatus;
use App\Enums\StudentStatus;
use App\Models\AuditLog;
use App\Models\Bill;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminStudentController extends Controller
{
    public function index(Request $request)
    {
        $students = Student::query()
            ->with(['user', 'bills'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->q.'%';
                $query->where(fn ($q) => $q
                    ->where('nim', 'like', $term)
                    ->orWhere('full_name', 'like', $term));
            })
            ->when($request->filled('status') && in_array($request->status, StudentStatus::values(), true),
                fn ($query) => $query->where('student_status', $request->status))
            ->when($request->filled('cohort'), fn ($query) => $query->where('cohort_year', (int) $request->cohort))
            ->when($request->filled('prodi'), fn ($query) => $query->where('program_study', $request->prodi))
            ->orderBy('nim')
            ->paginate(15)
            ->withQueryString();

        $cohorts = Student::distinct()->orderByDesc('cohort_year')->pluck('cohort_year');
        $programs = Student::distinct()->orderBy('program_study')->pluck('program_study');

        return view('admin.students.index', [
            'students' => $students,
            'cohorts' => $cohorts,
            'programs' => $programs,
            'filters' => $request->only(['q', 'status', 'cohort', 'prodi']),
        ]);
    }

    public function create()
    {
        return view('admin.students.create', [
            'statuses' => StudentStatus::labels(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nim' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9\.\-\/]{3,30}$/', 'unique:students,nim'],
            'full_name' => ['required', 'string', 'max:150'],
            'program_study' => ['required', 'string', 'max:150'],
            'cohort_year' => ['required', 'integer', 'digits:4', 'min:1990', 'max:'.(date('Y') + 1)],
            'email' => ['nullable', 'email', 'max:190', 'unique:students,email'],
            'student_status' => ['required', 'in:active,inactive,graduated'],
        ], [
            'nim.unique' => 'NIM sudah terdaftar pada data mahasiswa lain.',
            'nim.regex' => 'NIM hanya boleh huruf, angka, titik, dan garis (3–30 karakter).',
            'email.unique' => 'Email sudah dipakai data mahasiswa lain.',
        ]);

        $student = Student::create([
            'nim' => strtoupper(trim($validated['nim'])),
            'full_name' => trim($validated['full_name']),
            'program_study' => trim($validated['program_study']),
            'cohort_year' => (int) $validated['cohort_year'],
            'email' => $validated['email'] !== null ? strtolower(trim($validated['email'])) : null,
            'student_status' => $validated['student_status'],
        ]);

        // created_by diatur oleh kode internal tepercaya (bukan input publik).
        $student->forceFill(['created_by' => $request->user()->id])->save();

        AuditLog::record($request->user()->id, 'student.created', 'student', $student->id, [
            'nim' => $student->nim,
            'full_name' => $student->full_name,
            'source' => 'manual',
        ]);

        return redirect()
            ->route('admin.students.show', $student)
            ->with('status_type', 'success')
            ->with('status_message', 'Mahasiswa '.$student->full_name.' (NIM '.$student->nim.') berhasil ditambahkan.');
    }

    public function show(Student $student)
    {
        $student->load(['user', 'bills.academicTerm', 'bills.payments']);

        $totalBilled = $student->bills->whereNotIn('status', [BillStatus::Cancelled->value])->sum('amount');
        $totalPaid = $student->bills->sum(fn (Bill $bill) => $bill->paidAmount());

        return view('admin.students.show', [
            'student' => $student,
            'totalBilled' => $totalBilled,
            'totalPaid' => $totalPaid,
        ]);
    }

    public function edit(Student $student)
    {
        return view('admin.students.edit', [
            'student' => $student,
            'statuses' => StudentStatus::labels(),
        ]);
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'program_study' => ['required', 'string', 'max:150'],
            'student_status' => ['required', 'in:active,inactive,graduated'],
            'email' => ['nullable', 'email', 'max:190', Rule::unique('students')->ignore($student->id)],
        ]);

        $before = $student->only(['full_name', 'program_study', 'student_status', 'email']);

        $student->update($validated);

        AuditLog::record($request->user()->id, 'student.updated', 'student', $student->id, [
            'nim' => $student->nim,
            'before' => $before,
            'after' => $student->only(['full_name', 'program_study', 'student_status', 'email']),
        ]);

        return redirect()
            ->route('admin.students.show', $student)
            ->with('status_type', 'success')
            ->with('status_message', 'Data mahasiswa diperbarui.');
    }

    /**
     * Nonaktifkan/aktifkan mahasiswa (data tidak dihapus).
     */
    public function toggleStatus(Request $request, Student $student)
    {
        $newStatus = $student->student_status === StudentStatus::Active
            ? StudentStatus::Inactive
            : StudentStatus::Active;

        $student->update(['student_status' => $newStatus->value]);

        AuditLog::record($request->user()->id, 'student.status_changed', 'student', $student->id, [
            'nim' => $student->nim,
            'new_status' => $newStatus->value,
        ]);

        return back()
            ->with('status_type', 'success')
            ->with('status_message', 'Status mahasiswa diubah menjadi '.$newStatus->label().'.');
    }
}
