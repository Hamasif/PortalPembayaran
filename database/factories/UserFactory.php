<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'google_id' => (string) Str::uuid(),
            'avatar' => null,
            'role' => UserRole::Student->value,
            'account_status' => AccountStatus::Active->value,
            'password' => null, // autentikasi hanya via Google
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Akun mahasiswa.
     */
    public function student(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Student->value,
            'account_status' => AccountStatus::Active->value,
        ]);
    }

    /**
     * Akun super admin.
     */
    public function superAdmin(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::SuperAdmin->value,
            'account_status' => AccountStatus::Active->value,
        ]);
    }

    /**
     * Akun admin keuangan.
     */
    public function financeAdmin(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::FinanceAdmin->value,
            'account_status' => AccountStatus::Active->value,
        ]);
    }

    /**
     * Akun menunggu verifikasi (belum terhubung profil mahasiswa).
     */
    public function pendingVerification(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Student->value,
            'account_status' => AccountStatus::PendingVerification->value,
            'google_id' => null,
        ]);
    }

    /**
     * Email Google belum terverifikasi.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Akun diblokir.
     */
    public function blocked(): static
    {
        return $this->state(fn () => [
            'account_status' => AccountStatus::Blocked->value,
        ]);
    }
}
