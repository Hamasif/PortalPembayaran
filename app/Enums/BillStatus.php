<?php

namespace App\Enums;

enum BillStatus: string
{
    case Unpaid = 'unpaid';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Unpaid->value => 'Belum Lunas',
            self::PartiallyPaid->value => 'Sebagian Terbayar',
            self::Paid->value => 'Lunas',
            self::Cancelled->value => 'Dibatalkan',
        ];
    }

    public function label(): string
    {
        return self::labels()[$this->value] ?? $this->value;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
