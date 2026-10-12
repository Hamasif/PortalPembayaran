<x-layouts.student title="Tagihan Saya">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-ink">Daftar Tagihan</h1>
            <p class="mt-1 text-sm text-muted">Semua tagihan pendidikan Anda per semester.</p>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('student.bills.index') }}" class="card mt-5 grid gap-3 p-4 sm:grid-cols-4">
        <div>
            <label for="status" class="mb-1 block text-xs font-medium text-muted">Status</label>
            <select id="status" name="status" class="input">
                <option value="">Semua status</option>
                @foreach (\App\Enums\BillStatus::labels() as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
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
        <div class="flex items-end sm:col-span-2">
            <button type="submit" class="btn-secondary w-full sm:w-auto">
                <x-lucide-filter class="h-4 w-4" /> Terapkan Filter
            </button>
        </div>
    </form>

    {{-- Daftar tagihan --}}
    <div class="mt-5">
        @if ($bills->isEmpty())
            @if (collect($filters)->filter()->isNotEmpty())
                <x-empty-state icon="search-x" title="Tidak ada hasil"
                    description="Tidak ada tagihan yang cocok dengan filter. Coba ubah kriteria filter.">
                    <a href="{{ route('student.bills.index') }}" class="btn-secondary">Reset Filter</a>
                </x-empty-state>
            @else
                <x-empty-state icon="receipt" title="Belum ada tagihan"
                    description="Tagihan UKT Anda akan tampil di sini setelah diterbitkan bagian keuangan kampus." />
            @endif
        @else
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px]">
                        <thead class="border-b border-line bg-surface">
                            <tr>
                                <th class="table-th">No. Tagihan</th>
                                <th class="table-th">Periode</th>
                                <th class="table-th">Jenis</th>
                                <th class="table-th">Nominal</th>
                                <th class="table-th">Jatuh Tempo</th>
                                <th class="table-th">Status</th>
                                <th class="table-th"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($bills as $bill)
                                <tr class="transition-colors hover:bg-slate-50/60">
                                    <td class="table-td font-mono text-xs">{{ $bill->bill_number }}</td>
                                    <td class="table-td">{{ $bill->academicTerm->label() }}</td>
                                    <td class="table-td">{{ $bill->bill_type->label() }}</td>
                                    <td class="table-td font-semibold">Rp {{ number_format($bill->amount, 0, ',', '.') }}</td>
                                    <td class="table-td">
                                        {{ $bill->due_date->format('d M Y') }}
                                        @if ($bill->isOverdue())
                                            <span class="ml-1 text-xs font-semibold text-red-600">terlewat</span>
                                        @endif
                                    </td>
                                    <td class="table-td"><x-status-pill :status="$bill->status->value" /></td>
                                    <td class="table-td text-right">
                                        <a href="{{ route('student.bills.show', $bill) }}" class="btn-ghost !px-3 !py-1.5 text-xs">
                                            Detail
                                        </a>
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
</x-layouts.student>
