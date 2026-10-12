<?php

namespace App\Enums;

enum TermStatus: string
{
    case Upcoming = 'upcoming';
    case Active = 'active';
    case Closed = 'closed';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Upcoming->value => 'Akan Datang',
            self::Active->value => 'Aktif',
            self::Closed->value => 'Ditutup',
        ];
    }

    public function label(): string
    {
        return self::labels()[$this->value] ?? $this->value;
    }
}
