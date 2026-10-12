<?php

namespace App\Enums;

enum StudentStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Graduated = 'graduated';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Active->value => 'Aktif',
            self::Inactive->value => 'Tidak Aktif',
            self::Graduated->value => 'Lulus',
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
