<?php

namespace App\Services;

use App\Enums\BillType;
use App\Models\AcademicTerm;
use App\Models\Bill;
use App\Models\Student;

/**
 * Generator nomor tagihan deterministik & unik:
 * BILL-{tahun akademik}-{semester}-{NIM}-{jenis}
 * Contoh: BILL-2024/2025-GENAP-20230001-UKT
 */
final class BillNumberService
{
    public static function generate(AcademicTerm $term, Student $student, BillType $type): string
    {
        return sprintf(
            'BILL-%s-%s-%s-%s',
            str_replace('/', '-', $term->academic_year),
            strtoupper($term->semester->value),
            $student->nim,
            strtoupper($type->value)
        );
    }
}
