<?php

namespace Tests\Feature\Admin;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualStudentCreateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->financeAdmin()->create();
    }

    public function test_admin_dapat_melihat_form_tambah_mahasiswa(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.students.create'));

        $response->assertOk();
        $response->assertSee('Tambah Mahasiswa Manual');
    }

    public function test_admin_dapat_menambahkan_mahasiswa_manual(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'nim' => '20230123',
            'full_name' => 'Mahasiswa Manual Uji',
            'program_study' => 'Teknik Informatika',
            'cohort_year' => 2023,
            'email' => '20230123@student.universitas.ac.id',
            'student_status' => 'active',
        ]);

        $response->assertRedirect();

        $student = Student::where('nim', '20230123')->first();
        $this->assertNotNull($student);
        $this->assertEquals('Mahasiswa Manual Uji', $student->full_name);
        $this->assertEquals($this->admin->id, $student->created_by);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $this->admin->id,
            'action' => 'student.created',
            'entity_id' => $student->id,
        ]);
    }

    public function test_nim_duplikat_ditolak(): void
    {
        Student::factory()->create(['nim' => '20230123']);

        $response = $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'nim' => '20230123',
            'full_name' => 'Duplikat',
            'program_study' => 'Manajemen',
            'cohort_year' => 2023,
            'student_status' => 'active',
        ]);

        $response->assertSessionHasErrors('nim');
        $this->assertEquals(1, Student::where('nim', '20230123')->count());
    }

    public function test_angkatan_tidak_valid_ditolak(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'nim' => '20230124',
            'full_name' => 'Angkatan Salah',
            'program_study' => 'Manajemen',
            'cohort_year' => 18,
            'student_status' => 'active',
        ]);

        $response->assertSessionHasErrors('cohort_year');
    }

    public function test_mahasiswa_tidak_dapat_mengakses_form_tambah(): void
    {
        $student = User::factory()->student()->has(Student::factory(), 'student')->create();

        $this->actingAs($student)->get(route('admin.students.create'))->assertForbidden();
    }
}
