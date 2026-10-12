<?php

namespace Tests\Feature\Admin;

use App\Models\AllowedEmailDomain;
use App\Models\AuditLog;
use App\Models\Bill;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dapat_mengakses_dashboard_admin(): void
    {
        foreach ([User::factory()->superAdmin(), User::factory()->financeAdmin()] as $factory) {
            $admin = $factory->create();

            $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

            $admin->delete();
        }
    }

    public function test_mahasiswa_ditolak_dari_area_admin(): void
    {
        $student = User::factory()
            ->student()
            ->has(Student::factory(), 'student')
            ->create();

        $response = $this->actingAs($student)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_guest_dialihkan_ke_login(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login.google'));
    }

    public function test_finance_admin_tidak_dapat_mengakses_pengaturan_domain(): void
    {
        $financeAdmin = User::factory()->financeAdmin()->create();

        $response = $this->actingAs($financeAdmin)->get(route('admin.settings.domains'));

        $response->assertForbidden();
    }

    public function test_super_admin_dapat_mengelola_domain(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)->post(route('admin.settings.domains.store'), [
            'domain' => 'new.universitas.ac.id',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('allowed_email_domains', ['domain' => 'new.universitas.ac.id']);
        $this->assertTrue(AllowedEmailDomain::allowsEmail('x@new.universitas.ac.id'));
    }

    public function test_perubahan_mahasiswa_tercatat_di_audit_log(): void
    {
        $admin = User::factory()->financeAdmin()->create();
        $student = Student::factory()->create();

        $this->actingAs($admin)->put(route('admin.students.update', $student), [
            'full_name' => $student->full_name,
            'program_study' => $student->program_study,
            'student_status' => 'inactive',
            'email' => $student->email,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $admin->id,
            'action' => 'student.updated',
            'entity_type' => 'student',
            'entity_id' => $student->id,
        ]);
    }

    public function test_tagihan_lunas_tidak_dapat_diubah(): void
    {
        $admin = User::factory()->financeAdmin()->create();
        $student = Student::factory()->create();
        $bill = Bill::factory()->for($student)->paid()->create();

        $response = $this->actingAs($admin)->get(route('admin.bills.edit', $bill));

        $response->assertForbidden();
    }

    public function test_audit_log_tampil_hanya_untuk_admin(): void
    {
        $admin = User::factory()->superAdmin()->create();
        AuditLog::record($admin->id, 'test.action', 'test', null);

        $response = $this->actingAs($admin)->get(route('admin.audit-logs.index'));

        $response->assertOk();
        $response->assertSee('test.action');
    }
}
