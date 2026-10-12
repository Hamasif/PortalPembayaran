<?php

namespace Tests\Feature\Auth;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\AllowedEmailDomain;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\FakesGoogleUser;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use FakesGoogleUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Domain contoh sesuai konfigurasi portal.
        AllowedEmailDomain::create(['domain' => 'student.universitas.ac.id']);
        AllowedEmailDomain::create(['domain' => 'universitas.ac.id']);
    }

    public function test_email_domain_resmi_dapat_login_dan_dibuatkan_akun(): void
    {
        $response = $this->callbackGoogleLogin([
            'email' => 'mahasiswa001@student.universitas.ac.id',
        ]);

        $response->assertRedirect(route('verification.complete.form'));
        $this->assertDatabaseHas('users', [
            'email' => 'mahasiswa001@student.universitas.ac.id',
            'role' => UserRole::Student->value,
            'account_status' => AccountStatus::PendingVerification->value,
        ]);
    }

    public function test_email_domain_tidak_diizinkan_ditolak_tanpa_membuat_akun(): void
    {
        $response = $this->callbackGoogleLogin([
            'email' => 'penyerang@gmail.com',
        ]);

        $response->assertRedirect(route('access.denied'));
        $this->assertDatabaseMissing('users', [
            'email' => 'penyerang@gmail.com',
        ]);
        $this->assertGuest();
    }

    public function test_email_google_belum_terverifikasi_ditolak(): void
    {
        $response = $this->callbackGoogleLogin([
            'email' => 'belum.terverifikasi@student.universitas.ac.id',
            'verified' => false,
        ]);

        $response->assertRedirect(route('landing'));
        $response->assertSessionHas('status_type', 'error');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'email' => 'belum.terverifikasi@student.universitas.ac.id',
        ]);
    }

    public function test_google_id_terhubung_pada_akun_pra_registrasi_admin(): void
    {
        // Admin dibuat lewat perintah artisan (tanpa google_id).
        $this->artisan('portal:create-admin', [
            '--email' => 'admin@universitas.ac.id',
            '--name' => 'Admin Awal',
            '--role' => 'super_admin',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        $response = $this->callbackGoogleLogin([
            'email' => 'admin@universitas.ac.id',
            'name' => 'Admin Awal',
        ]);

        $response->assertRedirect(route('admin.dashboard'));

        $user = User::where('email', 'admin@universitas.ac.id')->first();
        $this->assertNotNull($user->google_id);
        $this->assertTrue($user->isSuperAdmin());
        $this->assertAuthenticatedAs($user);
    }

    public function test_foto_profil_google_disimpan_lokal_saat_registrasi(): void
    {
        Http::fake([
            'lh3.googleusercontent.com/*' => Http::response('fake-jpeg-bytes', 200),
        ]);

        $response = $this->callbackGoogleLogin([
            'email' => 'foto@student.universitas.ac.id',
        ]);

        $response->assertRedirect(route('verification.complete.form'));

        $user = User::where('email', 'foto@student.universitas.ac.id')->first();
        $this->assertStringContainsString('/storage/avatars/', $user->avatar);
        $this->assertTrue(Storage::disk('public')->exists(
            'avatars/'.$user->google_id.'.jpg'
        ));
    }

    public function test_foto_profil_diperbarui_saat_login_ulang(): void
    {
        Http::fake([
            'lh3.googleusercontent.com/*' => Http::response('foto-baru', 200),
        ]);

        $user = User::factory()->create([
            'email' => 'lama@student.universitas.ac.id',
            'google_id' => 'google-lama-123',
            'avatar' => 'https://lh3.googleusercontent.com/a/lama=s96-c',
        ]);

        $this->callbackGoogleLogin([
            'email' => 'lama@student.universitas.ac.id',
            'id' => 'google-lama-123',
        ]);

        $user->refresh();
        $this->assertStringContainsString('/storage/avatars/', $user->avatar);
        $this->assertStringNotContainsString('lh3.googleusercontent.com', $user->avatar);
    }

    public function test_login_menghasilkan_session_baru(): void
    {
        $this->callbackGoogleLogin();

        $this->assertAuthenticated();
    }

    public function test_akun_menunggu_verifikasi_diarahkan_ke_halaman_lengkapi_profil(): void
    {
        $user = User::factory()->pendingVerification()->create();

        $response = $this->actingAs($user)->get(route('verification.complete.form'));

        $response->assertOk();
        $response->assertSee('Lengkapi Profil Mahasiswa');
    }

    public function test_pengguna_tidak_bisa_memilih_role_melalui_input(): void
    {
        // Endpoint lengkapi profil hanya menerima nim & cohort_year.
        $student = Student::factory()->create([
            'nim' => '20230099',
            'cohort_year' => 2023,
        ]);

        $response = $this->actingAs(
            User::factory()->pendingVerification()->create(['email' => 'm99@student.universitas.ac.id'])
        )->post(route('verification.complete.submit'), [
            'nim' => '20230099',
            'cohort_year' => 2023,
            'role' => 'super_admin', // coba injeksi role
            'account_status' => 'active',
        ]);

        $response->assertRedirect(route('student.dashboard'));
        $this->assertFalse(
            User::where('email', 'm99@student.universitas.ac.id')->first()->isSuperAdmin()
        );
    }
}
