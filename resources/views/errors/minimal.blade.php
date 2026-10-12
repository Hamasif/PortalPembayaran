<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} — {{ $title }} | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-surface px-4">
    <div class="w-full max-w-md text-center">
        <a href="{{ route('landing') }}" class="inline-flex items-center gap-2.5">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-600 text-white shadow-sm">
                <x-lucide-graduation-cap class="h-6 w-6" />
            </span>
            <span class="text-lg font-bold text-ink">{{ config('app.name') }}</span>
        </a>

        <div class="card mt-8 p-8 sm:p-10">
            <p class="text-6xl font-bold tracking-tight text-brand-600">{{ $code }}</p>
            <h1 class="mt-4 text-xl font-bold text-ink">{{ $title }}</h1>
            <p class="mt-3 text-sm leading-relaxed text-muted">@yield('description')</p>
            <div class="mt-8 flex flex-col gap-2.5 sm:flex-row sm:justify-center">
                <a href="{{ route('landing') }}" class="btn-primary">
                    <x-lucide-home class="h-4 w-4" /> Kembali ke Beranda
                </a>
                @guest
                    <a href="{{ route('login.google') }}" class="btn-secondary">Masuk</a>
                @endguest
            </div>
        </div>

        <p class="mt-6 text-xs text-muted">
            Jika Anda yakin ini adalah kesalahan sistem, hubungi bagian TI fakultas/universitas Anda.
        </p>
    </div>
</body>
</html>
