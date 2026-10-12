<x-layouts.admin title="Monitoring Transaksi">
    <div>
        <h1 class="text-xl font-bold text-ink">Monitoring Transaksi</h1>
        <p class="mt-1 text-sm text-muted">Semua transaksi pembayaran dari payment gateway.</p>
    </div>

    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card label="Total Berhasil" :value="'Rp ' . number_format($summary['success_amount'], 0, ',', '.')" icon="badge-check" tone="brand" />
        <x-stat-card label="Transaksi Sukses" :value="$summary['success_count']" icon="check-circle-2" tone="slate" />
        <x-stat-card label="Menunggu" :value="$summary['pending_count']" icon="clock" tone="accent" />
        <x-stat-card label="Gagal" :value="$summary['failed_count']" icon="x-circle" tone="red" />
    </div>

    <form method="GET" action="{{ route('admin.transactions.index') }}" class="card mt-5 grid gap-3 p-4 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <label for="q" class="mb-1 block text-xs font-medium text-muted">Cari</label>
            <input type="text" id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="ID transaksi / no. tagihan / NIM…" class="input">
        </div>
        <div class="flex items-end gap-2">
            <select id="status" name="status" class="input">
                <option value="">Semua status</option>
                @foreach (\App\Enums\PaymentStatus::labels() as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-secondary shrink-0">
                <x-lucide-search class="h-4 w-4" />
            </button>
        </div>
    </form>

    <div class="mt-5">
        @if ($payments->isEmpty())
            <x-empty-state icon="credit-card" title="Belum ada transaksi"
                description="Transaksi akan muncul setelah mahasiswa melakukan pembayaran QRIS." />
        @else
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[880px]">
                        <thead class="border-b border-line bg-surface">
                            <tr>
                                <th class="table-th">Waktu</th>
                                <th class="table-th">Mahasiswa</th>
                                <th class="table-th">No. Tagihan</th>
                                <th class="table-th">Nominal</th>
                                <th class="table-th">ID Eksternal</th>
                                <th class="table-th">Status</th>
                                <th class="table-th"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($payments as $payment)
                                <tr class="transition-colors hover:bg-slate-50/60">
                                    <td class="table-td whitespace-nowrap text-xs">{{ $payment->created_at->format('d M Y H:i') }}</td>
                                    <td class="table-td">
                                        <p class="font-medium">{{ $payment->bill->student->full_name }}</p>
                                        <p class="font-mono text-xs text-muted">{{ $payment->bill->student->nim }}</p>
                                    </td>
                                    <td class="table-td font-mono text-xs">{{ $payment->bill->bill_number }}</td>
                                    <td class="table-td font-semibold">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                    <td class="table-td font-mono text-[11px] text-muted">{{ \Illuminate\Support\Str::limit($payment->external_transaction_id ?? '—', 20) }}</td>
                                    <td class="table-td"><x-status-pill :status="$payment->status->value" /></td>
                                    <td class="table-td text-right">
                                        <a href="{{ route('admin.transactions.show', $payment) }}" class="btn-ghost !px-3 !py-1.5 text-xs">Detail</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-line p-4">
                    <x-pagination :paginator="$payments" />
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
