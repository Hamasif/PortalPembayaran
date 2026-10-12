<x-layouts.admin title="Audit Log">
    <div>
        <h1 class="text-xl font-bold text-ink">Audit Log</h1>
        <p class="mt-1 text-sm text-muted">Riwayat perubahan data penting & tindakan admin — untuk keperluan ketertelusuran.</p>
    </div>

    <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="card mt-5 grid gap-3 p-4 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <label for="q" class="mb-1 block text-xs font-medium text-muted">Cari entitas / email aktor</label>
            <input type="text" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="input">
        </div>
        <div class="flex items-end gap-2">
            <select id="action" name="action" class="input">
                <option value="">Semua aksi</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-secondary shrink-0">
                <x-lucide-search class="h-4 w-4" />
            </button>
        </div>
    </form>

    <div class="mt-5">
        @if ($logs->isEmpty())
            <x-empty-state icon="scroll-text" title="Belum ada aktivitas tercatat" description="Setiap tindakan penting akan tercatat di sini." />
        @else
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[820px]">
                        <thead class="border-b border-line bg-surface">
                            <tr>
                                <th class="table-th">Waktu</th>
                                <th class="table-th">Aktor</th>
                                <th class="table-th">Aksi</th>
                                <th class="table-th">Entitas</th>
                                <th class="table-th">Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($logs as $log)
                                <tr class="transition-colors hover:bg-slate-50/60">
                                    <td class="table-td whitespace-nowrap text-xs">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                                    <td class="table-td text-xs">{{ $log->actor?->email ?? 'sistem' }}</td>
                                    <td class="table-td">
                                        <span class="rounded-md bg-slate-100 px-2 py-1 font-mono text-[11px] font-semibold text-ink">{{ $log->action }}</span>
                                    </td>
                                    <td class="table-td text-xs">
                                        {{ $log->entity_type }}@if($log->entity_id) #{{ $log->entity_id }} @endif
                                    </td>
                                    <td class="table-td max-w-[320px]">
                                        <p class="truncate text-xs text-muted">
                                            {{ \Illuminate\Support\Str::limit(json_encode($log->metadata, JSON_UNESCAPED_UNICODE) ?? '—', 80) }}
                                        </p>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-line p-4">
                    <x-pagination :paginator="$logs" />
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
