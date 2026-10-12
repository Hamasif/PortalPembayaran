<?php

namespace App\Models;

use App\Enums\StudentStatus;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    /**
     * user_id sengaja TIDAK mass-assignable: keterkaitan akun hanya boleh
     * dilakukan lewat alur verifikasi di StudentVerificationService.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nim',
        'full_name',
        'program_study',
        'cohort_year',
        'email',
        'student_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'student_status' => StudentStatus::class,
            'cohort_year' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * Total sisa tagihan (amount - pembayaran sukses).
     */
    public function outstandingAmount(): int
    {
        return (int) $this->bills()
            ->whereIn('status', [BillStatus::Unpaid->value, BillStatus::PartiallyPaid->value])
            ->get()
            ->sum(fn (Bill $bill) => $bill->remainingAmount());
    }
}
