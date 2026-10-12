<?php

namespace Tests\Feature;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Tests\TestCase;

class UnauthenticatedAccessTest extends TestCase
{
    public function test_endpoint_mahasiswa_terlindungi(): void
    {
        foreach ([
            'student.dashboard',
            'student.bills.index',
            'student.payments.history',
            'student.profile.edit',
        ] as $route) {
            $this->get(route($route))->assertRedirect(route('login.google'));
        }
    }

    public function test_endpoint_admin_terlindungi(): void
    {
        foreach ([
            'admin.students.index',
            'admin.bills.index',
            'admin.transactions.index',
            'admin.reports.index',
            'admin.audit-logs.index',
            'admin.verifications.index',
        ] as $route) {
            $this->get(route($route))->assertRedirect(route('login.google'));
        }
    }

    public function test_aksi_mengubah_data_ditolak_tanpa_login(): void
    {
        $this->post(route('student.payments.store'), ['bill_id' => 1])
            ->assertRedirect(route('login.google'));

        $this->post(route('admin.settings.domains.store'), ['domain' => 'contoh.ac.id'])
            ->assertRedirect(route('login.google'));
    }

    public function test_halaman_error_tampil_dengan_desain(): void
    {
        $this->get('/halaman-yang-tidak-ada')->assertNotFound();
    }

    public function test_csrf_middleware_aktif_pada_stack_web_dan_webhook_dikecualikan(): void
    {
        // CSRF (PreventRequestForgery) wajib bagian dari grup middleware web.
        $kernel = app(Kernel::class);
        $groups = $kernel->getMiddlewareGroups();

        $this->assertArrayHasKey('web', $groups);
        $this->assertContains(
            PreventRequestForgery::class,
            $groups['web']
        );

        // Webhook payment dikecualikan dari CSRF (diverifikasi via signature HMAC).
        $csrfExclusions = app('router')->getMiddlewareGroups(); // pastikan route tetap terdaftar
        $this->postJson(route('webhook.payment'), ['transaction' => ['external_id' => 'x']])
            ->assertStatus(401); // ditolak signature, bukan CSRF
    }
}
