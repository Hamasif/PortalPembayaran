<?php

namespace App\Services;

use App\Enums\StudentStatus;
use App\Models\AuditLog;
use App\Models\Student;
use App\Models\StudentImportBatch;
use App\Models\User;
use App\Support\Templates;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Service import data mahasiswa resmi dari Excel/CSV.
 *
 * Alur: preview (validasi seluruh baris, tanpa menulis DB)
 *       -> import (DB transaction, hanya baris valid, laporan hasil).
 */
final class StudentImportService
{
    /**
     * Header yang wajib ada pada berkas.
     *
     * @var list<string>
     */
    private const REQUIRED_HEADERS = ['nim', 'nama_lengkap', 'program_studi', 'angkatan'];

    public function __construct(private readonly SpreadsheetReader $reader) {}

    /**
     * Unggah berkas, validasi seluruh baris, dan simpan preview.
     * Tidak ada baris yang ditulis ke tabel students.
     *
     * @return array{batch: StudentImportBatch, rows: list<array<string, mixed>>}
     */
    public function preview(UploadedFile $file, User $admin): array
    {
        $this->assertValidUpload($file);

        $storedPath = $file->store('imports/students');
        $parsed = $this->reader->read(
            Storage::path($storedPath),
            $file->getClientOriginalName(),
            self::REQUIRED_HEADERS
        );

        $this->assertRequiredHeaders($parsed['headers']);

        $validated = $this->validateRows($parsed['rows']);
        $successCount = count(array_filter($validated, fn (array $row) => $row['valid']));
        $failedCount = count($validated) - $successCount;

        $errors = [];
        foreach ($validated as $row) {
            if (! $row['valid']) {
                $errors[] = [
                    'row' => $row['row_number'],
                    'nim' => $row['data']['nim'] ?? '',
                    'errors' => $row['errors'],
                ];
            }
        }

        $batch = StudentImportBatch::create([
            'created_by' => $admin->id,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'total_rows' => count($validated),
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'status' => 'previewed',
            'errors' => $errors === [] ? null : array_slice($errors, 0, 100),
        ]);

        return ['batch' => $batch, 'rows' => $validated];
    }

    /**
     * Jalankan import untuk batch preview: proses ulang berkas tersimpan,
     * sisipkan baris yang valid di dalam database transaction.
     */
    public function import(StudentImportBatch $batch, User $admin): StudentImportBatch
    {
        if ($batch->status !== 'previewed') {
            throw new \LogicException('Batch ini sudah diproses sebelumnya.');
        }

        $parsed = $this->reader->read(Storage::path($batch->stored_path), $batch->original_filename, self::REQUIRED_HEADERS);
        $validated = $this->validateRows($parsed['rows']);

        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        DB::transaction(function () use ($validated, $admin, &$successCount, &$failedCount, &$errors): void {
            foreach ($validated as $row) {
                if (! $row['valid']) {
                    $failedCount++;
                    $errors[] = [
                        'row' => $row['row_number'],
                        'nim' => $row['data']['nim'] ?? '',
                        'errors' => $row['errors'],
                    ];

                    continue;
                }

                $data = $row['data'];

                // Update jika NIM sudah ada (data induk terbaru), insert jika baru.
                Student::updateOrCreate(
                    ['nim' => $data['nim']],
                    [
                        'full_name' => $data['nama_lengkap'],
                        'program_study' => $data['program_studi'],
                        'cohort_year' => $data['angkatan'],
                        'email' => $data['email'] ?? null,
                        'student_status' => $data['status'] ?? StudentStatus::Active->value,
                        'created_by' => $admin->id,
                    ]
                );

                $successCount++;
            }
        });

        $batch->update([
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'errors' => $errors === [] ? null : array_slice($errors, 0, 100),
            'status' => 'imported',
        ]);

        AuditLog::record($admin->id, 'students.imported', 'student_import_batch', $batch->id, [
            'filename' => $batch->original_filename,
            'total' => $batch->total_rows,
            'success' => $successCount,
            'failed' => $failedCount,
        ]);

        return $batch->refresh();
    }

    /**
     * Validasi seluruh baris (untuk preview & import).
     *
     * @param  list<array<string, string>>  $rows
     * @return list<array<string, mixed>>
     */
    private function validateRows(array $rows): array
    {
        $seenNims = [];
        $nimRegex = '/^[A-Za-z0-9\.\-\/]{3,30}$/';
        $currentYear = (int) date('Y');

        $validated = [];
        $rowNumber = 2; // baris 1 = header

        foreach ($rows as $row) {
            $data = [
                'nim' => strtoupper(trim($row['nim'] ?? '')),
                'nama_lengkap' => trim($row['nama_lengkap'] ?? ''),
                'program_studi' => trim($row['program_studi'] ?? ''),
                'angkatan' => trim($row['angkatan'] ?? ''),
                'email' => trim($row['email'] ?? '') !== '' ? strtolower(trim($row['email'])) : null,
                'status' => strtolower(trim($row['status'] ?? '')),
            ];

            $errors = [];

            if ($data['nim'] === '') {
                $errors[] = 'NIM wajib diisi.';
            } elseif (! preg_match($nimRegex, $data['nim'])) {
                $errors[] = 'Format NIM tidak valid (3–30 karakter alfanumerik).';
            } elseif (in_array($data['nim'], $seenNims, true)) {
                $errors[] = 'NIM duplikat di dalam berkas.';
            } elseif (Student::where('nim', $data['nim'])->exists()) {
                // Baris dengan NIM yang sudah ada akan diperbarui (bukan error).
            }

            $seenNims[] = $data['nim'];

            if ($data['nama_lengkap'] === '') {
                $errors[] = 'Nama lengkap wajib diisi.';
            } elseif (mb_strlen($data['nama_lengkap']) > 150) {
                $errors[] = 'Nama lengkap maksimal 150 karakter.';
            }

            if ($data['program_studi'] === '') {
                $errors[] = 'Program studi wajib diisi.';
            }

            $cohort = $data['angkatan'];
            if ($cohort === '' || ! ctype_digit($cohort)) {
                $errors[] = 'Angkatan wajib berupa angka tahun (contoh: 2023).';
            } elseif ((int) $cohort < 1990 || (int) $cohort > $currentYear + 1) {
                $errors[] = sprintf('Angkatan harus di antara 1990 dan %d.', $currentYear + 1);
            } else {
                $data['angkatan'] = (int) $cohort;
            }

            if ($data['email'] !== null && ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Format email tidak valid.';
            }

            if ($data['status'] !== '') {
                if (! array_key_exists($data['status'], StudentStatus::labels())) {
                    $errors[] = 'Status tidak dikenal (gunakan: active, inactive, atau graduated).';
                }
            } else {
                $data['status'] = StudentStatus::Active->value;
            }

            $validated[] = [
                'row_number' => $rowNumber,
                'data' => $data,
                'errors' => $errors,
                'valid' => $errors === [],
            ];

            $rowNumber++;
        }

        return $validated;
    }

    /**
     * @param  list<string>  $headers
     */
    private function assertRequiredHeaders(array $headers): void
    {
        $missing = array_diff(self::REQUIRED_HEADERS, $headers);

        if ($missing !== []) {
            throw new \InvalidArgumentException(
                'Kolom wajib tidak lengkap: '.implode(', ', $missing).'.'
            );
        }
    }

    private function assertValidUpload(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw new \InvalidArgumentException('Unggahan berkas tidak valid.');
        }

        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, config('portal.import_allowed_extensions', ['csv', 'xls', 'xlsx']), true)) {
            throw new \InvalidArgumentException('Format berkas harus CSV, XLS, atau XLSX.');
        }

        if ($file->getSize() > (int) config('portal.import_max_size_kb', 5120) * 1024) {
            throw new \InvalidArgumentException('Ukuran berkas melebihi batas maksimal.');
        }
    }

    /**
     * Templat import mahasiswa (Excel berformat rapi — lihat App\Support\Templates).
     */
    public static function templateResponse(): StreamedResponse
    {
        return Templates::studentTemplate();
    }
}
