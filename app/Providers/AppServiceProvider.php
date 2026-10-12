<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Payment service selalu memakai driver sesuai konfigurasi.
        $this->app->bind(PaymentGatewayInterface::class, function () {
            return app(PaymentGatewayManager::class)->driver();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Rate limiting untuk callback Google OAuth (proteksi brute force / abuse).
        RateLimiter::for('google-auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Rate limiting untuk endpoint pencarian / filter data sensitif.
        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limiting untuk pembuatan transaksi pembayaran.
        RateLimiter::for('payment', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Badge jumlah akun menunggu verifikasi di sidebar admin.
        View::composer('components.layouts.admin', function ($view) {
            $view->with('pendingVerificationCount', User::query()
                ->where('role', 'student')
                ->where('account_status', 'pending_verification')
                ->count());
        });
    }
}
