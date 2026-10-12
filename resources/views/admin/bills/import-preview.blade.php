<x-layouts.admin title="Pratinjau Import Tagihan">
    <a href="{{ route('admin.bills.import') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-muted transition hover:text-ink">
        <x-lucide-arrow-left class="h-4 w-4" /> Batalkan & pilih berkas lain
    </a>

    <div class="card p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-bold text-ink">Pratinjau Import Tagihan</h1>
                <p class="mt-1 text-sm text-muted">
                    {{ $batch->original_filename }} — {{ $batch->total_rows }} baris terdeteksi.
                    <strong>Data belum tersimpan.</strong>
                </p>
            </div>
            <div class="flex gap-2">
                <x-badge tone="green">{{ $batch->success_count }} valid</x-badge>
                <x-badge tone="red">{{ $batch->failed_count }} bermasalah</x-badge>
            </div>
        </div>

        <div class="mt-5 overflow-x-auto rounded-lg border border-line">
            <table class="w-full min-w-[860px]">
                <thead class="border-b border-line bg-surface">
                    <tr>
                        <th class="table-th">#</th>
                        <th class="table-th">NIM</th>
                        <th class="table-th">Tahun Akademik</th>
                        <th class="table-th">Semester</th>
                        <th class="table-th">Jenis</th>
                        <th class="table-th">Nominal</th>
                        <th class="table-th">Jatuh Tempo</th>
                        <th class="table-th">Validasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($rows as $row)
                        <tr class="{{ $row['valid'] ? '' : 'bg-red-50/50' }}">
                            <td class="table-td text-xs text-muted">{{ $row['row_number'] }}</td>
                            <td class="table-td font-mono text-xs">{{ $row['data']['nim'] }}</td>
                            <td class="table-td text-xs">{{ $row['data']['tahun_akademik'] }}</td>
                            <td class="table-td text-xs">{{ ucfirst($row['data']['semester']) }}</td>
                            <td class="table-td text-xs">{{ $row['data']['jenis'] }}</td>
                            <td class="table-td font-semibold">Rp {{ number_format(is_numeric($row['data']['nominal']) ? (int) $row['data']['nominal'] : 0, 0, ',', '.') }}</td>
                            <td class="table-td text-xs">{{ $row['data']['jatuh_tempo'] }}</td>
                            <td class="table-td">
                                @if ($row['valid'])
                                    <x-badge tone="green">Valid</x-badge>
                                @else
                                    <span class="text-xs font-semibold text-red-600">{{ implode(' ', $row['errors']) }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('admin.bills.import.commit', $batch) }}" class="mt-6" data-confirm="Konfirmasi import tagihan?">
            @csrf
            <div class="flex flex-col gap-2 sm:flex-row">
                <button type="submit" class="btn-primary" @disabled($batch->success_count === 0)>
                    <x-lucide-check class="h-4 w-4" /> Konfirmasi Import {{ $batch->success_count }} Tagihan
                </button>
                <a href="{{ route('admin.bills.import') }}" class="btn-secondary">Batalkan</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
