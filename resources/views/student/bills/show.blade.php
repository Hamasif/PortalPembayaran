<x-layouts.student title="Detail Tagihan">
    @php
        $remaining = $bill->remainingAmount();
        $paidTotal = $bill->paidAmount();
    @endphp

    <a href="{{ route('student.bills.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-muted transition hover:text-ink">
        <x-lucide-arrow-left class="h-4 w-4" /> Kembali ke daftar tagihan
    </a>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Info tagihan --}}
        <div class="card p-6 lg:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-lg font-bold text-ink">{{ $bill->description ?? 'Tagihan '.$bill->bill_type->label() }}</h1>
                    <p class="mt-1 font-mono text-xs text-muted">{{ $bill->bill_number }}</p>
                </div>
                <x-status-pill :status="$bill->status->value" />
            </div>

            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-line bg-surface px-4 py-3">
                    <dt class="text-xs text-muted">Periode</dt>
                    <dd class="mt-1 text-sm font-semibold text-ink">{{ $bill->academicTerm->label() }}</dd>
                </div>
                <div class="rounded-lg border border-line bg-surface px-4 py-3">
                    <dt class="text-xs text-muted">Jenis</dt>
                    <dd class="mt-1 text-sm font-semibold text-ink">{{ $bill->bill_type->label() }}</dd>
                </div>
                <div class="rounded-lg border border-line bg-surface px-4 py-3">
                    <dt class="text-xs text-muted">Jatuh Tempo</dt>
                    <dd class="mt-1 text-sm font-semibold text-ink">{{ $bill->due_date->format('d M Y') }}</dd>
                </div>
                <div class="rounded-lg border border-line bg-surface px-4 py-3">
                    <dt class="text-xs text-muted">Nominal</dt>
                    <dd class="mt-1 text-sm font-semibold text-ink">Rp {{ number_format($bill->amount, 0, ',', '.') }}</dd>
                </div>
            </dl>

            {{-- Rincian pembayaran --}}
            <h2 class="mt-8 text-sm font-bold text-ink">Rincian Pembayaran</h2>
            <div class="mt-3 space-y-2 rounded-lg border border-line p-4">
                <div class="flex justify-between text-sm">
                    <span class="text-muted">Nominal tagihan</span>
                    <span class="font-medium text-ink">Rp {{ number_format($bill->amount, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-muted">Sudah terbayar</span>
                    <span class="font-medium text-brand-700">Rp {{ number_format($paidTotal, 0, ',', '.') }}</span>
                </div>
                <div class="border-t border-line pt-2 flex justify-between text-sm">
                    <span class="font-semibold text-ink">Sisa tagihan</span>
                    <span class="font-bold {{ $remaining > 0 ? 'text-accent-700' : 'text-brand-700' }}">
                        Rp {{ number_format($remaining, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            @if ($bill->isPayable())
                <form method="POST" action="{{ route('student.payments.store') }}" class="mt-6">
                    @csrf
                    <input type="hidden" name="bill_id" value="{{ $bill->id }}">
                    <button type="submit" class="btn-primary w-full sm:w-auto">
                        <x-lucide-qr-code class="h-5 w-5" /> Bayar dengan QRIS
                    </button>
                    <p class="mt-2 text-xs text-muted">
                        Pembayaran penuh sebesar <strong>Rp {{ number_format($remaining, 0, ',', '.') }}</strong>.
                        Pembayaran parsial belum didukung pada tahap ini.
                    </p>
                </form>
            @elseif ($bill->status->value === 'paid')
                <div class="mt-6">
                    <x-alert type="success">
                        Tagihan ini telah lunas. Bukti pembayaran tersedia di riwayat transaksi Anda.
                    </x-alert>
                </div>
            @elseif ($bill->status->value === 'cancelled')
                <div class="mt-6">
                    <x-alert type="info">Tagihan ini telah dibatalkan oleh bagian keuangan.</x-alert>
                </div>
            @endif
        </div>

        {{-- Riwayat transaksi tagihan ini --}}
        <div class="card p-6">
            <h2 class="text-sm font-bold text-ink">Transaksi Tagihan Ini</h2>
            @if ($payments->isEmpty())
                <x-empty-state icon="credit-card" title="Belum ada transaksi"
                    description="Transaksi pembayaran untuk tagihan ini akan tampil di sini." class="mt-4 !px-4 !py-8" />
            @else
                <ul class="mt-4 space-y-3">
                    @foreach ($payments as $payment)
                        <li class="rounded-lg border border-line px-4 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-mono text-xs text-muted">{{ Str::limit($payment->external_transaction_id ?? $payment->idempotency_key, 18) }}</span>
                                <x-status-pill :status="$payment->status->value" />
                            </div>
                            <p class="mt-1.5 text-sm font-semibold text-ink">Rp {{ number_format($payment->amount, 0, ',', '.') }}</p>
                            <p class="text-xs text-muted">{{ optional($payment->created_at)->format('d M Y H:i') }}</p>
                            @if ($payment->isSuccess())
                                <a href="{{ route('student.payments.receipt', $payment) }}" class="btn-ghost mt-2 !px-3 !py-1.5 text-xs">
                                    <x-lucide-file-check-2 class="h-3.5 w-3.5" /> Lihat bukti
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-layouts.student>
