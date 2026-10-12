@props(['title' => config('app.name')])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $title) — {{ config('app.name') }}</title>
    <meta name="description" content="Portal pembayaran UKT mahasiswa: kelola tagihan, bayar dengan QRIS, dan unduh bukti pembayaran.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    @include('partials.toasts')

    {{-- Konten halaman --}}
    {{ $slot }}

    <footer class="border-t border-line bg-white">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-6 text-sm text-muted sm:flex-row sm:px-6 lg:px-8">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Seluruh hak cipta.</p>
            <p>Dikembangkan untuk kemudahan layanan pembayaran pendidikan.</p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
