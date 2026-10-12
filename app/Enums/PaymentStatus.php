<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
    case Expired = 'expired';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Pending->value => 'Menunggu Pembayaran',
            self::Success->value => 'Berhasil',
            self::Failed->value => 'Gagal',
            self::Expired->value => 'Kedaluwarsa',
        ];
    }

    public function label(): string
    {
        return self::labels()[$this->value] ?? $this->value;
    }
}
