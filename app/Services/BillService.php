<?php

namespace App\Services;

use App\Enums\BillStatus;
use App\Enums\BillType;
use App\Models\AcademicTerm;
use App\Models\AuditLog;
use App\Models\Bill;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Operasi tagihan oleh admin dengan aturan:
 * - Duplikasi jenis tagihan pada periode yang sama ditolak.
 * - Tagihan yang lunas tidak boleh diubah.
 * - Tagihan tidak dihapus; perubahan dicatat pada audit log.
 */
final class BillService
{
    /**
     * @param  array{bill_type: string, description: ?string, amount: int, due_date: string}  $data
     */
    public function create(Student $student, AcademicTerm $term, array $data, User $actor): Bill
    {
        $type = BillType::tryFrom($data['bill_type']);

        if ($type === null) {
            throw new \InvalidArgumentException('Jenis tagihan tidak dikenal.');
        }

        if ($data['amount'] < 1) {
            throw new \InvalidArgumentException('Nominal tagihan harus lebih dari nol.');
        }

        $exists = Bill::where('student_id', $student->id)
            ->where('academic_term_id', $term->id)
            ->where('bill_type', $type->value)
            ->exists();

        if ($exists) {
            throw new \InvalidArgumentException(
                sprintf('Tagihan %s untuk %s pada periode %s semester %s sudah ada.',
                    $type->label(), $student->nim, $term->academic_year, $term->semester->label())
            );
        }

        return DB::transaction(function () use ($student, $term, $data, $type, $actor): Bill {
            $bill = Bill::create([
                'student_id' => $student->id,
                'academic_term_id' => $term->id,
                'bill_number' => BillNumberService::generate($term, $student, $type),
                'bill_type' => $type->value,
                'description' => $data['description'] ?? null,
                'amount' => (int) $data['amount'],
                'due_date' => $data['due_date'],
                'status' => BillStatus::Unpaid->value,
                'created_by' => $actor->id,
            ]);

            $bill->forceFill(['status' => BillStatus::Unpaid->value, 'created_by' => $actor->id])->save();

            AuditLog::record($actor->id, 'bill.created', 'bill', $bill->id, [
                'bill_number' => $bill->bill_number,
                'amount' => $bill->amount,
                'student_nim' => $student->nim,
            ]);

            return $bill;
        });
    }

    /**
     * Perbarui tagihan. Tagihan lunas tidak boleh diubah.
     *
     * @param  array{bill_type?: string, description: ?string, amount: int, due_date: string}  $data
     */
    public function update(Bill $bill, array $data, User $actor): Bill
    {
        if ($bill->status === BillStatus::Paid) {
            throw new \InvalidArgumentException('Tagihan yang sudah lunas tidak dapat diubah.');
        }

        if (isset($data['amount']) && $data['amount'] < 1) {
            throw new \InvalidArgumentException('Nominal tagihan harus lebih dari nol.');
        }

        return DB::transaction(function () use ($bill, $data, $actor): Bill {
            $before = ['amount' => $bill->amount, 'due_date' => $bill->due_date->toDateString()];

            $bill->update([
                'description' => $data['description'] ?? $bill->description,
                'amount' => (int) ($data['amount'] ?? $bill->amount),
                'due_date' => $data['due_date'] ?? $bill->due_date,
            ]);

            AuditLog::record($actor->id, 'bill.updated', 'bill', $bill->id, [
                'bill_number' => $bill->bill_number,
                'before' => $before,
                'after' => ['amount' => $bill->amount, 'due_date' => $bill->due_date->toDateString()],
            ]);

            return $bill;
        });
    }

    /**
     * Batalkan tagihan (hanya jika belum ada pembayaran sukses).
     */
    public function cancel(Bill $bill, User $actor, string $reason): Bill
    {
        if ($bill->status === BillStatus::Paid) {
            throw new \InvalidArgumentException('Tagihan yang sudah lunas tidak dapat dibatalkan.');
        }

        if ($bill->paidAmount() > 0) {
            throw new \InvalidArgumentException('Tagihan memiliki riwayat pembayaran; gunakan proses koreksi resmi, bukan pembatalan.');
        }

        return DB::transaction(function () use ($bill, $actor, $reason): Bill {
            $bill->forceFill(['status' => BillStatus::Cancelled->value])->save();

            AuditLog::record($actor->id, 'bill.cancelled', 'bill', $bill->id, [
                'bill_number' => $bill->bill_number,
                'reason' => $reason,
            ]);

            return $bill;
        });
    }
}
