<?php

namespace App\Http\Controllers;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminVerificationController extends Controller
{
    /**
     * Daftar akun mahasiswa menunggu verifikasi.
     */
    public function index(Request $request)
    {
        $pendingUsers = User::query()
            ->where('role', UserRole::Student->value)
            ->where('account_status', AccountStatus::PendingVerification->value)
            ->with('student')
            ->orderBy('updated_at')
            ->paginate(15);

        return view('admin.verifications.index', [
            'users' => $pendingUsers,
        ]);
    }

    /**
     * Setujui akun: hubungkan ke profil mahasiswa & aktifkan.
     * Admin memilih NIM mahasiswa resmi yang cocok.
     */
    public function approve(Request $request, User $user)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
        ]);

        $student = Student::findOrFail($validated['student_id']);

        if ($student->user_id !== null && $student->user_id !== $user->id) {
            return back()->with('status_type', 'error')
                ->with('status_message', 'Mahasiswa tersebut sudah terhubung dengan akun lain.');
        }

        if ($user->role !== UserRole::Student) {
            return back()->with('status_type', 'error')
                ->with('status_message', 'Hanya akun mahasiswa yang dapat disetujui.');
        }

        DB::transaction(function () use ($user, $student): void {
            $student->forceFill(['user_id' => $user->id])->save();
            $user->forceFill(['account_status' => AccountStatus::Active->value])->save();
        });

        AuditLog::record($request->user()->id, 'verification.approved', 'user', $user->id, [
            'student_id' => $student->id,
            'nim' => $student->nim,
        ]);

        return back()->with('status_type', 'success')
            ->with('status_message', 'Akun disetujui dan terhubung ke NIM '.$student->nim.'.');
    }

    /**
     * Tolak akun: blokir (tidak dapat login / mengakses data).
     */
    public function reject(Request $request, User $user)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $user->update(['account_status' => AccountStatus::Blocked->value]);

        AuditLog::record($request->user()->id, 'verification.rejected', 'user', $user->id, [
            'reason' => $validated['reason'],
        ]);

        return back()->with('status_type', 'success')
            ->with('status_message', 'Akun ditolak dan diblokir.');
    }
}
