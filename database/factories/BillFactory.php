<?php

namespace Database\Factories;

use App\Enums\BillStatus;
use App\Enums\BillType;
use App\Models\AcademicTerm;
use App\Models\Bill;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bill>
 */
class BillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'academic_term_id' => AcademicTerm::factory(),
            'bill_number' => 'BILL-'.fake()->unique()->numerify('########'),
            'bill_type' => BillType::Ukt->value,
            'description' => 'Uang Kuliah Tunggal (UKT)',
            'amount' => fake()->randomElement([3_500_000, 5_000_000, 7_500_000]),
            'due_date' => fake()->dateTimeBetween('+1 week', '+2 months')->format('Y-m-d'),
            'status' => BillStatus::Unpaid->value,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => BillStatus::Paid->value]);
    }

    public function unpaid(): static
    {
        return $this->state(fn () => ['status' => BillStatus::Unpaid->value]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'due_date' => fake()->dateTimeBetween('-2 months', '-1 week')->format('Y-m-d'),
        ]);
    }

    public function type(BillType $type): static
    {
        return $this->state(fn () => ['bill_type' => $type->value]);
    }
}
