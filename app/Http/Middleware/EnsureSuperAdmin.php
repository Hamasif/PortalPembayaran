<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware khusus super admin (mis. pengaturan domain email).
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isSuperAdmin()) {
            abort(403, 'Fitur ini hanya dapat diakses oleh Super Admin.');
        }

        return $next($request);
    }
}
