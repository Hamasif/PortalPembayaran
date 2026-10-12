<?php

namespace App\Models;

use App\Enums\BillStatus;
use App\Enums\BillType;
use App\Enums\PaymentStatus;
use Database\Factories\BillFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    /** @use HasFactory<BillFactory> */
    use HasFactory;

    /**
     * `status` TIDAK mass-assignable — hanya berubah lewat transaksi
     * pembayaran terverifikasi atau aksi admin yang diaudit.
     *
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'academic_term_id',
        'bill_number',
        'bill_type',
        'description',
        'amount',
        'due_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bill_type' => BillType::class,
            'status' => BillStatus::class,
            'amount' => 'integer',
            'due_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Total pembayaran dengan status sukses untuk tagihan ini.
     */
    public function paidAmount(): int
    {
        return (int) $this->payments()
            ->where('status', PaymentStatus::Success->value)
            ->sum('amount');
    }

    /**
     * Sisa tagihan yang belum dibayar.
     */
    public function remainingAmount(): int
    {
        return max(0, $this->amount - $this->paidAmount());
    }

    /**
     * Apakah tagihan dapat dibayar (belum lunas dan tidak dibatalkan).
     */
    public function isPayable(): bool
    {
        return in_array($this->status, [BillStatus::Unpaid, BillStatus::PartiallyPaid], true)
            && $this->remainingAmount() > 0;
    }

    public function isOverdue(): bool
    {
        return $this->isPayable() && $this->due_date->isPast();
    }

    public function daysUntilDue(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->due_date->copy()->startOfDay(), false);
    }

    /**
     * Scope: hanya tagihan milik mahasiswa tertentu (proteksi kepemilikan data).
     */
    public function scopeForStudent(Builder $query, Student $student): Builder
    {
        return $query->where('student_id', $student->id);
    }

    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}
