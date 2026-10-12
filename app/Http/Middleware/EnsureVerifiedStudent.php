<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware untuk area mahasiswa: wajib akun student aktif yang sudah
 * terhubung dengan profil mahasiswa resmi.
 */
class EnsureVerifiedStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login.google');
        }

        if ($user->account_status === AccountStatus::Blocked) {
            auth()->logout();
            $request->session()->invalidate();

            return redirect()
                ->route('landing')
                ->with('status_type', 'error')
                ->with('status_message', 'Akun Anda diblokir. Silakan hubungi administrator.');
        }

        if (! $user->isVerifiedStudent()) {
            if ($user->student === null) {
                return redirect()->route('verification.complete.form');
            }

            return redirect()->route('verification.status');
        }

        return $next($request);
    }
}
