<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AllowedEmailDomain extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'domain',
        'is_active',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Daftar domain aktif (cache 60 detik agar hemat query).
     *
     * @return list<string>
     */
    public static function activeDomains(): array
    {
        return cache()->remember('allowed_email_domains', 60, function () {
            $domains = static::query()
                ->where('is_active', true)
                ->pluck('domain')
                ->map(fn (string $domain) => strtolower(trim($domain)))
                ->all();

            // Fallback ke konfigurasi saat tabel masih kosong (bootstrap awal).
            if ($domains === []) {
                $domains = config('portal.allowed_email_domains', []);
            }

            return array_values(array_unique(array_map('strtolower', $domains)));
        });
    }

    /**
     * Cek apakah sebuah email berada di domain yang diizinkan.
     */
    public static function allowsEmail(string $email): bool
    {
        $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));

        return $domain !== '' && in_array($domain, static::activeDomains(), true);
    }

    protected static function booted(): void
    {
        static::saved(fn () => cache()->forget('allowed_email_domains'));
        static::deleted(fn () => cache()->forget('allowed_email_domains'));
    }
}
