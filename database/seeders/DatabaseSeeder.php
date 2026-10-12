<?php

namespace Database\Seeders;

use App\Enums\BillType;
use App\Enums\TermStatus;
use App\Models\AcademicTerm;
use App\Models\AllowedEmailDomain;
use App\Models\Bill;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed data awal aplikasi.
     *
     * SELURUH data di sini adalah DUMMY untuk pengembangan:
     * nama, NIM, dan email dibuat oleh Faker dan BUKAN data
     * mahasiswa sungguhan. Untuk data resmi gunakan fitur import admin.
     */
    public function run(): void
    {
        // 1. Domain email default (contoh) — WAJIB diganti dengan domain resmi institusi.
        $this->seedAllowedDomains();

        // 2. Tahun akademik.
        $terms = $this->seedAcademicTerms();

        // Idempoten: lewati data dummy jika sudah pernah di-seed.
        if (Student::query()->exists() || Bill::query()->exists()) {
            return;
        }

        // 3. Data mahasiswa dummy tanpa akun terhubung (untuk demo import/verifikasi).
        $students = Student::factory()
            ->count(25)
            ->unlinked()
            ->create();

        // 4. Tagihan dummy untuk sebagian mahasiswa.
        $uktTerm = $terms->firstWhere('status', TermStatus::Active->value) ?? $terms->first();

        foreach ($students as $index => $student) {
            if ($index % 4 === 0) {
                continue; // sebagian mahasiswa sengaja tanpa tagihan (untuk empty state)
            }

            Bill::factory()
                ->for($student)
                ->for($uktTerm)
                ->create();

            if ($index % 3 === 0) {
                Bill::factory()
                    ->for($student)
                    ->for($uktTerm)
                    ->type(BillType::Other)
                    ->create([
                        'description' => 'Tagihan wisuda (dummy)',
                        'amount' => 750_000,
                    ]);
            }
        }
    }

    private function seedAllowedDomains(): void
    {
        foreach (config('portal.allowed_email_domains', []) as $domain) {
            AllowedEmailDomain::firstOrCreate(
                ['domain' => $domain],
                ['is_active' => true]
            );
        }
    }

    /**
     * @return Collection<int, AcademicTerm>
     */
    private function seedAcademicTerms(): Collection
    {
        $terms = collect();

        $definitions = [
            ['ganjil', 2023, TermStatus::Closed],
            ['genap', 2023, TermStatus::Closed],
            ['ganjil', 2024, TermStatus::Closed],
            ['genap', 2024, TermStatus::Active],
            ['ganjil', 2025, TermStatus::Upcoming],
        ];

        foreach ($definitions as [$semester, $year, $status]) {
            $startDate = $semester === 'ganjil'
                ? sprintf('%d-09-01', $year)
                : sprintf('%d-02-01', $year + 1);
            $endDate = $semester === 'ganjil'
                ? sprintf('%d-01-31', $year + 1)
                : sprintf('%d-06-30', $year + 1);

            $term = AcademicTerm::firstOrCreate(
                ['academic_year' => sprintf('%d/%d', $year, $year + 1), 'semester' => $semester],
                [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => $status->value,
                ]
            );

            $terms->push($term);
        }

        return $terms;
    }
}
