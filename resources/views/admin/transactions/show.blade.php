<x-layouts.admin title="Detail Transaksi">
    <a href="{{ route('admin.transactions.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-muted transition hover:text-ink">
        <x-lucide-arrow-left class="h-4 w-4" /> Kembali ke monitoring
    </a>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card p-6 lg:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-lg font-bold text-ink">Transaksi #{{ $payment->id }}</h1>
                    <p class="mt-1 font-mono text-xs text-muted">{{ $payment->external_transaction_id ?? $payment->idempotency_key }}</p>
                </div>
                <x-status-pill :status="$payment->status->value" />
            </div>

            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-line bg-surface px-4 py-3">
                    <dt class="text-xs text-muted">Nominal</dt>
                    <dd class="mt-1 font-bold text-ink">Rp {{ number_format($payment->amount, 0, ',', '.') }} {{ $payment->currency }}</dd>
                </div>
                <div class="rounded-lg border border-line bg-surface px-4 py-3">
                    <dt class="text-xs text-muted">Metode</dt>
                    <dd class="mt-1 font-semibold uppercase text-ink">{{ $payment->payment_method }}</dd>
                </div>
                <div class="rounded-lg border border-line bg-surface px-4 py-3">
                    <dt class="text-xs text-muted">Dibuat</dt>
                    <dd class="mt-1 text-sm font-semibold text-ink">{{ $payment->created_at->format('d M Y H:i') }}</dd>
                </div>
                <div class="rounded-lg border border-line bg-surface px-4 py-3">
                    <dt class="text-xs text-muted">Dibayar</dt>
                    <dd class="mt-1 text-sm font-semibold text-ink">{{ optional($payment->paid_at)->format('d M Y H:i') ?? '—' }}</dd>
                </div>
                <div class="rounded-lg border border-line bg-surface px-4 py-3">
                    <dt class="text-xs text-muted">Kedaluwarsa</dt>
                    <dd class="mt-1 text-sm font-semibold text-ink">{{ optional($payment->expires_at)->format('d M Y H:i') ?? '—' }}</dd>
                </div>
                <div class="rounded-lg border border-line bg-surface px-4 py-3">
                    <dt class="text-xs text-muted">Provider reference</dt>
                    <dd class="mt-1 break-all font-mono text-xs font-semibold text-ink">{{ $payment->provider_reference ?? '—' }}</dd>
                </div>
            </dl>

            @if ($payment->isPending())
                <form method="POST" action="{{ route('admin.transactions.check', $payment) }}" class="mt-6"
                      data-confirm="Periksa status transaksi ke provider?">
                    @csrf
                    <button type="submit" class="btn-secondary">
                        <x-lucide-refresh-cw class="h-4 w-4" /> Sinkronkan Status ke Provider
                    </button>
                    <p class="mt-2 text-xs text-muted">Gunakan bila webhook provider terlambat.</p>
                </form>
            @endif
        </div>

        <div class="space-y-6">
            <div class="card p-6">
                <h2 class="text-sm font-bold text-ink">Terkait Tagihan</h2>
                <dl class="mt-3 space-y-2.5 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Mahasiswa</dt>
                        <dd class="text-right font-medium text-ink">{{ $payment->bill->student->full_name }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">NIM</dt>
                        <dd class="font-mono text-xs font-medium text-ink">{{ $payment->bill->student->nim }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">No. tagihan</dt>
                        <dd class="font-mono text-xs font-medium text-ink">{{ $payment->bill->bill_number }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Periode</dt>
                        <dd class="text-xs font-medium text-ink">{{ $payment->bill->academicTerm->label() }}</dd>
                    </div>
                </dl>
                <a href="{{ route('admin.bills.show', $payment->bill) }}" class="btn-secondary mt-4 w-full !py-2 text-xs">Lihat Tagihan</a>
            </div>

            <div class="card p-6">
                <h2 class="text-sm font-bold text-ink">Log Event ({{ $payment->events->count() }})</h2>
                @if ($payment->events->isEmpty())
                    <p class="mt-2 text-xs text-muted">Belum ada event.</p>
                @else
                    <ul class="mt-3 space-y-2.5">
                        @foreach ($payment->events->take(10) as $event)
                            <li class="rounded-lg bg-surface px-3 py-2.5">
                                <p class="text-xs font-semibold text-ink">{{ $event->event_type }}</p>
                                <p class="mt-0.5 text-[11px] text-muted">
                                    {{ $event->created_at->format('d M H:i:s') }}
                                    @if ($event->processed_at) · diproses @else · belum diproses @endif
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-layouts.admin>
