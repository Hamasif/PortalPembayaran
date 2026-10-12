<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureVerifiedStudent;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Webhook payment provider diverifikasi via HMAC signature (bukan CSRF).
        $middleware->validateCsrfTokens(except: ['webhooks/*']);

        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'super_admin' => EnsureSuperAdmin::class,
            'verified_student' => EnsureVerifiedStudent::class,
        ]);

        // Pengguna belum login -> diarahkan ke login Google.
        $middleware->redirectGuestsTo(fn () => route('login.google'));

        // Pengguna yang sudah login membuka halaman guest -> diarahkan sesuai role.
        $middleware->redirectUsersTo(function () {
            $user = auth()->user();

            if ($user === null) {
                return route('landing');
            }

            if ($user->isAdmin()) {
                return route('admin.dashboard');
            }

            return $user->student === null
                ? route('verification.complete.form')
                : route('student.dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
