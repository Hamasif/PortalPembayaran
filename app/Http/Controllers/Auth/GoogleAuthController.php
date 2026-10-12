<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AllowedEmailDomain;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

class GoogleAuthController extends Controller
{
    /**
     * Arahkan pengguna ke halaman login Google.
     */
    public function redirect()
    {
        if (! $this->isConfigured()) {
            return $this->unconfiguredResponse();
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Tangani callback dari Google.
     *
     * Keamanan:
     * - OAuth state divalidasi otomatis oleh Socialite (CSRF flow).
     * - Email harus terverifikasi oleh Google.
     * - Domain email divalidasi di BACKEND (bukan hanya parameter frontend).
     * - Identitas disimpan sebagai google_id (stabil), bukan email.
     * - Session regeneration setelah login.
     */
    public function callback(Request $request)
    {
        if (! $this->isConfigured()) {
            return $this->unconfiguredResponse();
        }

        /** @var SocialiteUser $googleUser */
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('landing')
                ->with('status_type', 'error')
                ->with('status_message', 'Login Google gagal atau dibatalkan. Silakan coba lagi.');
        }

        // 1. Email wajib ada dan sudah diverifikasi oleh Google.
        $email = strtolower(trim((string) $googleUser->getEmail()));
        $googleEmailVerified = (bool) ($googleUser->user['email_verified'] ?? false);

        if ($email === '' || ! $googleEmailVerified) {
            return $this->reject('Email Google Anda belum terverifikasi. Gunakan akun Google yang telah memverifikasi email.');
        }

        // 2. Domain email divalidasi di backend.
        if (! AllowedEmailDomain::allowsEmail($email)) {
            AuditLog::record(null, 'auth.domain_rejected', 'user', null, [
                'domain' => substr(strrchr($email, '@') ?: '', 1),
            ]);

            return redirect()->route('access.denied');
        }

        // 3. Cari akun berdasarkan google_id (identitas stabil).
        $user = User::where('google_id', $googleUser->getId())->first();

        // 4. Akun pra-registrasi (mis. admin awal / mahasiswa): email cocok & google_id kosong.
        if ($user === null) {
            $user = User::where('email', $email)->whereNull('google_id')->first();

            if ($user !== null) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $this->persistAvatar($googleUser->getAvatar(), $googleUser->getId()),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);

                AuditLog::record($user->id, 'auth.google_linked', 'user', $user->id);
            }
        }

        // 5. Buat akun baru untuk mahasiswa.
        if ($user === null) {
            $user = User::create([
                'name' => $googleUser->getName() ?: $email,
                'email' => $email,
                'email_verified_at' => now(),
                'google_id' => $googleUser->getId(),
                'avatar' => $this->persistAvatar($googleUser->getAvatar(), $googleUser->getId()),
                'account_status' => AccountStatus::PendingVerification->value,
            ]);

            // Role hanya boleh diatur kode internal tepercaya (default DB: student).
            $user->forceFill(['role' => UserRole::Student->value])->save();

            AuditLog::record($user->id, 'auth.registered', 'user', $user->id, [
                'via' => 'google',
            ]);
        } else {
            // Sinkronkan foto profil & nama terbaru dari Google pada setiap login.
            $avatar = $this->persistAvatar($googleUser->getAvatar(), $googleUser->getId());

            $user->update([
                'name' => $googleUser->getName() ?: $user->name,
                'avatar' => $avatar ?? $user->avatar,
            ]);
        }

        // 6. Blokir akun yang dinonaktifkan.
        if ($user->account_status === AccountStatus::Blocked) {
            return $this->reject('Akun Anda diblokir. Silakan hubungi administrator.');
        }

        // 7. Login + session regeneration.
        Auth::login($user, remember: false);
        $request->session()->regenerate();

        AuditLog::record($user->id, 'auth.login', 'user', $user->id);

        return $this->redirectAfterLogin($user);
    }

    /**
     * Tujuan setelah login sesuai status akun.
     */
    private function redirectAfterLogin(User $user)
    {
        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        // Mahasiswa belum terhubung profil resmi.
        if ($user->student === null) {
            return redirect()->route('verification.complete.form');
        }

        if ($user->account_status === AccountStatus::PendingVerification) {
            return redirect()->route('verification.status');
        }

        return redirect()->intended(route('student.dashboard'));
    }

    /**
     * Unduh foto profil Google dan simpan salinan lokal (storage publik).
     *
     * URL avatar Google (lh3.googleusercontent.com) dapat kedaluwarsa atau
     * gagal dimuat saat di-hotlink; dengan menyimpan salinan lokal, foto
     * selalu tampil seperti foto profil Google yang terbaru.
     *
     * Fallback: jika unduhan gagal, kembalikan URL Google apa adanya.
     */
    private function persistAvatar(?string $googleAvatarUrl, string $googleId): ?string
    {
        if ($googleAvatarUrl === null || $googleAvatarUrl === '') {
            return null;
        }

        // Minta ukuran 256px dari Google (URL biasanya berakhiran =s96-c).
        $url = preg_replace('/=s\d+(-c)?$/', '=s256', $googleAvatarUrl) ?? $googleAvatarUrl;

        try {
            $response = Http::timeout(10)->get($url);

            if ($response->successful() && mb_strlen($response->body()) > 0) {
                $path = "avatars/{$googleId}.jpg";

                Storage::disk('public')->put($path, $response->body());

                return Storage::disk('public')->url($path);
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $googleAvatarUrl;
    }

    private function reject(string $message)
    {
        return redirect()
            ->route('landing')
            ->with('status_type', 'error')
            ->with('status_message', $message);
    }

    private function isConfigured(): bool
    {
        return (bool) (config('services.google.client_id') && config('services.google.client_secret'));
    }

    private function unconfiguredResponse()
    {
        return redirect()
            ->route('landing')
            ->with('status_type', 'error')
            ->with('status_message', 'Login Google belum dikonfigurasi. Silakan hubungi administrator sistem.');
    }
}
