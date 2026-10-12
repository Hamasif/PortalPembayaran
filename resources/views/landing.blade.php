<x-layouts.guest title="Beranda">
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-white">
        <div class="pointer-events-none absolute -top-24 -right-24 h-96 w-96 rounded-full bg-brand-100 blur-3xl"></div>
        <div class="pointer-events-none absolute top-40 -left-32 h-72 w-72 rounded-full bg-accent-100 blur-3xl"></div>

        <div class="relative mx-auto max-w-7xl px-4 pb-20 pt-16 sm:px-6 lg:px-8 lg:pb-28 lg:pt-24">
            {{-- Navbar --}}
            <header class="absolute inset-x-0 top-0">
                <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    <a href="{{ route('landing') }}" class="flex items-center gap-2.5">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-600 text-white shadow-sm">
                            <x-lucide-graduation-cap class="h-5.5 w-5.5" />
                        </span>
                        <span class="flex flex-col leading-tight">
                            <span class="text-base font-bold text-ink">{{ config('app.name') }}</span>
                            <span class="text-[11px] text-muted">Sistem Pembayaran UKT Terintegrasi</span>
                        </span>
                    </a>
                    <nav class="flex items-center gap-2">
                        @auth
                            <a href="{{ url(auth()->user()->hasRole(['super_admin', 'finance_admin']) ? route('admin.dashboard') : route('student.dashboard')) }}" class="btn-primary">
                                <x-lucide-layout-dashboard class="h-4 w-4" /> Buka Dashboard
                            </a>
                        @else
                            <a href="{{ route('login.google') }}" class="btn-secondary hidden sm:inline-flex">
                                <x-lucide-log-in class="h-4 w-4" /> Masuk
                            </a>
                            <a href="{{ route('login.google') }}" class="btn-primary">
                                <x-lucide-log-in class="h-4 w-4" /> Login dengan Google
                            </a>
                        @endauth
                    </nav>
                </div>
            </header>

            <div class="grid items-center gap-12 pt-16 lg:grid-cols-2 lg:pt-20">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-accent-100 px-3.5 py-1.5 text-xs font-semibold text-accent-700 ring-1 ring-inset ring-accent-500/30">
                        <x-lucide-sparkles class="h-3.5 w-3.5" /> Pembayaran UKT resmi via QRIS
                    </span>
                    <h1 class="mt-5 text-4xl font-bold leading-tight tracking-tight text-ink sm:text-5xl">
                        Kelola tagihan kuliah,
                        <span class="text-brand-600">bayar dalam hitungan detik</span>
                    </h1>
                    <p class="mt-5 max-w-xl text-lg leading-relaxed text-muted">
                        Portal Pembayaran Mahasiswa memudahkan Anda memantau tagihan UKT setiap semester,
                        membayar dengan aman menggunakan QRIS, dan menyimpan bukti pembayaran secara digital.
                    </p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        @auth
                            <a href="{{ url(auth()->user()->hasRole(['super_admin', 'finance_admin']) ? route('admin.dashboard') : route('student.dashboard')) }}" class="btn-primary lg">
                                <x-lucide-arrow-right class="h-5 w-5" /> Lanjut ke Dashboard
                            </a>
                        @else
                            <x-google-button :href="route('login.google')" label="Login dengan Google" class="sm:w-auto" />
                        @endauth
                        <a href="#cara-pembayaran" class="btn-secondary lg">
                            <x-lucide-circle-help class="h-5 w-5" /> Cara Pembayaran
                        </a>
                    </div>
                    <p class="mt-4 flex items-center gap-2 text-xs text-muted">
                        <x-lucide-shield-check class="h-4 w-4 text-brand-600" />
                        Hanya akun email resmi kampus yang dapat digunakan.
                    </p>
                </div>

                {{-- Ilustrasi kartu tagihan (statis, tanpa data mahasiswa sungguhan) --}}
                <div class="relative hidden lg:block">
                    <div class="card p-6 shadow-xl">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-muted">Ringkasan Tagihan</p>
                                <p class="mt-1 text-2xl font-bold text-ink">Contoh Tampilan</p>
                            </div>
                            <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                                <x-lucide-wallet class="h-5 w-5" />
                            </span>
                        </div>
                        <div class="mt-5 space-y-3">
                            <div class="flex items-center justify-between rounded-lg border border-line bg-surface px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                                        <x-lucide-receipt class="h-4 w-4" />
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-ink">UKT Semester Ganjil</p>
                                        <p class="text-xs text-muted">Contoh: tagihan semester</p>
                                    </div>
                                </div>
                                <x-badge tone="green">Lunas</x-badge>
                            </div>
                            <div class="flex items-center justify-between rounded-lg border border-line bg-surface px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-accent-100 text-accent-700">
                                        <x-lucide-receipt class="h-4 w-4" />
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-ink">UKT Semester Genap</p>
                                        <p class="text-xs text-muted">Contoh: menunggu pembayaran</p>
                                    </div>
                                </div>
                                <x-badge tone="accent">Belum Lunas</x-badge>
                            </div>
                        </div>
                        <div class="mt-5 flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-3 text-white">
                            <x-lucide-qr-code class="h-5 w-5" />
                            <p class="text-sm font-medium">Bayar dengan QRIS — pindai dari aplikasi bank e-wallet Anda</p>
                        </div>
                    </div>
                    <div class="absolute -bottom-6 -left-6 -z-10 h-full w-full rounded-xl bg-brand-100"></div>
                </div>
            </div>
        </div>
    </section>

    {{-- Manfaat --}}
    <section class="py-16 lg:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-ink">Mengapa menggunakan portal ini?</h2>
                <p class="mt-4 text-lg text-muted">Satu tempat untuk semua kebutuhan pembayaran pendidikan Anda.</p>
            </div>
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['icon' => 'receipt-text', 'title' => 'Tagihan Terpusat', 'desc' => 'Lihat seluruh tagihan UKT Anda per semester lengkap dengan jatuh tempo dan statusnya.'],
                    ['icon' => 'qr-code', 'title' => 'Pembayaran QRIS', 'desc' => 'Bayar kapan saja dari aplikasi bank atau e-wallet favorit Anda melalui QRIS yang aman.'],
                    ['icon' => 'shield-check', 'title' => 'Data Terverifikasi', 'desc' => 'Data tagihan berasal dari bagian keuangan kampus dan dipantau melalui log audit.'],
                    ['icon' => 'file-check-2', 'title' => 'Bukti Pembayaran Digital', 'desc' => 'Unduh atau cetak bukti pembayaran setelah transaksi Anda terverifikasi.'],
                    ['icon' => 'bell-ring', 'title' => 'Pengingat Jatuh Tempo', 'desc' => 'Tagihan yang mendekati tenggat ditandai jelas agar Anda tidak melewatkannya.'],
                    ['icon' => 'lock', 'title' => 'Keamanan Berlapis', 'desc' => 'Login Google resmi kampus, proteksi sesi, dan validasi berlapis di sisi server.'],
                ] as $feature)
                    <div class="card p-6 transition-shadow hover:shadow-md">
                        <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-600 text-white shadow-sm">
                            <x-lucide-{{ $feature['icon'] }} class="h-6 w-6" />
                        </span>
                        <h3 class="mt-5 text-lg font-semibold text-ink">{{ $feature['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted">{{ $feature['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Cara pembayaran --}}
    <section id="cara-pembayaran" class="bg-white py-16 lg:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-ink">Cara melakukan pembayaran</h2>
                <p class="mt-4 text-lg text-muted">Empat langkah sederhana untuk melunasi tagihan Anda.</p>
            </div>
            <ol class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['icon' => 'log-in', 'title' => 'Masuk', 'desc' => 'Login menggunakan akun Google dengan email resmi kampus Anda.'],
                    ['icon' => 'user-check', 'title' => 'Verifikasi Profil', 'desc' => 'Lengkapi NIM dan angkatan pada login pertama untuk pencocokan data resmi.'],
                    ['icon' => 'qr-code', 'title' => 'Pindai QRIS', 'desc' => 'Pilih tagihan, pindai kode QRIS, dan bayar melalui aplikasi bank atau e-wallet.'],
                    ['icon' => 'badge-check', 'title' => 'Terima Bukti', 'desc' => 'Setelah terverifikasi otomatis, bukti pembayaran tersedia di riwayat Anda.'],
                ] as $i => $step)
                    <li class="relative">
                        <div class="card h-full p-6">
                            <div class="flex items-center gap-3">
                                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-600 text-lg font-bold text-white">{{ $i + 1 }}</span>
                                <x-lucide-{{ $step['icon'] }} class="h-6 w-6 text-brand-600" />
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-ink">{{ $step['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-muted">{{ $step['desc'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-16 lg:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-2xl bg-brand-700 px-6 py-14 text-center shadow-xl sm:px-16">
                <div class="pointer-events-none absolute -top-20 -right-20 h-64 w-64 rounded-full bg-white/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-16 -left-16 h-48 w-48 rounded-full bg-accent-500/20 blur-2xl"></div>
                <h2 class="relative text-3xl font-bold text-white sm:text-4xl">Siap menyelesaikan tagihan Anda?</h2>
                <p class="relative mx-auto mt-4 max-w-xl text-lg text-brand-100">
                    Masuk sekarang dan kelola pembayaran pendidikan Anda dengan mudah dan aman.
                </p>
                <div class="relative mt-8">
                    @guest
                        <x-google-button :href="route('login.google')" label="Login dengan Google" class="mx-auto max-w-xs" />
                    @else
                        <a href="{{ url(auth()->user()->hasRole(['super_admin', 'finance_admin']) ? route('admin.dashboard') : route('student.dashboard')) }}" class="btn-accent lg mx-auto">
                            <x-lucide-arrow-right class="h-5 w-5" /> Buka Dashboard
                        </a>
                    @endguest
                </div>
            </div>
        </div>
    </section>
</x-layouts.guest>
