<?php

namespace App\Support;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Generator templat Excel (.xlsx) untuk import admin.
 *
 * Templat dirancang agar cepat & sulit salah:
 * - Header berwarna hijau brand dengan teks putih tebal.
 * - Lebar kolom besar agar seluruh kolom terbaca tanpa resize.
 * - Baris contoh (miring, abu-abu) yang bisa langsung ditimpa.
 * - Sheet "Petunjuk" berisi aturan pengisian dan nilai yang valid.
 *
 * Nama header dipakai juga oleh SpreadsheetReader, sehingga harus
 * tetap cocok dengan alias kolom di sana.
 */
final class Templates
{
    private const BRAND_GREEN = '087F5B';

    private const DARK_GREEN = '065F46';

    /**
     * Templat import data mahasiswa.
     */
    public static function studentTemplate(): StreamedResponse
    {
        return self::download(
            filename: 'template-import-mahasiswa.xlsx',
            title: 'IMPORT DATA MAHASISWA - PORTAL PEMBAYARAN',
            headers: ['NIM', 'Nama Lengkap', 'Program Studi', 'Angkatan', 'Email', 'Status'],
            widths: [18.0, 38.0, 34.0, 12.0, 42.0, 14.0],
            examples: [
                ['20230001', 'Contoh Mahasiswa Satu', 'Teknik Informatika', 2023, '20230001@student.universitas.ac.id', 'active'],
                ['20230002', 'Contoh Mahasiswa Dua', 'Manajemen', 2023, '', 'active'],
            ],
            rules: [
                'NIM - WAJIB. 3-30 karakter alfanumerik (boleh mengandung titik/garis). Unik di seluruh sistem.',
                'Nama Lengkap - WAJIB. Maksimal 150 karakter.',
                'Program Studi - WAJIB. Tulis konsisten, misal: Teknik Informatika.',
                'Angkatan - WAJIB. Tahun 4 digit, contoh: 2023.',
                'Email - Opsional. Email kampus mahasiswa; dipakai untuk pencocokan otomatis.',
                'Status - Opsional. Nilai yang valid: active, inactive, graduated. Kosong = active.',
                '',
                'Catatan:',
                '- Isi data mulai baris ke-4 pada sheet "Data" (baris contoh boleh ditimpa/dihapus).',
                '- Baris dengan NIM yang sudah ada di sistem akan DIPERBARUI, bukan ditolak.',
                '- Sistem memvalidasi seluruh baris dan menampilkan pratinjau SEBELUM tersimpan.',
                '- Jangan menambahkan kolom lain di samping kolom yang tersedia.',
            ],
        );
    }

    /**
     * Templat import tagihan (UKT & lainnya).
     */
    public static function billTemplate(): StreamedResponse
    {
        $year = (int) date('Y');

        return self::download(
            filename: 'template-import-tagihan.xlsx',
            title: 'IMPORT TAGIHAN UKT - PORTAL PEMBAYARAN',
            headers: ['NIM', 'Tahun Akademik', 'Semester', 'Jenis', 'Nominal', 'Jatuh Tempo', 'Keterangan'],
            widths: [18.0, 20.0, 14.0, 12.0, 20.0, 16.0, 44.0],
            examples: [
                ['20230001', sprintf('%d/%d', $year, $year + 1), 'ganjil', 'ukt', 5000000, sprintf('%d-10-31', $year), 'UKT Semester Ganjil'],
                ['20230001', sprintf('%d/%d', $year, $year + 1), 'genap', 'ukt', 5000000, sprintf('%d-04-30', $year + 1), 'UKT Semester Genap'],
            ],
            rules: [
                'NIM - WAJIB. Harus sudah terdaftar di Data Mahasiswa (import data mahasiswa dulu).',
                'Tahun Akademik - WAJIB. Format: 2025/2026. Periode harus sudah dibuat di sistem.',
                'Semester - WAJIB. Nilai yang valid: ganjil atau genap.',
                'Jenis - WAJIB. Nilai yang valid: ukt atau lainnya.',
                'Nominal - WAJIB. Angka rupiah bulat tanpa titik/desimal, contoh: 5000000.',
                'Jatuh Tempo - WAJIB. Format tanggal: YYYY-MM-DD, contoh: 2025-10-31.',
                'Keterangan - Opsional. Maksimal 255 karakter.',
                '',
                'Catatan:',
                '- Isi data mulai baris ke-5 pada sheet "Data" (baris contoh boleh ditimpa/dihapus).',
                '- Satu jenis tagihan hanya boleh satu per mahasiswa per periode - duplikat ditolak.',
                '- Tagihan yang sudah lunas TIDAK AKAN diubah oleh import ini.',
                '- Sistem menampilkan pratinjau dan laporan baris gagal sebelum tersimpan.',
            ],
            headerRowNumber: 3,
            firstDataRow: 5,
        );
    }

    /**
     * Bangun berkas XLSX dan kirim sebagai unduhan.
     *
     * @param  list<string>  $headers
     * @param  list<list<mixed>>  $examples
     * @param  list<string>  $rules
     */
    private static function download(
        string $filename,
        string $title,
        array $headers,
        array $widths,
        array $examples,
        array $rules,
        int $headerRowNumber = 2,
        int $firstDataRow = 4,
    ): StreamedResponse {
        $headerStyle = new Style(
            fontBold: true,
            fontColor: 'FFFFFF',
            fontSize: 11,
            backgroundColor: self::BRAND_GREEN,
            cellAlignment: CellAlignment::CENTER,
        );
        $titleStyle = new Style(fontBold: true, fontColor: self::DARK_GREEN, fontSize: 13);
        $exampleStyle = new Style(fontItalic: true, fontColor: '64748B');
        $noteStyle = new Style(fontBold: true, fontColor: '0F172A', fontSize: 12);
        $ruleStyle = new Style(fontColor: '334155');

        return response()->streamDownload(function () use ($filename, $title, $headers, $widths, $examples, $rules, $headerStyle, $titleStyle, $exampleStyle, $noteStyle, $ruleStyle, $headerRowNumber): void {
            $writer = new Writer;
            $writer->openToBrowser($filename);

            // ---------- Sheet "Data" ----------
            $dataSheet = $writer->getCurrentSheet();
            $dataSheet->setName('Data');

            foreach ($widths as $index => $width) {
                $dataSheet->setColumnWidth($width, $index + 1);
            }

            $writer->addRow(Row::fromValuesWithStyle([$title], $titleStyle));
            for ($i = 2; $i < $headerRowNumber; $i++) {
                $writer->addRow(Row::fromValues(['']));
            }
            $writer->addRow(Row::fromValuesWithStyle($headers, $headerStyle));

            foreach ($examples as $example) {
                $writer->addRow(Row::fromValuesWithStyle($example, $exampleStyle));
            }

            // ---------- Sheet "Petunjuk" ----------
            $writer->addNewSheetAndMakeItCurrent();
            $instructionSheet = $writer->getCurrentSheet();
            $instructionSheet->setName('Petunjuk');
            $instructionSheet->setColumnWidth(110.0, 1);

            $writer->addRow(Row::fromValuesWithStyle(['CARA PENGISIAN'], $noteStyle));
            $writer->addRow(Row::fromValues(['']));
            foreach ($rules as $rule) {
                if ($rule === '') {
                    $writer->addRow(Row::fromValues(['']));

                    continue;
                }
                $isNote = str_starts_with($rule, 'Catatan:') || str_starts_with($rule, '- Isi');
                $writer->addRow(Row::fromValuesWithStyle([$rule], $isNote ? $noteStyle : $ruleStyle));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
