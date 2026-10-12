<?php

namespace App\Services;

use OpenSpout\Reader\CSV\Options;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Pembaca generik file CSV/XLSX menjadi array baris asosiatif
 * berdasarkan baris header pertama.
 */
final class SpreadsheetReader
{
    /**
     * Deteksi delimiter CSV dari baris pertama (dukung ',' dan ';').
     */
    private function detectCsvDelimiter(string $absolutePath): string
    {
        $handle = fopen($absolutePath, 'rb');

        if ($handle === false) {
            return ',';
        }

        $firstLine = (string) fgets($handle);
        fclose($handle);

        return substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
    }

    /**
     * Baca file dan kembalikan daftar baris (kolom => nilai).
     *
     * Baris header dideteksi otomatis:
     * - Jika $requiredHeaders diisi, baris pertama (maks. 25 baris awal)
     *   yang memuat SEMUA kolom wajib dipakai sebagai header — sehingga
     *   berkas dengan baris judul/deskripsi di atas tetap terbaca.
     * - Jika kosong, baris pertama yang tidak kosong dipakai.
     *
     * @param  list<string>  $requiredHeaders
     * @return array{headers: list<string>, rows: list<array<string, string>>}
     */
    public function read(string $absolutePath, string $originalName, array $requiredHeaders = []): array
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        $reader = match ($extension) {
            'csv' => new CsvReader(new Options(
                FIELD_DELIMITER: $this->detectCsvDelimiter($absolutePath)
            )),
            'xls', 'xlsx' => new XlsxReader,
            default => throw new \InvalidArgumentException('Format berkas tidak didukung. Gunakan CSV atau XLSX.'),
        };

        try {
            $reader->open($absolutePath);

            $sheetIterator = $reader->getSheetIterator();
            $sheetIterator->rewind();
            $sheet = $sheetIterator->current();

            $rowIterator = $sheet->getRowIterator();
            $rowIterator->rewind();

            if (! $rowIterator->valid()) {
                throw new \InvalidArgumentException('Berkas kosong atau tidak dapat dibaca.');
            }

            // Kumpulkan hingga 25 baris pertama (cukup untuk judul + header + mulai data).
            $rawRows = [];

            while ($rowIterator->valid() && count($rawRows) < 25) {
                $rawRows[] = $rowIterator->current()->toArray();
                $rowIterator->next();
            }

            $normalizedRows = array_map(
                fn (array $cells) => $this->normalizeHeaders($cells),
                $rawRows
            );

            $headerIndex = $this->locateHeaderRow($normalizedRows, $requiredHeaders);
            $headers = $normalizedRows[$headerIndex];

            if ($headers === ['_ignore']) {
                throw new \InvalidArgumentException('Baris header tidak ditemukan pada berkas.');
            }

            $rows = [];

            for ($i = $headerIndex + 1; $i < count($rawRows); $i++) {
                $cells = $rawRows[$i];
                $row = [];

                foreach ($headers as $index => $header) {
                    $row[$header] = isset($cells[$index]) ? trim((string) $cells[$index]) : '';
                }

                // Lewati baris yang seluruhnya kosong.
                if (implode('', $row) !== '') {
                    $rows[] = $row;
                }
            }

            // Baris data di luar 25 baris pertama (berkas besar).
            while ($rowIterator->valid()) {
                $cells = $rowIterator->current()->toArray();
                $row = [];

                foreach ($headers as $index => $header) {
                    $row[$header] = isset($cells[$index]) ? trim((string) $cells[$index]) : '';
                }

                if (implode('', $row) !== '') {
                    $rows[] = $row;
                }

                $rowIterator->next();
            }
        } finally {
            $reader->close();
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * Temukan indeks baris header dalam kumpulan baris ternormalisasi.
     *
     * @param  list<list<string>>  $normalizedRows
     * @param  list<string>  $requiredHeaders
     */
    private function locateHeaderRow(array $normalizedRows, array $requiredHeaders): int
    {
        if ($requiredHeaders !== []) {
            foreach ($normalizedRows as $index => $headers) {
                if (count(array_intersect($requiredHeaders, $headers)) === count($requiredHeaders)) {
                    return $index;
                }
            }

            throw new \InvalidArgumentException(
                'Baris header tidak ditemukan. Pastikan kolom berikut ada: '.implode(', ', $requiredHeaders).'.'
            );
        }

        foreach ($normalizedRows as $index => $headers) {
            $meaningful = array_filter($headers, fn (string $h) => $h !== '_ignore');

            if ($meaningful !== []) {
                return $index;
            }
        }

        return 0;
    }

    /**
     * Normalisasi nama kolom header: huruf kecil, tanpa spasi berlebih.
     *
     * @param  list<mixed>  $headerRow
     * @return list<string>
     */
    private function normalizeHeaders(array $headerRow): array
    {
        $aliases = [
            'nim' => 'nim',
            'n.o._induk_mahasiswa' => 'nim',
            'no_induk_mahasiswa' => 'nim',
            'nama' => 'nama_lengkap',
            'nama_lengkap' => 'nama_lengkap',
            'program_studi' => 'program_studi',
            'prodi' => 'program_studi',
            'angkatan' => 'angkatan',
            'tahun_angkatan' => 'angkatan',
            'email' => 'email',
            'status' => 'status',
            'status_mahasiswa' => 'status',
        ];

        $headers = [];

        foreach ($headerRow as $cell) {
            $normalized = strtolower(trim(preg_replace('/[\s\-\.]+/', '_', (string) $cell) ?? ''));

            if (isset($aliases[$normalized])) {
                $headers[] = $aliases[$normalized];
            } elseif ($normalized !== '') {
                $headers[] = $normalized;
            } else {
                $headers[] = '_ignore';
            }
        }

        return $headers;
    }
}
