<x-layouts.admin title="Dashboard">
    <h1 class="text-xl font-bold text-ink">Dashboard Admin</h1>
    <p class="mt-1 text-sm text-muted">Ringkasan keuangan dan aktivitas sistem.</p>

    <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Total Mahasiswa" :value="$stats['students_total']" :hint="$stats['students_active'] . ' mahasiswa aktif'" icon="users" tone="brand" />
        <x-stat-card label="Total Tagihan Diterbitkan" :value="'Rp ' . number_format($stats['bills_total_amount'], 0, ',', '.')" :hint="$stats['bills_total'] . ' tagihan'" icon="receipt" tone="sky" />
        <x-stat-card label="Pembayaran Berhasil" :value="'Rp ' . number_format($stats['payments_success_amount'], 0, ',', '.')" :hint="$stats['payments_success_count'] . ' transaksi terverifikasi'" icon="badge-check" tone="brand" />
        <x-stat-card label="Tagihan Belum Lunas" :value="'Rp ' . number_format($stats['bills_unpaid_amount'], 0, ',', '.')" :hint="$stats['payments_pending_count'] . ' transaksi menunggu'" icon="wallet" tone="accent" />
    </div>

    <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card flex items-center gap-3 p-4">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-accent-100 text-accent-700">
                <x-lucide-user-round-check class="h-5 w-5" />
            </span>
            <div>
                <p class="text-xs text-muted">Akun menunggu verifikasi</p>
                <a href="{{ route('admin.verifications.index') }}" class="text-lg font-bold text-ink hover:text-brand-700">{{ $stats['accounts_pending'] }}</a>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Grafik pemasukan --}}
        <div class="card p-6 lg:col-span-2">
            <h2 class="text-base font-bold text-ink">Pembayaran Terverifikasi &mdash; 6 Bulan Terakhir</h2>
            <div class="mt-4 h-72">
                <canvas data-chart="{{ $revenueChartJson }}" aria-label="Grafik pemasukan 6 bulan terakhir"></canvas>
            </div>
        </div>

        {{-- Distribusi status tagihan --}}
        <div class="card p-6">
            <h2 class="text-base font-bold text-ink">Status Tagihan</h2>
            <div class="mt-4 h-72">
                <canvas data-chart="{{ $billStatusChartJson }}" aria-label="Distribusi status tagihan"></canvas>
            </div>
        </div>
    </div>
</x-layouts.admin>
