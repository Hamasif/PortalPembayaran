<?php

namespace App\Enums;

enum AccountStatus: string
{
    case Active = 'active';
    case PendingVerification = 'pending_verification';
    case Blocked = 'blocked';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Active->value => 'Aktif',
            self::PendingVerification->value => 'Menunggu Verifikasi',
            self::Blocked->value => 'Diblokir',
        ];
    }

    public function label(): string
    {
        return self::labels()[$this->value] ?? $this->value;
    }
}
