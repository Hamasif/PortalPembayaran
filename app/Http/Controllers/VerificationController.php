<?php

namespace App\Http\Controllers;

use App\Services\StudentVerificationService;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function __construct(private readonly StudentVerificationService $verificationService) {}

    /**
     * Form Lengkapi Profil Mahasiswa (login pertama).
     */
    public function showCompleteForm(Request $request)
    {
        $user = $request->user();

        if ($user->student !== null) {
            return redirect()->route('student.dashboard');
        }

        return view('verification.complete-profile', [
            'user' => $user,
        ]);
    }

    /**
     * Proses pengiriman NIM & angkatan untuk dicocokkan dengan data induk.
     */
    public function submitProfile(Request $request)
    {
        $validated = $request->validate([
            'nim' => ['required', 'string', 'max:30'],
            'cohort_year' => ['required', 'integer', 'digits:4', 'min:1990', 'max:'.(date('Y') + 1)],
        ], [
            'nim.required' => 'NIM wajib diisi.',
            'cohort_year.digits' => 'Angkatan harus berupa 4 digit tahun (contoh: 2023).',
        ]);

        $result = $this->verificationService->linkProfile(
            $request->user(),
            $validated['nim'],
            (int) $validated['cohort_year']
        );

        if ($result['status'] === 'linked') {
            return redirect()
                ->route('student.dashboard')
                ->with('status_type', 'success')
                ->with('status_message', $result['message']);
        }

        return redirect()
            ->route('verification.status')
            ->with('status_type', 'warning')
            ->with('status_message', $result['message']);
    }

    /**
     * Halaman status verifikasi (menunggu pemeriksaan admin).
     */
    public function status(Request $request)
    {
        $user = $request->user();

        if ($user->student !== null && $user->isVerifiedStudent()) {
            return redirect()->route('student.dashboard');
        }

        return view('verification.status', [
            'user' => $user,
            'student' => $user->student,
        ]);
    }
}
