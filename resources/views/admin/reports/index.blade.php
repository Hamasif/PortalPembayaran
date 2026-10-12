<x-layouts.admin title="Laporan">
    <div>
        <h1 class="text-xl font-bold text-ink">Laporan Transaksi</h1>
        <p class="mt-1 text-sm text-muted">Laporan pembayaran terverifikasi. Ekspor CSV dilindungi dari formula injection.</p>
    </div>

    <form method="GET" action="{{ route('admin.reports.index') }}" class="card mt-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label for="from" class="mb-1 block text-xs font-medium text-muted">Dari tanggal</label>
            <input type="date" id="from" name="from" value="{{ $filters['from'] ?? '' }}" class="input">
        </div>
        <div>
            <label for="to" class="mb-1 block text-xs font-medium text-muted">Sampai tanggal</label>
            <input type="date" id="to" name="to" value="{{ $filters['to'] ?? '' }}" class="input">
        </div>
        <div>
            <label for="term" class="mb-1 block text-xs font-medium text-muted">Periode</label>
            <select id="term" name="term" class="input">
                <option value="">Semua periode</option>
                @foreach ($terms as $term)
                    <option value="{{ $term->id }}" @selected((int) ($filters['term'] ?? 0) === $term->id)>{{ $term->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="btn-secondary flex-1">
                <x-lucide-filter class="h-4 w-4" /> Terapkan
            </button>
        </div>
    </form>

    <div class="card mt-5 flex flex-wrap items-center justify-between gap-3 p-5">
        <div>
            <p class="text-sm text-muted">Total pembayaran terverifikasi (sesuai filter)</p>
            <p class="mt-1 text-2xl font-bold text-brand-700">Rp {{ number_format($total, 0, ',', '.') }}</p>
        </div>
        <a href="{{ route('admin.reports.export', $filters) }}" class="btn-primary">
            <x-lucide-download class="h-4 w-4" /> Ekspor CSV
        </a>
    </div>

    <div class="mt-5">
        @if ($payments->isEmpty())
            <x-empty-state icon="file-chart-column" title="Belum ada data laporan"
                description="Laporan terisi otomatis dari transaksi yang terverifikasi." />
        @else
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[820px]">
                        <thead class="border-b border-line bg-surface">
                            <tr>
                                <th class="table-th">Tanggal Bayar</th>
                                <th class="table-th">Mahasiswa</th>
                                <th class="table-th">No. Tagihan</th>
                                <th class="table-th">Periode</th>
                                <th class="table-th">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($payments as $payment)
                                <tr class="transition-colors hover:bg-slate-50/60">
                                    <td class="table-td whitespace-nowrap text-xs">{{ optional($payment->paid_at)->format('d M Y H:i') }}</td>
                                    <td class="table-td">
                                        {{ $payment->bill->student->full_name }}
                                        <span class="font-mono text-xs text-muted">({{ $payment->bill->student->nim }})</span>
                                    </td>
                                    <td class="table-td font-mono text-xs">{{ $payment->bill->bill_number }}</td>
                                    <td class="table-td text-xs">{{ $payment->bill->academicTerm->label() }}</td>
                                    <td class="table-td font-semibold">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
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
