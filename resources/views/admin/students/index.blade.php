<x-layouts.admin title="Data Mahasiswa">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-ink">Data Mahasiswa</h1>
            <p class="mt-1 text-sm text-muted">Data induk resmi — sumber verifikasi akun mahasiswa.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.students.create') }}" class="btn-secondary">
                <x-lucide-user-plus class="h-4 w-4" /> Tambah Manual
            </a>
            <a href="{{ route('admin.students.import') }}" class="btn-secondary">
                <x-lucide-upload class="h-4 w-4" /> Import
            </a>
            <a href="{{ route('admin.bills.create') }}" class="btn-primary">
                <x-lucide-plus class="h-4 w-4" /> Buat Tagihan
            </a>
        </div>
    </div>

    {{-- Filter & pencarian --}}
    <form method="GET" action="{{ route('admin.students.index') }}" class="card mt-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <label for="q" class="mb-1 block text-xs font-medium text-muted">Cari NIM / nama</label>
            <input type="text" id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Ketik untuk mencari…" class="input">
        </div>
        <div>
            <label for="status" class="mb-1 block text-xs font-medium text-muted">Status</label>
            <select id="status" name="status" class="input">
                <option value="">Semua</option>
                @foreach (\App\Enums\StudentStatus::labels() as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
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
        <div class="flex items-end gap-2">
            <button type="submit" class="btn-secondary flex-1">
                <x-lucide-search class="h-4 w-4" /> Cari
            </button>
        </div>
    </form>

    <div class="mt-5">
        @if ($students->isEmpty())
            @if (collect($filters)->filter()->isNotEmpty())
                <x-empty-state icon="search-x" title="Tidak ada hasil"
                    description="Tidak ada mahasiswa yang cocok dengan kriteria pencarian.">
                    <a href="{{ route('admin.students.index') }}" class="btn-secondary">Reset Filter</a>
                </x-empty-state>
            @else
                <x-empty-state icon="users" title="Belum ada data mahasiswa"
                    description="Tambahkan mahasiswa satu per satu secara manual, atau impor data resmi dari Excel/CSV untuk mulai menggunakan portal.">
                    <div class="flex flex-col justify-center gap-2 sm:flex-row">
                        <a href="{{ route('admin.students.create') }}" class="btn-primary">Tambah Manual</a>
                        <a href="{{ route('admin.students.import') }}" class="btn-secondary">Import Data Mahasiswa</a>
                    </div>
                </x-empty-state>
            @endif
        @else
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[820px]">
                        <thead class="border-b border-line bg-surface">
                            <tr>
                                <th class="table-th">NIM</th>
                                <th class="table-th">Nama</th>
                                <th class="table-th">Program Studi</th>
                                <th class="table-th">Angkatan</th>
                                <th class="table-th">Akun</th>
                                <th class="table-th">Status</th>
                                <th class="table-th"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($students as $student)
                                <tr class="transition-colors hover:bg-slate-50/60">
                                    <td class="table-td font-mono text-xs">{{ $student->nim }}</td>
                                    <td class="table-td font-medium">{{ $student->full_name }}</td>
                                    <td class="table-td">{{ $student->program_study }}</td>
                                    <td class="table-td">{{ $student->cohort_year }}</td>
                                    <td class="table-td">
                                        @if ($student->user)
                                            <x-badge tone="green">Terhubung</x-badge>
                                        @else
                                            <x-badge tone="slate">Belum</x-badge>
                                        @endif
                                    </td>
                                    <td class="table-td"><x-status-pill :status="$student->student_status->value" /></td>
                                    <td class="table-td text-right">
                                        <a href="{{ route('admin.students.show', $student) }}" class="btn-ghost !px-3 !py-1.5 text-xs">Detail</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-line p-4">
                    <x-pagination :paginator="$students" />
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
