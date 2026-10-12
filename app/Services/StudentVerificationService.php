<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\StudentStatus;
use App\Models\AuditLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Penghubungan akun Google dengan profil mahasiswa resmi (data induk).
 *
 * Aturan:
 * - NIM + angkatan wajib cocok dengan data induk hasil import admin.
 * - Satu NIM hanya boleh terhubung ke satu akun Google.
 * - Satu akun Google hanya boleh terhubung ke satu mahasiswa.
 * - Nama lengkap & program studi DIAMBIL dari data induk (tidak dipercaya
 *   dari input mahasiswa).
 * - Jika data induk memiliki email kampus dan tidak cocok dengan email
 *   Google pengguna, akun masuk antrean persetujuan admin.
 */
final class StudentVerificationService
{
    /**
     * @return array{status: 'linked'|'pending', message: string}
     */
    public function linkProfile(User $user, string $nim, int $cohortYear): array
    {
        if ($user->student !== null) {
            return [
                'status' => 'linked',
                'message' => 'Akun Anda sudah terhubung dengan profil mahasiswa.',
            ];
        }

        $student = Student::where('nim', strtoupper(trim($nim)))->first();

        // 1. NIM tidak ada di data induk.
        if ($student === null) {
            return $this->markPending($user, 'Data tidak cocok', 'nim_not_found');
        }

        // 2. NIM cocok tetapi angkatan tidak cocok.
        if ((int) $student->cohort_year !== $cohortYear) {
            return $this->markPending($user, 'Angkatan tidak cocok', 'cohort_mismatch', $student);
        }

        // 3. NIM sudah terhubung dengan akun Google lain.
        if ($student->user_id !== null && $student->user_id !== $user->id) {
            return $this->markPending($user, 'NIM sudah digunakan akun lain', 'nim_taken', $student);
        }

        // 4. Data induk memiliki email kampus yang tidak cocok -> persetujuan admin.
        if ($student->email !== null && strtolower($student->email) !== $user->email) {
            return $this->markPending($user, 'Email kampus pada data induk tidak cocok', 'email_mismatch', $student);
        }

        // 5. Mahasiswa tidak berstatus aktif.
        if ($student->student_status !== StudentStatus::Active) {
            return $this->markPending($user, 'Status mahasiswa tidak aktif', 'student_inactive', $student);
        }

        DB::transaction(function () use ($user, $student): void {
            // Kunci baris untuk mencegah race condition double-linking.
            $student = Student::whereKey($student->id)->lockForUpdate()->firstOrFail();

            if ($student->user_id !== null && $student->user_id !== $user->id) {
                throw new \RuntimeException('NIM sudah terhubung dengan akun lain.');
            }

            $student->forceFill(['user_id' => $user->id])->save();
            $user->forceFill(['account_status' => AccountStatus::Active->value])->save();
        });

        AuditLog::record($user->id, 'student.linked', 'student', $student->id, [
            'nim' => $student->nim,
        ]);

        return [
            'status' => 'linked',
            'message' => 'Profil mahasiswa berhasil terhubung. Selamat datang, '.$student->full_name.'!',
        ];
    }

    /**
     * Tandai akun menunggu pemeriksaan admin (akses tagihan tidak diberikan).
     *
     * @return array{status: 'pending', message: string}
     */
    private function markPending(User $user, string $reason, string $reasonCode, ?Student $student = null): array
    {
        $user->update(['account_status' => AccountStatus::PendingVerification->value]);

        AuditLog::record($user->id, 'verification.pending', 'user', $user->id, [
            'reason' => $reasonCode,
            'student_id' => $student?->id,
        ]);

        return [
            'status' => 'pending',
            'message' => 'Data Anda sedang menunggu pemeriksaan admin. Alasan: '.$reason.'. '
                .'Anda dapat menghubungi bagian akademik jika ada kekeliruan.',
        ];
    }
}
