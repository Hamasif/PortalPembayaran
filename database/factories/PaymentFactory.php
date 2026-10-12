<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bill_id' => Bill::factory(),
            'user_id' => User::factory(),
            'external_transaction_id' => 'MOCK-'.Str::uuid()->toString(),
            'idempotency_key' => 'IDEM-'.Str::uuid()->toString(),
            'provider_reference' => 'REF-'.Str::uuid()->toString(),
            'amount' => fake()->randomElement([3_500_000, 5_000_000, 7_500_000]),
            'currency' => 'IDR',
            'payment_method' => 'qris',
            'status' => PaymentStatus::Pending->value,
            'paid_at' => null,
            'expires_at' => now()->addMinutes(15),
        ];
    }

    public function success(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Success->value,
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => PaymentStatus::Failed->value]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Expired->value,
            'expires_at' => now()->subMinutes(5),
        ]);
    }
}
