<?php

namespace Database\Factories;

use App\Enums\StudentStatus;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = (int) fake()->numberBetween(2021, 2025);
        $sequence = fake()->unique()->numberBetween(1, 99999);

        return [
            // Data fiktif untuk kebutuhan pengembangan — BUKAN data mahasiswa sungguhan.
            'nim' => sprintf('%d%05d', $year, $sequence),
            'full_name' => fake()->name(),
            'program_study' => fake()->randomElement([
                'Teknik Informatika',
                'Sistem Informasi',
                'Manajemen',
                'Akuntansi',
                'Ilmu Komunikasi',
            ]),
            'cohort_year' => $year,
            'email' => null,
            'student_status' => StudentStatus::Active->value,
        ];
    }

    /**
     * Mahasiswa tanpa akun Google terhubung.
     */
    public function unlinked(): static
    {
        return $this->state(fn () => [
            'user_id' => null,
        ]);
    }

    /**
     * Mahasiswa tidak aktif.
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'student_status' => StudentStatus::Inactive->value,
        ]);
    }
}
