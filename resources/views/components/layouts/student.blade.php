@props(['title' => config('app.name')])

@php
    $user = auth()->user();
    $student = $user?->student;

    $navItems = [
        ['route' => 'student.dashboard', 'label' => 'Dashboard', 'icon' => 'layout-dashboard', 'match' => 'student.dashboard'],
        ['route' => 'student.bills.index', 'label' => 'Tagihan', 'icon' => 'receipt', 'match' => 'student.bills.*'],
        ['route' => 'student.payments.history', 'label' => 'Riwayat', 'icon' => 'history', 'match' => 'student.payments.*'],
        ['route' => 'student.profile.edit', 'label' => 'Profil', 'icon' => 'user-round', 'match' => 'student.profile.*'],
    ];
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $title) — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    @include('partials.toasts')

    {{-- Navbar atas --}}
    <header class="sticky top-0 z-40 border-b border-line bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
        <div class="mx-auto flex h-16 max-w-7xl items-center gap-6 px-4 sm:px-6 lg:px-8">
            {{-- Brand --}}
            <a href="{{ route('student.dashboard') }}" class="flex shrink-0 items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-600 text-white shadow-sm">
                    <x-lucide-graduation-cap class="h-5 w-5" />
                </span>
                <span class="hidden flex-col leading-tight sm:flex">
                    <span class="text-[15px] font-bold text-ink">{{ config('app.name') }}</span>
                    <span class="text-[10px] font-medium text-muted">Portal Mahasiswa</span>
                </span>
            </a>

            {{-- Navigasi desktop --}}
            <nav class="hidden items-center gap-1.5 md:flex">
                @foreach ($navItems as $item)
                    @php $active = request()->routeIs($item['match']); @endphp
                    <a href="{{ route($item['route']) }}"
                       class="tab-item {{ $active ? 'tab-item-active' : '' }}"
                       @if ($active) aria-current="page" @endif>
                        <x-lucide-{{ $item['icon'] }} class="h-4 w-4" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="flex-1"></div>

            {{-- Chip akun --}}
            <div class="relative shrink-0" data-dropdown>
                <button type="button" data-dropdown-trigger class="account-trigger"
                        aria-label="Menu akun">
                    @if ($user?->avatar)
                        <img src="{{ $user->avatar }}" alt="Foto profil" class="h-8 w-8 rounded-full object-cover ring-2 ring-brand-100">
                    @else
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white">
                            {{ mb_substr($user?->name ?? 'M', 0, 1) }}
                        </span>
                    @endif
                    <span class="hidden max-w-[140px] truncate text-[13px] font-semibold text-ink sm:block">{{ $user?->name }}</span>
                    <x-lucide-chevron-down class="h-4 w-4 shrink-0 text-muted" />
                </button>

                <div data-dropdown-menu class="absolute right-0 top-full z-50 mt-2 hidden w-60 overflow-hidden rounded-xl border border-line bg-white shadow-lg">
                    <div class="border-b border-line px-4 py-3">
                        <p class="truncate text-sm font-semibold text-ink">{{ $user?->name }}</p>
                        <p class="truncate text-xs text-muted">{{ $user?->email }}</p>
                        @if ($student)
                            <p class="mt-1.5 inline-flex items-center rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-semibold text-brand-700">
                                {{ $student->nim }} · {{ $student->program_study }}
                            </p>
                        @endif
                    </div>
                    <div class="p-1.5">
                        <a href="{{ route('student.profile.edit') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-ink hover:bg-slate-50">
                            <x-lucide-user-round class="h-4 w-4 text-muted" /> Profil Saya
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-red-600 hover:bg-red-50">
                                <x-lucide-log-out class="h-4 w-4" /> Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Navigasi mobile --}}
        <nav class="flex gap-1 overflow-x-auto border-t border-line px-3 py-2 md:hidden">
            @foreach ($navItems as $item)
                @php $active = request()->routeIs($item['match']); @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex shrink-0 items-center gap-1.5 rounded-full px-3.5 py-1.5 text-[13px] font-medium {{ $active ? 'bg-brand-600 text-white' : 'text-muted' }}">
                    <x-lucide-{{ $item['icon'] }} class="h-4 w-4" />
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
    </header>

    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>

    <footer class="border-t border-line bg-white">
        <div class="mx-auto max-w-7xl px-4 py-5 text-center text-sm text-muted sm:px-6 lg:px-8">
            &copy; {{ date('Y') }} {{ config('app.name') }}
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
