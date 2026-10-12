<?php

namespace Database\Factories;

use App\Enums\Semester;
use App\Enums\TermStatus;
use App\Models\AcademicTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicTerm>
 */
class AcademicTermFactory extends Factory
{
    /**
     * Penghitung agar tahun akademik unik antar factory instance
     * (menghindari pelanggaran unique constraint pada test).
     */
    private static int $sequence = 2015;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = self::$sequence++;
        $semester = fake()->randomElement([Semester::Ganjil, Semester::Genap]);

        $startDate = $semester === Semester::Ganjil
            ? sprintf('%d-09-01', $startYear)
            : sprintf('%d-02-01', $startYear + 1);

        $endDate = $semester === Semester::Ganjil
            ? sprintf('%d-01-31', $startYear + 1)
            : sprintf('%d-06-30', $startYear + 1);

        return [
            'academic_year' => sprintf('%d/%d', $startYear, $startYear + 1),
            'semester' => $semester->value,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => TermStatus::Active->value,
        ];
    }

    public function ganjil(int $startYear): static
    {
        return $this->state(fn () => [
            'academic_year' => sprintf('%d/%d', $startYear, $startYear + 1),
            'semester' => Semester::Ganjil->value,
            'start_date' => sprintf('%d-09-01', $startYear),
            'end_date' => sprintf('%d-01-31', $startYear + 1),
        ]);
    }

    public function genap(int $startYear): static
    {
        return $this->state(fn () => [
            'academic_year' => sprintf('%d/%d', $startYear, $startYear + 1),
            'semester' => Semester::Genap->value,
            'start_date' => sprintf('%d-02-01', $startYear + 1),
            'end_date' => sprintf('%d-06-30', $startYear + 1),
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn () => ['status' => TermStatus::Upcoming->value]);
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => TermStatus::Active->value]);
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => TermStatus::Closed->value]);
    }
}
