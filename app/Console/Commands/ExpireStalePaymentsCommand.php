<?php

namespace App\Console\Commands;

use App\Services\Payment\PaymentService;
use Illuminate\Console\Command;

class ExpireStalePaymentsCommand extends Command
{
    protected $signature = 'payments:expire-stale';

    protected $description = 'Kedaluwarsakan transaksi pembayaran pending yang melewati masa berlaku';

    public function handle(PaymentService $paymentService): int
    {
        $count = $paymentService->expireStalePayments();

        $this->info("{$count} transaksi pending dikedaluwarsakan.");

        return self::SUCCESS;
    }
}
