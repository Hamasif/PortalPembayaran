<?php

namespace App\Enums;

enum Semester: string
{
    case Ganjil = 'ganjil';
    case Genap = 'genap';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Ganjil->value => 'Ganjil',
            self::Genap->value => 'Genap',
        ];
    }

    public function label(): string
    {
        return self::labels()[$this->value] ?? $this->value;
    }
}
