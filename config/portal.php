<?php

return [

    /*
    |----------------------------------------------------------------------
    | Konfigurasi Portal Pembayaran Mahasiswa
    |----------------------------------------------------------------------
    */

    /*
    |----------------------------------------------------------------------
    | Domain Email yang Diizinkan (fallback / seed awal)
    |----------------------------------------------------------------------
    | Sumber utama adalah tabel `allowed_email_domains` (dikelola admin).
    | Nilai di bawah hanya digunakan saat tabel kosong (bootstrap awal).
    |
    | PENTING: WAJIB diganti dengan domain resmi institusi Anda sebelum
    | digunakan di lingkungan produksi. Nilai ini hanya contoh.
    */
    'allowed_email_domains' => [
        'student.universitas.ac.id',
        'universitas.ac.id',
    ],

    /*
    |----------------------------------------------------------------------
    | Unggahan Berkas (Import Excel/CSV)
    |----------------------------------------------------------------------
    | Ukuran maksimum dalam kilobyte.
    */
    'import_max_size_kb' => 5120, // 5 MB

    'import_allowed_extensions' => ['csv', 'xls', 'xlsx'],

    /*
    |----------------------------------------------------------------------
    | Mata Uang
    |----------------------------------------------------------------------
    */
    'currency' => 'IDR',
    'currency_symbol' => 'Rp',

    /*
    |----------------------------------------------------------------------
    | Aturan Pembayaran MVP
    |----------------------------------------------------------------------
    | MVP ini TIDAK mendukung pembayaran parsial. Nominal pembayaran
    | harus sama dengan sisa tagihan. Lihat README.md bagian aturan.
    */
    'allow_partial_payment' => false,

    /*
    |----------------------------------------------------------------------
    | Masa Berlaku QRIS (menit) — untuk mock/development
    |----------------------------------------------------------------------
    */
    'qris_ttl_minutes' => 15,
];
