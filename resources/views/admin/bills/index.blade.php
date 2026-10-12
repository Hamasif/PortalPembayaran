<x-layouts.admin title="Tagihan UKT">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-ink">Manajemen Tagihan UKT</h1>
            <p class="mt-1 text-sm text-muted">
                Diterbitkan: <strong>Rp {{ number_format($summary['total'], 0, ',', '.') }}</strong> ·
                Belum lunas: <strong class="text-accent-700">Rp {{ number_format($summary['unpaid'], 0, ',', '.') }}</strong>
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.bills.import') }}" class="btn-secondary">
                <x-lucide-upload class="h-4 w-4" /> Import Tagihan
            </a>
            <a href="{{ route('admin.bills.create') }}" class="btn-primary">
                <x-lucide-plus class="h-4 w-4" /> Buat Tagihan
            </a>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('admin.bills.index') }}" class="card mt-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-6">
        <div class="lg:col-span-2">
            <label for="q" class="mb-1 block text-xs font-medium text-muted">Cari</label>
            <input type="text" id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="No. tagihan / NIM / nama…" class="input">
        </div>
        <div>
            <label for="status" class="mb-1 block text-xs font-medium text-muted">Status</label>
            <select id="status" name="status" class="input">
                <option value="">Semua</option>
                @foreach (\App\Enums\BillStatus::labels() as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="term" class="mb-1 block text-xs font-medium text-muted">Periode</label>
            <select id="term" name="term" class="input">
                <option value="">Semua</option>
                @foreach ($terms as $term)
                    <option value="{{ $term->id }}" @selected((int) ($filters['term'] ?? 0) === $term->id)>{{ $term->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="cohort" class="mb-1 block text-xs font-medium text-muted">Angkatan</label>
            <select id="cohort" name="cohort" class="input">
                <option value="">Semua</option>
                @foreach ($cohorts as $cohort)
                    <option value="{{ $cohort }}" @selected((int) ($filters['cohort'] ?? 0) === $cohort)>{{ $cohort }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="btn-secondary w-full">
                <x-lucide-search class="h-4 w-4" /> Filter
            </button>
        </div>
    </form>

    <div class="mt-5">
        @if ($bills->isEmpty())
            @if (collect($filters)->filter()->isNotEmpty())
                <x-empty-state icon="search-x" title="Tidak ada hasil" description="Tidak ada tagihan yang cocok dengan filter.">
                    <a href="{{ route('admin.bills.index') }}" class="btn-secondary">Reset Filter</a>
                </x-empty-state>
            @else
                <x-empty-state icon="receipt" title="Belum ada tagihan"
                    description="Buat tagihan satu per satu atau import massal dari berkas CSV/Excel.">
                    <a href="{{ route('admin.bills.create') }}" class="btn-primary">Buat Tagihan Pertama</a>
                </x-empty-state>
            @endif
        @else
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px]">
                        <thead class="border-b border-line bg-surface">
                            <tr>
                                <th class="table-th">No. Tagihan</th>
                                <th class="table-th">Mahasiswa</th>
                                <th class="table-th">Periode</th>
                                <th class="table-th">Nominal</th>
                                <th class="table-th">Sisa</th>
                                <th class="table-th">Jatuh Tempo</th>
                                <th class="table-th">Status</th>
                                <th class="table-th"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($bills as $bill)
                                <tr class="transition-colors hover:bg-slate-50/60">
                                    <td class="table-td font-mono text-xs">{{ $bill->bill_number }}</td>
                                    <td class="table-td">
                                        <p class="font-medium">{{ $bill->student->full_name }}</p>
                                        <p class="font-mono text-xs text-muted">{{ $bill->student->nim }}</p>
                                    </td>
                                    <td class="table-td text-xs">{{ $bill->academicTerm->label() }}</td>
                                    <td class="table-td font-semibold">Rp {{ number_format($bill->amount, 0, ',', '.') }}</td>
                                    <td class="table-td {{ $bill->remainingAmount() > 0 ? 'text-accent-700' : 'text-brand-700' }} font-medium">
                                        Rp {{ number_format($bill->remainingAmount(), 0, ',', '.') }}
                                    </td>
                                    <td class="table-td whitespace-nowrap">{{ $bill->due_date->format('d M Y') }}</td>
                                    <td class="table-td"><x-status-pill :status="$bill->status->value" /></td>
                                    <td class="table-td text-right">
                                        <a href="{{ route('admin.bills.show', $bill) }}" class="btn-ghost !px-3 !py-1.5 text-xs">Detail</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-line p-4">
                    <x-pagination :paginator="$bills" />
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
