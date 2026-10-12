<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;

/**
 * Seeder contoh file data mahasiswa (dummy) — memperlihatkan bentuk
 * data induk yang diharapkan oleh fitur import admin.
 * Data di sini DUMMY (Faker), bukan data mahasiswa sungguhan.
 */
class DummyStudentSeeder extends Seeder
{
    public function run(): void
    {
        Student::factory()->count(10)->unlinked()->create();
    }
}
