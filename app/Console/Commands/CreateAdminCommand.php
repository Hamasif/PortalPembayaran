<?php

namespace App\Console\Commands;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;

use function Laravel\Prompts\text;

class CreateAdminCommand extends Command
{
    /**
     * Usage:
     *   php artisan portal:create-admin --email=admin@universitas.ac.id --name="Nama Admin" --role=super_admin
     * Atau interaktif (tanpa opsi).
     *
     * Tidak ada password/kredensial default yang dibuat: admin login
     * dengan Google. Pada login pertama, akun Google dengan email yang
     * sama (terverifikasi & domain diizinkan) akan terhubung otomatis.
     */
    protected $signature = 'portal:create-admin
                            {--email= : Email Google resmi admin}
                            {--name= : Nama lengkap admin}
                            {--role=super_admin : super_admin | finance_admin}';

    protected $description = 'Mendaftarkan email resmi sebagai akun admin awal (login via Google, tanpa password default)';

    public function handle(): int
    {
        $email = strtolower(trim($this->option('email') ?: text(
            label: 'Email Google resmi admin',
            required: true,
        )));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Format email tidak valid.');

            return self::FAILURE;
        }

        $roleInput = strtolower(trim($this->option('role')));

        if (! in_array($roleInput, [UserRole::SuperAdmin->value, UserRole::FinanceAdmin->value], true)) {
            $this->error('Role harus super_admin atau finance_admin.');

            return self::FAILURE;
        }

        $role = UserRole::from($roleInput);
        $name = trim($this->option('name') ?: text(
            label: 'Nama lengkap admin',
            required: true,
        ));

        $existing = User::where('email', $email)->first();

        if ($existing !== null) {
            if ($existing->isAdmin()) {
                $this->warn("Email {$email} sudah terdaftar sebagai {$existing->role->label()}.");

                return self::SUCCESS;
            }

            $existing->forceFill([
                'role' => $role->value,
                'account_status' => AccountStatus::Active->value,
            ])->save();

            AuditLog::record($existing->id, 'admin.promoted', 'user', $existing->id, [
                'role' => $role->value,
            ]);

            $this->info("Email {$email} sudah terdaftar; role ditingkatkan menjadi {$role->label()}.");

            return self::SUCCESS;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => null, // terisi saat Google login pertama
            'google_id' => null,         // terhubung otomatis pada login pertama
            'account_status' => AccountStatus::Active->value,
        ]);

        // Role TIDAK mass-assignable demi keamanan — di-set eksplisit via forceFill
        // pada kode internal tepercaya (command artisan).
        $user->forceFill(['role' => $role->value])->save();

        AuditLog::record($user->id, 'admin.created', 'user', $user->id, [
            'role' => $role->value,
            'email' => $email,
        ]);

        $this->components->info("Akun admin {$role->label()} berhasil dibuat untuk {$email}.");
        $this->line('Login pertama harus menggunakan akun Google dengan email tersebut,');
        $this->line('kemudian identitas Google akan terhubung otomatis ke akun ini.');

        return self::SUCCESS;
    }
}
