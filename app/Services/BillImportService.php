<?php

namespace App\Services;

use App\Enums\BillStatus;
use App\Enums\BillType;
use App\Models\AcademicTerm;
use App\Models\AuditLog;
use App\Models\Bill;
use App\Models\Student;
use App\Models\StudentImportBatch;
use App\Models\User;
use App\Support\Templates;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Import tagihan UKT massal dari CSV/Excel.
 *
 * Kolom berkas: nim, tahun_akademik (2024/2025), semester (ganjil|genap),
 * jenis (ukt|lainnya), nominal (angka rupiah), jatuh_tempo (YYYY-MM-DD),
 * keterangan (opsional).
 *
 * Aturan:
 * - Seluruh baris divalidasi pada tahap preview.
 * - Duplikasi tagihan pada periode yang sama = baris gagal (tidak diubah).
 * - Tagihan yang sudah lunas tidak pernah disentuh.
 * - Insert dilakukan dalam database transaction.
 */
final class BillImportService
{
    private const REQUIRED_HEADERS = ['nim', 'tahun_akademik', 'semester', 'jenis', 'nominal', 'jatuh_tempo'];

    public function __construct(private readonly SpreadsheetReader $reader) {}

    /**
     * @return array{batch: StudentImportBatch, rows: list<array<string, mixed>>}
     */
    public function preview(UploadedFile $file, User $admin): array
    {
        $storedPath = $file->store('imports/bills');
        $parsed = $this->reader->read(Storage::path($storedPath), $file->getClientOriginalName(), self::REQUIRED_HEADERS);

        $missing = array_diff(self::REQUIRED_HEADERS, $parsed['headers']);
        if ($missing !== []) {
            throw new \InvalidArgumentException('Kolom wajib tidak lengkap: '.implode(', ', $missing).'.');
        }

        $validated = $this->validateRows($parsed['rows']);
        $successCount = count(array_filter($validated, fn (array $row) => $row['valid']));

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
            'failed_count' => count($validated) - $successCount,
            'status' => 'previewed',
            'errors' => $errors === [] ? null : array_slice($errors, 0, 100),
        ]);

        return ['batch' => $batch, 'rows' => $validated];
    }

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
        $createdIds = [];

        DB::transaction(function () use ($validated, $admin, &$successCount, &$failedCount, &$errors, &$createdIds): void {
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
                $student = Student::where('nim', $data['nim'])->first();
                $term = AcademicTerm::where('academic_year', $data['tahun_akademik'])
                    ->where('semester', $data['semester'])
                    ->first();

                $bill = Bill::create([
                    'student_id' => $student->id,
                    'academic_term_id' => $term->id,
                    'bill_number' => BillNumberService::generate($term, $student, BillType::from($data['jenis'])),
                    'bill_type' => $data['jenis'],
                    'description' => $data['keterangan'] ?: 'Tagihan UKT (import massal)',
                    'amount' => $data['nominal'],
                    'due_date' => $data['jatuh_tempo'],
                    'status' => BillStatus::Unpaid->value,
                    'created_by' => $admin->id,
                ]);

                $bill->forceFill(['status' => BillStatus::Unpaid->value, 'created_by' => $admin->id])->save();

                $createdIds[] = $bill->id;
                $successCount++;
            }
        });

        $batch->update([
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'errors' => $errors === [] ? null : array_slice($errors, 0, 100),
            'status' => 'imported',
        ]);

        AuditLog::record($admin->id, 'bills.imported', 'student_import_batch', $batch->id, [
            'filename' => $batch->original_filename,
            'total' => $batch->total_rows,
            'success' => $successCount,
            'failed' => $failedCount,
            'bill_ids' => array_slice($createdIds, 0, 50),
        ]);

        return $batch->refresh();
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @return list<array<string, mixed>>
     */
    private function validateRows(array $rows): array
    {
        // Cache data induk untuk performa & validasi konsisten.
        $students = Student::pluck('id', 'nim');
        $terms = AcademicTerm::all()->keyBy(
            fn (AcademicTerm $term) => $term->academic_year.'|'.$term->semester->value
        );

        $seenKeys = [];
        $validated = [];
        $rowNumber = 2;

        foreach ($rows as $row) {
            $data = [
                'nim' => strtoupper(trim($row['nim'] ?? '')),
                'tahun_akademik' => str_replace(' ', '', trim($row['tahun_akademik'] ?? '')),
                'semester' => strtolower(trim($row['semester'] ?? '')),
                'jenis' => strtolower(trim($row['jenis'] ?? '')),
                'nominal' => trim($row['nominal'] ?? ''),
                'jatuh_tempo' => trim($row['jatuh_tempo'] ?? ''),
                'keterangan' => trim($row['keterangan'] ?? '') !== '' ? trim($row['keterangan']) : null,
            ];

            $errors = [];

            if (! $students->has($data['nim'])) {
                $errors[] = 'NIM tidak ditemukan pada data mahasiswa.';
            }

            if (! $terms->has($data['tahun_akademik'].'|'.$data['semester'])) {
                $errors[] = 'Kombinasi tahun akademik & semester tidak ditemukan.';
            }

            if (! array_key_exists($data['jenis'], BillType::labels())) {
                $errors[] = 'Jenis tagihan harus ukt atau lainnya.';
            }

            // Nominal: izinkan "5.000.000", "5000000", "5,000,000" — hasil integer rupiah.
            $nominalNormalized = preg_replace('/[.,\s]/', '', (string) $data['nominal']);
            if ($nominalNormalized === '' || ! ctype_digit($nominalNormalized)) {
                $errors[] = 'Nominal harus berupa angka rupiah bulat tanpa desimal.';
            } else {
                $data['nominal'] = (int) $nominalNormalized;
                if ($data['nominal'] < 1) {
                    $errors[] = 'Nominal harus lebih dari nol.';
                } elseif ($data['nominal'] > 1_000_000_000_000) {
                    $errors[] = 'Nominal melebihi batas wajar.';
                }
            }

            $dueDate = Carbon::tryParse($data['jatuh_tempo']);
            if ($dueDate === null) {
                $errors[] = 'Tanggal jatuh tempo tidak valid (gunakan format YYYY-MM-DD).';
            } else {
                $data['jatuh_tempo'] = $dueDate->toDateString();
            }

            // Duplikasi dalam berkas.
            $key = $data['nim'].'|'.$data['tahun_akademik'].'|'.$data['semester'].'|'.$data['jenis'];
            if (in_array($key, $seenKeys, true)) {
                $errors[] = 'Duplikat: baris yang sama sudah ada di berkas.';
            }
            $seenKeys[] = $key;

            // Duplikasi pada database.
            if ($errors === [] && $students->has($data['nim']) && $terms->has($data['tahun_akademik'].'|'.$data['semester'])) {
                $exists = Bill::where('student_id', $students[$data['nim']])
                    ->where('academic_term_id', $terms[$data['tahun_akademik'].'|'.$data['semester']]->id)
                    ->where('bill_type', $data['jenis'])
                    ->exists();

                if ($exists) {
                    $errors[] = 'Duplikat: tagihan jenis ini untuk periode tersebut sudah ada (tidak diubah).';
                }
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

    public static function templateResponse(): StreamedResponse
    {
        return Templates::billTemplate();
    }
}
