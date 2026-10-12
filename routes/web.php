<?php

use App\Http\Controllers\AdminAuditLogController;
use App\Http\Controllers\AdminBillController;
use App\Http\Controllers\AdminBillImportController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AdminStudentController;
use App\Http\Controllers\AdminStudentImportController;
use App\Http\Controllers\AdminTransactionController;
use App\Http\Controllers\AdminVerificationController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\StudentBillController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentPaymentController;
use App\Http\Controllers\StudentPaymentFlowController;
use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman Publik
|--------------------------------------------------------------------------
*/

Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/akses-ditolak', fn () => view('access-denied'))->name('access.denied');

/*
|--------------------------------------------------------------------------
| Login Google
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login/google', [GoogleAuthController::class, 'redirect'])->name('login.google');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
        ->middleware('throttle:google-auth')
        ->name('login.google.callback');
});

/*
|--------------------------------------------------------------------------
| Logout (semua pengguna terautentikasi)
|--------------------------------------------------------------------------
*/

Route::post('/logout', [LogoutController::class, 'store'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Verifikasi & Lengkapi Profil Mahasiswa (login pertama)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/lengkapi-profil', [VerificationController::class, 'showCompleteForm'])
        ->name('verification.complete.form');
    Route::post('/lengkapi-profil', [VerificationController::class, 'submitProfile'])
        ->middleware('throttle:6,1')
        ->name('verification.complete.submit');
    Route::get('/status-verifikasi', [VerificationController::class, 'status'])
        ->name('verification.status');
});

/*
|--------------------------------------------------------------------------
| Area Mahasiswa (wajib profil terverifikasi)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified_student'])->prefix('mahasiswa')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('student.dashboard');

    Route::get('/tagihan', [StudentBillController::class, 'index'])->name('student.bills.index');
    Route::get('/tagihan/{bill}', [StudentBillController::class, 'show'])
        ->whereNumber('bill')
        ->name('student.bills.show');

    Route::post('/pembayaran', [StudentPaymentFlowController::class, 'store'])
        ->middleware('throttle:payment')
        ->name('student.payments.store');
    Route::get('/pembayaran/{payment}', [StudentPaymentFlowController::class, 'show'])
        ->whereNumber('payment')
        ->name('student.payments.show');
    Route::post('/pembayaran/{payment}/simulasi', [StudentPaymentFlowController::class, 'simulate'])
        ->whereNumber('payment')
        ->name('student.payments.simulate');

    Route::get('/riwayat', [StudentPaymentController::class, 'history'])->name('student.payments.history');
    Route::get('/riwayat/{payment}/bukti', [StudentPaymentController::class, 'receipt'])
        ->whereNumber('payment')
        ->name('student.payments.receipt');

    Route::get('/profil', [StudentProfileController::class, 'edit'])->name('student.profile.edit');
    Route::put('/profil', [StudentProfileController::class, 'update'])->name('student.profile.update');
});

/*
|--------------------------------------------------------------------------
| Area Admin (super_admin & finance_admin)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Data mahasiswa
    Route::get('/mahasiswa', [AdminStudentController::class, 'index'])
        ->middleware('throttle:search')
        ->name('students.index');
    Route::get('/mahasiswa/create', [AdminStudentController::class, 'create'])
        ->name('students.create');
    Route::post('/mahasiswa', [AdminStudentController::class, 'store'])
        ->name('students.store');
    Route::get('/mahasiswa/import', [AdminStudentImportController::class, 'index'])->name('students.import');
    Route::post('/mahasiswa/import/preview', [AdminStudentImportController::class, 'preview'])
        ->name('students.import.preview');
    Route::post('/mahasiswa/import/{batch}/commit', [AdminStudentImportController::class, 'commit'])
        ->whereNumber('batch')
        ->name('students.import.commit');
    Route::get('/mahasiswa/import/template', [AdminStudentImportController::class, 'template'])
        ->name('students.import.template');
    Route::get('/mahasiswa/import/{batch}/errors.csv', [AdminStudentImportController::class, 'errorsCsv'])
        ->whereNumber('batch')
        ->name('students.import.errors');
    Route::get('/mahasiswa/{student}', [AdminStudentController::class, 'show'])
        ->whereNumber('student')
        ->name('students.show');
    Route::get('/mahasiswa/{student}/edit', [AdminStudentController::class, 'edit'])
        ->whereNumber('student')
        ->name('students.edit');
    Route::put('/mahasiswa/{student}', [AdminStudentController::class, 'update'])
        ->whereNumber('student')
        ->name('students.update');
    Route::post('/mahasiswa/{student}/toggle-status', [AdminStudentController::class, 'toggleStatus'])
        ->whereNumber('student')
        ->name('students.toggle');

    // Verifikasi akun
    Route::get('/verifikasi', [AdminVerificationController::class, 'index'])->name('verifications.index');
    Route::post('/verifikasi/{user}/approve', [AdminVerificationController::class, 'approve'])
        ->whereNumber('user')
        ->name('verifications.approve');
    Route::post('/verifikasi/{user}/reject', [AdminVerificationController::class, 'reject'])
        ->whereNumber('user')
        ->name('verifications.reject');

    // Tagihan UKT
    Route::get('/tagihan', [AdminBillController::class, 'index'])
        ->middleware('throttle:search')
        ->name('bills.index');
    Route::get('/tagihan/create', [AdminBillController::class, 'create'])->name('bills.create');
    Route::post('/tagihan', [AdminBillController::class, 'store'])->name('bills.store');
    Route::get('/tagihan/import', [AdminBillImportController::class, 'index'])->name('bills.import');
    Route::post('/tagihan/import/preview', [AdminBillImportController::class, 'preview'])
        ->name('bills.import.preview');
    Route::post('/tagihan/import/{batch}/commit', [AdminBillImportController::class, 'commit'])
        ->whereNumber('batch')
        ->name('bills.import.commit');
    Route::get('/tagihan/import/template', [AdminBillImportController::class, 'template'])
        ->name('bills.import.template');
    Route::get('/tagihan/{bill}', [AdminBillController::class, 'show'])
        ->whereNumber('bill')
        ->name('bills.show');
    Route::get('/tagihan/{bill}/edit', [AdminBillController::class, 'edit'])
        ->whereNumber('bill')
        ->name('bills.edit');
    Route::put('/tagihan/{bill}', [AdminBillController::class, 'update'])
        ->whereNumber('bill')
        ->name('bills.update');
    Route::post('/tagihan/{bill}/cancel', [AdminBillController::class, 'cancel'])
        ->whereNumber('bill')
        ->name('bills.cancel');

    // Transaksi & laporan
    Route::get('/transaksi', [AdminTransactionController::class, 'index'])
        ->middleware('throttle:search')
        ->name('transactions.index');
    Route::get('/transaksi/{payment}', [AdminTransactionController::class, 'show'])
        ->whereNumber('payment')
        ->name('transactions.show');
    Route::post('/transaksi/{payment}/cek-status', [AdminTransactionController::class, 'checkStatus'])
        ->whereNumber('payment')
        ->name('transactions.check');
    Route::get('/laporan', [AdminReportController::class, 'index'])
        ->middleware('throttle:search')
        ->name('reports.index');
    Route::get('/laporan/ekspor', [AdminReportController::class, 'export'])->name('reports.export');

    // Audit log
    Route::get('/audit-log', [AdminAuditLogController::class, 'index'])
        ->middleware('throttle:search')
        ->name('audit-logs.index');

    // Pengaturan (khusus super_admin)
    Route::middleware('super_admin')->group(function () {
        Route::get('/pengaturan/domain', [AdminSettingsController::class, 'domains'])->name('settings.domains');
        Route::post('/pengaturan/domain', [AdminSettingsController::class, 'storeDomain'])->name('settings.domains.store');
        Route::post('/pengaturan/domain/{domain}/toggle', [AdminSettingsController::class, 'toggleDomain'])
            ->whereNumber('domain')
            ->name('settings.domains.toggle');
        Route::delete('/pengaturan/domain/{domain}', [AdminSettingsController::class, 'destroyDomain'])
            ->whereNumber('domain')
            ->name('settings.domains.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Webhook Payment Provider (tanpa CSRF — diverifikasi via signature)
|--------------------------------------------------------------------------
*/

Route::post('/webhooks/payment', [WebhookController::class, 'payment'])
    ->middleware('throttle:120,1')
    ->name('webhook.payment');
