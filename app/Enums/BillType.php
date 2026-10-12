<?php

namespace App\Enums;

enum BillType: string
{
    case Ukt = 'ukt';
    case Other = 'other';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Ukt->value => 'UKT',
            self::Other->value => 'Lainnya',
        ];
    }

    public function label(): string
    {
        return self::labels()[$this->value] ?? $this->value;
    }
}
