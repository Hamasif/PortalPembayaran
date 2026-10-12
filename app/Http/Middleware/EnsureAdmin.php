<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware otorisasi area admin — diperiksa di BACKEND, bukan hanya
 * menyembunyikan menu di frontend.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isAdmin() || $user->account_status === AccountStatus::Blocked) {
            abort(403, 'Anda tidak memiliki akses ke area admin.');
        }

        return $next($request);
    }
}
