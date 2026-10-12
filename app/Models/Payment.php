<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * `status`, `external_transaction_id`, `provider_reference` TIDAK
     * mass-assignable: hanya berubah lewat PaymentService (transaksi DB
     * + verifikasi webhook), bukan dari input pengguna.
     *
     * @var list<string>
     */
    protected $fillable = [
        'bill_id',
        'user_id',
        'idempotency_key',
        'amount',
        'currency',
        'payment_method',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class);
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    public function isSuccess(): bool
    {
        return $this->status === PaymentStatus::Success;
    }

    /**
     * Apakah transaksi mock/sandbox ini masih dalam masa berlaku.
     */
    public function isExpiredByTime(): bool
    {
        return $this->expires_at !== null
            && $this->isPending()
            && $this->expires_at->isPast();
    }
}
