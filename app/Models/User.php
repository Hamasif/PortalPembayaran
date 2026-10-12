<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Atribut yang dapat diisi secara mass assignment.
     * CATATAN: `role` TIDAK boleh diisi dari input pengguna;
     * perubahan role hanya lewat perintah artisan / logika admin.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'google_id',
        'avatar',
        'account_status',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'account_status' => AccountStatus::class,
        ];
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function hasRole(UserRole|string $role): bool
    {
        if (is_string($role)) {
            $role = UserRole::tryFrom($role);
        }

        return $role !== null && $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function isAdmin(): bool
    {
        return $this->role !== null && $this->role->isAdmin();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(UserRole::SuperAdmin);
    }

    /**
     * Apakah akun sudah terhubung dengan profil mahasiswa terverifikasi.
     */
    public function isVerifiedStudent(): bool
    {
        return $this->hasRole(UserRole::Student)
            && $this->account_status === AccountStatus::Active
            && $this->student !== null;
    }
}
