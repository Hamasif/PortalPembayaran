@props(['title' => config('app.name')])

@php
    $user = auth()->user();

    $menuGroups = [
        [
            'label' => 'Utama',
            'items' => [
                ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'layout-dashboard', 'match' => 'admin.dashboard'],
            ],
        ],
        [
            'label' => 'Manajemen',
            'items' => [
                ['route' => 'admin.students.index', 'label' => 'Data Mahasiswa', 'icon' => 'users', 'match' => 'admin.students.index|admin.students.show|admin.students.edit|admin.students.import.*'],
                ['route' => 'admin.verifications.index', 'label' => 'Verifikasi Akun', 'icon' => 'user-check', 'match' => 'admin.verifications.*', 'badge' => $pendingVerificationCount ?? 0],
                ['route' => 'admin.bills.index', 'label' => 'Tagihan UKT', 'icon' => 'receipt', 'match' => 'admin.bills.*'],
            ],
        ],
        [
            'label' => 'Keuangan',
            'items' => [
                ['route' => 'admin.transactions.index', 'label' => 'Transaksi', 'icon' => 'credit-card', 'match' => 'admin.transactions.*'],
                ['route' => 'admin.reports.index', 'label' => 'Laporan', 'icon' => 'file-chart-column', 'match' => 'admin.reports.*'],
            ],
        ],
        [
            'label' => 'Sistem',
            'items' => [
                ['route' => 'admin.audit-logs.index', 'label' => 'Audit Log', 'icon' => 'scroll-text', 'match' => 'admin.audit-logs.*'],
                ...($user?->isSuperAdmin() ? [
                    ['route' => 'admin.settings.domains', 'label' => 'Pengaturan Domain', 'icon' => 'settings', 'match' => 'admin.settings.*'],
                ] : []),
            ],
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $title) — Admin {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    @include('partials.toasts')

    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <div id="sidebar-backdrop" data-sidebar-backdrop class="fixed inset-0 z-30 hidden bg-ink/50 lg:hidden"></div>

        <aside id="admin-sidebar"
               class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-brand-700 shadow-xl transition-transform duration-200 lg:translate-x-0">
            {{-- Logo --}}
            <div class="flex h-16 shrink-0 items-center gap-2.5 border-b border-white/10 px-5">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-brand-700">
                    <x-lucide-graduation-cap class="h-5 w-5" />
                </span>
                <div class="flex flex-col leading-tight">
                    <span class="text-sm font-bold text-white">{{ config('app.name') }}</span>
                    <span class="text-[10px] tracking-wider text-brand-200/80 uppercase">Panel Admin</span>
                </div>
            </div>

            {{-- Navigasi --}}
            <nav class="sidebar-scroll flex-1 space-y-1 overflow-y-auto px-3 py-3">
                @foreach ($menuGroups as $groupIndex => $group)
                    @if ($groupIndex > 0)
                        <div class="mx-3.5 my-4 border-t border-white/10"></div>
                    @endif
                    <p class="nav-label">{{ $group['label'] }}</p>

                    @foreach ($group['items'] as $item)
                        @php
                            $patterns = explode('|', $item['match']);
                            $active = collect($patterns)->contains(fn ($p) => request()->routeIs($p));
                        @endphp
                        <a href="{{ route($item['route']) }}"
                           class="nav-item {{ $active ? 'nav-item-active' : '' }}"
                           @if ($active) aria-current="page" @endif>
                            <x-lucide-{{ $item['icon'] }} class="nav-icon h-[18px] w-[18px] shrink-0 {{ $active ? '' : 'text-brand-200/70' }}" />
                            <span class="flex-1 whitespace-nowrap leading-snug">{{ $item['label'] }}</span>

                            @if (isset($item['badge']) && $item['badge'] > 0)
                                <span class="ml-auto shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $active ? 'bg-accent-500 text-ink' : 'bg-accent-400/90 text-ink' }}">
                                    {{ $item['badge'] }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                @endforeach
            </nav>

            {{-- Akun --}}
            <div class="shrink-0 border-t border-white/10 p-3">
                <div class="flex items-center gap-3 rounded-lg bg-white/5 px-3 py-2.5">
                    @if ($user?->avatar)
                        <img src="{{ $user->avatar }}" alt="Foto profil" class="h-9 w-9 rounded-full ring-2 ring-white/20">
                    @else
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-sm font-bold text-white">
                            {{ mb_substr($user?->name ?? 'A', 0, 1) }}
                        </span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[13px] font-semibold text-white">{{ $user?->name }}</p>
                        <p class="truncate text-[10px] text-brand-200">{{ $user?->role?->label() }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg px-3 py-2 text-[13px] font-medium text-brand-100/80 transition hover:bg-white/10 hover:text-white">
                        <x-lucide-log-out class="h-4 w-4" /> Keluar
                    </button>
                </form>
            </div>
        </aside>

        {{-- Konten --}}
        <div class="flex min-h-screen flex-1 flex-col lg:pl-64">
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-line bg-white/90 px-4 backdrop-blur sm:px-6">
                <button type="button" data-sidebar-toggle class="rounded-lg p-2 text-muted hover:bg-slate-100 lg:hidden" aria-label="Buka menu">
                    <x-lucide-menu class="h-5 w-5" />
                </button>
                <div class="flex-1"></div>
                <a href="{{ route('landing') }}" class="hidden items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-muted transition hover:bg-slate-50 hover:text-ink sm:flex">
                    <x-lucide-home class="h-4 w-4" /> Beranda
                </a>
            </header>

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>

            <footer class="border-t border-line px-6 py-4 text-center text-xs text-muted">
                &copy; {{ date('Y') }} {{ config('app.name') }} — Panel Admin
            </footer>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
