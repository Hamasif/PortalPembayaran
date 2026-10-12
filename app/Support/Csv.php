<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Utilitas CSV dengan proteksi formula injection.
 *
 * Nilai yang diawali karakter rumus (=, +, -, @, tab, CR) diberi awalan
 * kutip satu (') agar tidak dieksekusi oleh Excel/LibreOffice saat file
 * ekspor dibuka.
 */
final class Csv
{
    private const FORMULA_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * Bersihkan satu sel untuk ekspor CSV yang aman.
     */
    public static function safeCell(mixed $value): string
    {
        $value = $value === null ? '' : (string) $value;

        if ($value !== '' && in_array($value[0], self::FORMULA_PREFIXES, true)) {
            // "-" awalan juga dipakai angka negatif; izinkan angka negatif murni.
            if ($value[0] === '-' && is_numeric($value)) {
                return $value;
            }

            return "'".$value;
        }

        return $value;
    }

    /**
     * Kirim array baris sebagai unduhan CSV ke browser.
     *
     * @param  list<string>  $header
     * @param  iterable<list<mixed>>  $rows
     */
    public static function streamDownload(string $filename, array $header, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, array_map(self::safeCell(...), $header), separator: ';', escape: '\\');

            foreach ($rows as $row) {
                fputcsv($handle, array_map(self::safeCell(...), array_values($row)), separator: ';', escape: '\\');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
