<?php

namespace Tests\Feature\Import;

use App\Models\Student;
use App\Models\StudentImportBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->financeAdmin()->create();
    }

    private function csvContent(array $rows, array $header = ['nim', 'nama_lengkap', 'program_studi', 'angkatan', 'email', 'status']): string
    {
        $lines = [implode(';', $header)];

        foreach ($rows as $row) {
            $lines[] = implode(';', $row);
        }

        return implode("\n", $lines);
    }

    public function test_import_data_valid_ditampilkan_di_preview(): void
    {
        $content = $this->csvContent([
            ['20230001', 'Contoh Valid Satu', 'Teknik Informatika', '2023', '', 'active'],
            ['20230002', 'Contoh Valid Dua', 'Manajemen', '2023', '', 'active'],
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.students.import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('mahasiswa.csv', $content),
        ]);

        $response->assertOk();
        $response->assertSee('20230001');
        $response->assertSee('Contoh Valid Satu');
    }

    public function test_commit_import_menyimpan_baris_valid(): void
    {
        $content = $this->csvContent([
            ['20230001', 'Contoh Valid Satu', 'Teknik Informatika', '2023', '', 'active'],
        ]);

        $this->actingAs($this->admin)->post(route('admin.students.import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('mahasiswa.csv', $content),
        ]);

        $batch = StudentImportBatch::latest('id')->first();

        $response = $this->actingAs($this->admin)->post(route('admin.students.import.commit', $batch));

        $response->assertRedirect();
        $this->assertDatabaseHas('students', ['nim' => '20230001', 'full_name' => 'Contoh Valid Satu']);
    }

    public function test_import_baris_dengan_nim_kosong_dilaporkan_gagal(): void
    {
        $content = $this->csvContent([
            ['', 'Tanpa NIM', 'Akuntansi', '2023', '', 'active'],
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.students.import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('mahasiswa.csv', $content),
        ]);

        $response->assertOk();
        $response->assertSee('NIM wajib diisi');
    }

    public function test_import_nim_duplikat_dalam_berkas_dilaporkan(): void
    {
        $content = $this->csvContent([
            ['20230001', 'Pertama', 'Manajemen', '2023', '', 'active'],
            ['20230001', 'Duplikat', 'Manajemen', '2023', '', 'active'],
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.students.import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('mahasiswa.csv', $content),
        ]);

        $response->assertOk();
        $response->assertSee('NIM duplikat');
    }

    public function test_import_angkatan_invalid_dilaporkan(): void
    {
        $content = $this->csvContent([
            ['20230001', 'Angkatan Salah', 'Manajemen', '18ab', '', 'active'],
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.students.import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('mahasiswa.csv', $content),
        ]);

        $response->assertOk();
        $response->assertSee('Angkatan wajib berupa angka');
    }

    public function test_import_header_tidak_lengkap_ditolak(): void
    {
        $content = $this->csvContent([
            ['20230001', 'Tanpa Kolom Wajib', 'Manajemen'],
        ], ['nim', 'nama_lengkap', 'prodi_x']);

        $response = $this->actingAs($this->admin)->post(route('admin.students.import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('mahasiswa.csv', $content),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status_type', 'error');
    }

    public function test_nim_yang_sudah_ada_diperbarui_bukan_diduplikasi(): void
    {
        Student::factory()->create(['nim' => '20230001', 'full_name' => 'Nama Lama']);

        $content = $this->csvContent([
            ['20230001', 'Nama Baru Hasil Import', 'Teknik Informatika', '2023', '', 'active'],
        ]);

        $this->actingAs($this->admin)->post(route('admin.students.import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('mahasiswa.csv', $content),
        ]);
        $batch = StudentImportBatch::latest('id')->first();
        $this->actingAs($this->admin)->post(route('admin.students.import.commit', $batch));

        $this->assertEquals(1, Student::where('nim', '20230001')->count());
        $this->assertEquals('Nama Baru Hasil Import', Student::where('nim', '20230001')->first()->full_name);
    }

    public function test_commit_batch_dua_kali_ditolak(): void
    {
        $content = $this->csvContent([
            ['20230001', 'Sekali Saja', 'Manajemen', '2023', '', 'active'],
        ]);

        $this->actingAs($this->admin)->post(route('admin.students.import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('mahasiswa.csv', $content),
        ]);
        $batch = StudentImportBatch::latest('id')->first();

        $this->actingAs($this->admin)->post(route('admin.students.import.commit', $batch))->assertRedirect();

        $response = $this->actingAs($this->admin)->post(route('admin.students.import.commit', $batch));
        $response->assertRedirect();
        $response->assertSessionHas('status_type', 'error');

        $this->assertEquals(1, Student::where('nim', '20230001')->count());
    }
}
