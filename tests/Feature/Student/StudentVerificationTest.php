<?php

namespace Tests\Feature\Student;

use App\Enums\AccountStatus;
use App\Enums\StudentStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrasi_pertama_dengan_nim_cocok_menghubungkan_akun(): void
    {
        Student::factory()->create([
            'nim' => '20230001',
            'cohort_year' => 2023,
            'full_name' => 'Nama Resmi Dari Data Induk',
            'program_study' => 'Teknik Informatika',
        ]);

        $user = User::factory()->pendingVerification()->create([
            'email' => 'coba001@student.universitas.ac.id',
        ]);

        $response = $this->actingAs($user)->post(route('verification.complete.submit'), [
            'nim' => '20230001',
            'cohort_year' => 2023,
        ]);

        $response->assertRedirect(route('student.dashboard'));

        $user->refresh();
        $this->assertEquals(AccountStatus::Active, $user->account_status);
        $this->assertEquals('20230001', $user->student->nim);
        // Nama & prodi diambil dari data induk, bukan dari input mahasiswa.
        $this->assertEquals('Nama Resmi Dari Data Induk', $user->student->full_name);
    }

    public function test_nim_tidak_cocok_menghasilkan_status_menunggu_admin(): void
    {
        Student::factory()->create(['nim' => '20230001', 'cohort_year' => 2023]);

        $user = User::factory()->pendingVerification()->create();

        $response = $this->actingAs($user)->post(route('verification.complete.submit'), [
            'nim' => '29009999', // tidak ada di data induk
            'cohort_year' => 2023,
        ]);

        $response->assertRedirect(route('verification.status'));
        $user->refresh();
        $this->assertEquals(AccountStatus::PendingVerification, $user->account_status);
        $this->assertNull($user->student);
    }

    public function test_angkatan_tidak_cocok_menghasilkan_status_menunggu_admin(): void
    {
        Student::factory()->create(['nim' => '20230001', 'cohort_year' => 2023]);

        $user = User::factory()->pendingVerification()->create();

        $response = $this->actingAs($user)->post(route('verification.complete.submit'), [
            'nim' => '20230001',
            'cohort_year' => 2022, // salah
        ]);

        $response->assertRedirect(route('verification.status'));
        $user->refresh();
        $this->assertNull($user->student);
    }

    public function test_nim_yang_sudah_dipakai_akun_lain_tidak_bisa_ditautkan(): void
    {
        $firstUser = User::factory()->create();
        $student = Student::factory()->create([
            'nim' => '20230001',
            'cohort_year' => 2023,
            'user_id' => $firstUser->id, // sudah terhubung akun lain
        ]);

        $secondUser = User::factory()->pendingVerification()->create();

        $response = $this->actingAs($secondUser)->post(route('verification.complete.submit'), [
            'nim' => '20230001',
            'cohort_year' => 2023,
        ]);

        $response->assertRedirect(route('verification.status'));
        $secondUser->refresh();
        $this->assertEquals(AccountStatus::PendingVerification, $secondUser->account_status);
        // Student tetap milik akun pertama.
        $this->assertEquals($firstUser->id, $student->refresh()->user_id);
    }

    public function test_satu_akun_tidak_bisa_menghubungkan_dua_mahasiswa(): void
    {
        $user = User::factory()->create();
        Student::factory()->create(['nim' => '20230001', 'cohort_year' => 2023, 'user_id' => $user->id]);
        Student::factory()->create(['nim' => '20230002', 'cohort_year' => 2023]);

        // user->student sudah ada: layanan tidak menautkan mahasiswa kedua.
        $response = $this->actingAs($user)->post(route('verification.complete.submit'), [
            'nim' => '20230002',
            'cohort_year' => 2023,
        ]);

        $response->assertRedirect(route('student.dashboard'));
        $this->assertEquals('20230001', $user->refresh()->student->nim);
    }

    public function test_email_induk_yang_tidak_cocok_membutuhkan_persetujuan_admin(): void
    {
        Student::factory()->create([
            'nim' => '20230001',
            'cohort_year' => 2023,
            'email' => '20230001@student.universitas.ac.id',
        ]);

        // Mahasiswa memakai email domain yang sama tetapi alamat berbeda.
        $user = User::factory()->pendingVerification()->create([
            'email' => 'nama.lain@student.universitas.ac.id',
        ]);

        $response = $this->actingAs($user)->post(route('verification.complete.submit'), [
            'nim' => '20230001',
            'cohort_year' => 2023,
        ]);

        $response->assertRedirect(route('verification.status'));
        $user->refresh();
        $this->assertEquals(AccountStatus::PendingVerification, $user->account_status);
        $this->assertNull(Student::where('nim', '20230001')->first()->user_id);
    }

    public function test_mahasiswa_non_aktif_tidak_ditautkan_otomatis(): void
    {
        Student::factory()->create([
            'nim' => '20230001',
            'cohort_year' => 2023,
            'student_status' => StudentStatus::Inactive->value,
        ]);

        $user = User::factory()->pendingVerification()->create();

        $response = $this->actingAs($user)->post(route('verification.complete.submit'), [
            'nim' => '20230001',
            'cohort_year' => 2023,
        ]);

        $response->assertRedirect(route('verification.status'));
        $this->assertNull($user->refresh()->student);
    }
}
