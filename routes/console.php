<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Tampilkan kutipan motivasi');

// Kedaluwarsakan transaksi QRIS pending yang melewati masa berlaku.
Schedule::command('payments:expire-stale')->everyMinute();
