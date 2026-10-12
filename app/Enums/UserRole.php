<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case FinanceAdmin = 'finance_admin';
    case Student = 'student';

    /**
     * Label ramah pengguna untuk antarmuka.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::SuperAdmin->value => 'Super Admin',
            self::FinanceAdmin->value => 'Admin Keuangan',
            self::Student->value => 'Mahasiswa',
        ];
    }

    public function label(): string
    {
        return self::labels()[$this->value] ?? $this->value;
    }

    public function isAdmin(): bool
    {
        return in_array($this, [self::SuperAdmin, self::FinanceAdmin], true);
    }
}
