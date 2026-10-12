<x-layouts.admin title="Detail Tagihan">
    <a href="{{ route('admin.bills.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-muted transition hover:text-ink">
        <x-lucide-arrow-left class="h-4 w-4" /> Kembali ke daftar tagihan
    </a>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card p-6 lg:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="font-mono text-base font-bold text-ink">{{ $bill->bill_number }}</h1>
                    <p class="mt-1 text-sm text-muted">
                        {{ $bill->student->full_name }} ({{ $bill->student->nim }}) · {{ $bill->academicTerm->label() }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <x-status-pill :status="$bill->status->value" />
                    @if ($bill->status !== \App\Enums\BillStatus::Paid && $bill->status !== \App\Enums\BillStatus::Cancelled)
                        <a href="{{ route('admin.bills.edit', $bill) }}" class="btn-secondary !px-3 !py-1.5 text-xs">
                            <x-lucide-pencil class="h-3.5 w-3.5" /> Ubah
                        </a>
                    @endif
                </div>
            </div>

            <dl class="mt-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-line bg-surface px-4 py-3">
                    <dt class="text-xs text-muted">Nominal</dt>
                    <dd class="mt-1 font-bold text-ink">Rp {{ number_format($bill->amount, 0, ',', '.') }}</dd>
                </div>
                <div class="rounded-lg border border-line bg-surface px-4 py-3">
                    <dt class="text-xs text-muted">Terbayar</dt>
                    <dd class="mt-1 font-bold text-brand-700">Rp {{ number_format($bill->paidAmount(), 0, ',', '.') }}</dd>
                </div>
                <div class="rounded-lg border border-line bg-surface px-4 py-3">
                    <dt class="text-xs text-muted">Sisa</dt>
                    <dd class="mt-1 font-bold text-accent-700">Rp {{ number_format($bill->remainingAmount(), 0, ',', '.') }}</dd>
                </div>
            </dl>

            @if ($bill->status !== \App\Enums\BillStatus::Paid && $bill->paidAmount() === 0)
                <form method="POST" action="{{ route('admin.bills.cancel', $bill) }}"
                      class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4"
                      data-confirm="Batalkan tagihan ini? Tagihan yang dibatalkan tidak dapat dibayar mahasiswa.">
                    @csrf
                    <label for="reason" class="text-xs font-semibold text-red-700">Alasan pembatalan</label>
                    <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                        <input type="text" id="reason" name="reason" maxlength="255" placeholder="Contoh: kesalahan input nominal" class="input flex-1" required>
                        <button type="submit" class="btn-danger shrink-0">
                            <x-lucide-ban class="h-4 w-4" /> Batalkan Tagihan
                        </button>
                    </div>
                    <p class="mt-2 text-[11px] text-red-600">
                        Tagihan dengan riwayat pembayaran tidak dapat dibatalkan — gunakan proses koreksi resmi.
                    </p>
                </form>
            @endif
        </div>

        <div class="card p-6">
            <h2 class="text-sm font-bold text-ink">Transaksi</h2>
            @if ($bill->payments->isEmpty())
                <x-empty-state icon="credit-card" title="Belum ada transaksi" class="mt-4 !px-4 !py-8" />
            @else
                <ul class="mt-4 space-y-3">
                    @foreach ($bill->payments as $payment)
                        <li class="rounded-lg border border-line px-4 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm font-semibold text-ink">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                                <x-status-pill :status="$payment->status->value" />
                            </div>
                            <p class="mt-1 font-mono text-[11px] text-muted">{{ $payment->external_transaction_id ?? $payment->idempotency_key }}</p>
                            <a href="{{ route('admin.transactions.show', $payment) }}" class="btn-ghost mt-1 !px-0 !py-1 text-xs">Detail transaksi</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-layouts.admin>
