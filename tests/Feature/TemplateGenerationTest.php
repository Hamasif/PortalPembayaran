<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\User;
use App\Services\SpreadsheetReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TemplateGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_templat_mahasiswa_dapat_dibaca_ulang(): void
    {
        $admin = User::factory()->financeAdmin()->create();
        $response = $this->actingAs($admin)->get(route('admin.students.import.template'));
        $response->assertOk();

        // Simpan konten unduhan, baca ulang dengan mesin import.
        $content = $response->streamedContent();
        Storage::put('test/template-students.xlsx', $content);

        $parsed = app(SpreadsheetReader::class)->read(
            Storage::path('test/template-students.xlsx'),
            'template-students.xlsx',
            ['nim', 'nama_lengkap', 'program_studi', 'angkatan']
        );

        $this->assertEquals(['nim', 'nama_lengkap', 'program_studi', 'angkatan', 'email', 'status'], $parsed['headers']);
        $this->assertNotEmpty($parsed['rows']);
        $this->assertEquals('20230001', $parsed['rows'][0]['nim']);
    }

    public function test_templat_tagihan_dapat_dibaca_ulang(): void
    {
        $admin = User::factory()->financeAdmin()->create();
        // Pastikan periode contoh ada agar validasi preview lolos penuh.
        AcademicTerm::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.bills.import.template'));
        $response->assertOk();

        $content = $response->streamedContent();
        Storage::put('test/template-bills.xlsx', $content);

        $parsed = app(SpreadsheetReader::class)->read(
            Storage::path('test/template-bills.xlsx'),
            'template-bills.xlsx',
            ['nim', 'tahun_akademik', 'semester', 'jenis', 'nominal', 'jatuh_tempo']
        );

        $this->assertEquals(['nim', 'tahun_akademik', 'semester', 'jenis', 'nominal', 'jatuh_tempo', 'keterangan'], $parsed['headers']);
        $this->assertEquals('20230001', $parsed['rows'][0]['nim']);
    }
}
