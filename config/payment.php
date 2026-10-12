<?php

return [

    /*
    |----------------------------------------------------------------------
    | Konfigurasi Payment Gateway (QRIS)
    |----------------------------------------------------------------------
    | 'mock' = provider pengembangan lokal. BUKAN pembayaran sungguhan.
    | Untuk provider resmi, implementasikan adapter berdasarkan
    | dokumentasi resmi penyedia dan daftarkan di PaymentGatewayManager.
    */

    'default' => env('PAYMENT_GATEWAY', 'mock'),

    'environment' => env('PAYMENT_GATEWAY_ENV', 'sandbox'),

    'timeout' => (int) env('PAYMENT_GATEWAY_TIMEOUT', 30),

    'mock' => [
        // Secret untuk menandatangani webhook mock (HMAC SHA-256).
        'webhook_secret' => env('PAYMENT_GATEWAY_WEBHOOK_SECRET'),
        'ttl_minutes' => env('QRIS_TTL_MINUTES', 15),
    ],

    /*
    | Kredensial provider resmi ditempatkan di sini saat tersedia,
    | contoh struktur (jangan mengarang endpoint provider):
    |
    | '<provider>' => [
    |     'merchant_id' => env('PAYMENT_GATEWAY_MERCHANT_ID'),
    |     'server_key'  => env('PAYMENT_GATEWAY_SERVER_KEY'),
    | ],
    */
];
