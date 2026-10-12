<x-layouts.admin title="Detail Mahasiswa">
    <a href="{{ route('admin.students.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-muted transition hover:text-ink">
        <x-lucide-arrow-left class="h-4 w-4" /> Kembali ke daftar mahasiswa
    </a>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card p-6 lg:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-lg font-bold text-ink">{{ $student->full_name }}</h1>
                    <p class="mt-1 font-mono text-sm text-muted">{{ $student->nim }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <x-status-pill :status="$student->student_status->value" />
                    <form method="POST" action="{{ route('admin.students.toggle', $student) }}" data-confirm="Ubah status mahasiswa ini?">
                        @csrf
                        <button type="submit" class="btn-secondary !px-3 !py-1.5 text-xs">
                            {{ $student->student_status->value === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                    </form>
                    <a href="{{ route('admin.students.edit', $student) }}" class="btn-secondary !px-3 !py-1.5 text-xs">
                        <x-lucide-pencil class="h-3.5 w-3.5" /> Ubah
                    </a>
                </div>
            </div>

            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-line bg-surface px-4 py-3">
                    <dt class="text-xs text-muted">Program studi</dt>
                    <dd class="mt-1 text-sm font-semibold text-ink">{{ $student->program_study }}</dd>
                </div>
                <div class="rounded-lg border border-line bg-surface px-4 py-3">
                    <dt class="text-xs text-muted">Angkatan</dt>
                    <dd class="mt-1 text-sm font-semibold text-ink">{{ $student->cohort_year }}</dd>
                </div>
                <div class="rounded-lg border border-line bg-surface px-4 py-3">
                    <dt class="text-xs text-muted">Email data induk</dt>
                    <dd class="mt-1 truncate text-sm font-semibold text-ink">{{ $student->email ?? '—' }}</dd>
                </div>
                <div class="rounded-lg border border-line bg-surface px-4 py-3">
                    <dt class="text-xs text-muted">Akun Google terhubung</dt>
                    <dd class="mt-1 text-sm font-semibold text-ink">
                        @if ($student->user)
                            {{ $student->user->email }}
                        @else
                            <span class="text-muted">Belum ada</span>
                        @endif
                    </dd>
                </div>
            </dl>

            <div class="mt-6 grid grid-cols-3 gap-3 text-center">
                <div class="rounded-lg bg-slate-50 px-3 py-3">
                    <p class="text-xs text-muted">Diterbitkan</p>
                    <p class="mt-1 font-bold text-ink">Rp {{ number_format($totalBilled, 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg bg-brand-50 px-3 py-3">
                    <p class="text-xs text-brand-700">Terbayar</p>
                    <p class="mt-1 font-bold text-brand-700">Rp {{ number_format($totalPaid, 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg bg-accent-50 px-3 py-3">
                    <p class="text-xs text-accent-700">Sisa</p>
                    <p class="mt-1 font-bold text-accent-700">Rp {{ number_format(max(0, $totalBilled - $totalPaid), 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="mt-6 flex gap-2">
                <a href="{{ route('admin.bills.create', ['student_id' => $student->id]) }}" class="btn-primary !px-4 !py-2 text-xs">
                    <x-lucide-plus class="h-4 w-4" /> Buat Tagihan untuk Mahasiswa Ini
                </a>
            </div>
        </div>

        {{-- Riwayat tagihan --}}
        <div class="card p-6">
            <h2 class="text-sm font-bold text-ink">Riwayat Tagihan</h2>
            @if ($student->bills->isEmpty())
                <x-empty-state icon="receipt" title="Belum ada tagihan" class="mt-4 !px-4 !py-8" />
            @else
                <ul class="mt-4 space-y-3">
                    @foreach ($student->bills as $bill)
                        <li>
                            <a href="{{ route('admin.bills.show', $bill) }}" class="block rounded-lg border border-line px-4 py-3 transition hover:border-brand-600/40 hover:bg-brand-50/40">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-semibold text-ink">{{ $bill->academicTerm->label() }}</span>
                                    <x-status-pill :status="$bill->status->value" />
                                </div>
                                <p class="mt-1 font-mono text-[11px] text-muted">{{ $bill->bill_number }}</p>
                                <p class="mt-1 text-sm font-medium text-ink">Rp {{ number_format($bill->amount, 0, ',', '.') }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-layouts.admin>
