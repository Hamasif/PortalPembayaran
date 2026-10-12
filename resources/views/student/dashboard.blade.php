<x-layouts.student title="Dashboard">
    {{-- Sapaan --}}
    <div class="card flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            @if (auth()->user()->avatar)
                <img src="{{ auth()->user()->avatar }}" alt="Foto profil" class="h-14 w-14 rounded-full border border-line object-cover">
            @else
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-600 text-xl font-bold text-white">
                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                </span>
            @endif
            <div>
                <h1 class="text-xl font-bold text-ink">Halo, {{ $student->full_name }} 👋</h1>
                <p class="mt-1 text-sm text-muted">
                    {{ $student->nim }} · {{ $student->program_study }} · Angkatan {{ $student->cohort_year }}
                </p>
            </div>
        </div>
        <a href="{{ route('student.bills.index') }}" class="btn-primary shrink-0">
            <x-lucide-receipt class="h-4 w-4" /> Lihat Semua Tagihan
        </a>
    </div>

    {{-- Ringkasan --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-stat-card label="Total Tagihan" :value="'Rp ' . number_format($totalBilled, 0, ',', '.')" icon="receipt" tone="slate" />
        <x-stat-card label="Total Terbayar" :value="'Rp ' . number_format($totalPaid, 0, ',', '.')" icon="check-circle-2" tone="brand" />
        <x-stat-card label="Sisa Tagihan" :value="'Rp ' . number_format($outstanding, 0, ',', '.')" icon="wallet" :tone="$outstanding > 0 ? 'accent' : 'brand'" />
    </div>

    {{-- Tagihan mendekati tenggat --}}
    @if ($nearDue->isNotEmpty())
        <div class="mt-6">
            <div class="mb-3 flex items-center gap-2">
                <x-lucide-alarm-clock class="h-5 w-5 text-accent-600" />
                <h2 class="text-lg font-bold text-ink">Mendekati Tenggat</h2>
            </div>
            <div class="space-y-3">
                @foreach ($nearDue->take(3) as $bill)
                    <div class="card flex flex-col gap-3 border-l-4 border-l-accent-500 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-ink">{{ $bill->description ?? 'Tagihan UKT' }}</p>
                            <p class="mt-0.5 text-xs text-muted">
                                {{ $bill->bill_number }} · Jatuh tempo {{ $bill->due_date->format('d M Y') }}
                                @if ($bill->daysUntilDue() < 0)
                                    <span class="font-semibold text-red-600">(terlewat {{ abs($bill->daysUntilDue()) }} hari)</span>
                                @elseif ($bill->daysUntilDue() <= 3)
                                    <span class="font-semibold text-accent-700">(tinggal {{ $bill->daysUntilDue() }} hari)</span>
                                @endif
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <span class="text-sm font-bold text-ink">Rp {{ number_format($bill->remainingAmount(), 0, ',', '.') }}</span>
                            <a href="{{ route('student.bills.show', $bill) }}" class="btn-secondary">
                                Bayar <x-lucide-arrow-right class="h-4 w-4" />
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-5">
        {{-- Grafik per semester --}}
        <div class="card p-6 lg:col-span-3">
            <h2 class="text-base font-bold text-ink">Tagihan per Semester</h2>
            @unless ($hasBills)
                <x-empty-state icon="bar-chart-3" title="Belum ada tagihan"
                    description="Grafik akan tampil setelah tagihan UKT Anda diterbitkan oleh bagian keuangan." class="mt-4" />
            @else
                <div class="mt-4 h-64">
                    <canvas data-chart="{{ $chartJson }}" aria-label="Grafik tagihan per semester"></canvas>
                </div>
            @endunless
        </div>

        {{-- Tagihan terbaru --}}
        <div class="card p-6 lg:col-span-2">
            <h2 class="text-base font-bold text-ink">Tagihan Terbaru</h2>
            @if ($recentBills->isEmpty())
                <x-empty-state icon="receipt" title="Belum ada tagihan"
                    description="Tagihan UKT Anda akan muncul di sini." class="mt-4" />
            @else
                <ul class="mt-4 divide-y divide-line">
                    @foreach ($recentBills as $bill)
                        <li class="py-3 first:pt-0 last:pb-0">
                            <a href="{{ route('student.bills.show', $bill) }}" class="group flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-ink group-hover:text-brand-700">
                                        {{ $bill->academicTerm->label() }}
                                    </p>
                                    <p class="text-xs text-muted">Jatuh tempo {{ $bill->due_date->format('d M Y') }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-sm font-semibold text-ink">Rp {{ number_format($bill->remainingAmount(), 0, ',', '.') }}</p>
                                    <x-status-pill :status="$bill->status->value" class="mt-1" />
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-layouts.student>
