@props(['status'])

@php
    $map = [
        'unpaid' => ['Belum Lunas', 'accent'],
        'partially_paid' => ['Sebagian Terbayar', 'blue'],
        'paid' => ['Lunas', 'green'],
        'cancelled' => ['Dibatalkan', 'slate'],
        'pending' => ['Menunggu Pembayaran', 'accent'],
        'success' => ['Berhasil', 'green'],
        'failed' => ['Gagal', 'red'],
        'expired' => ['Kedaluwarsa', 'slate'],
        'active' => ['Aktif', 'green'],
        'inactive' => ['Tidak Aktif', 'slate'],
        'graduated' => ['Lulus', 'blue'],
        'pending_verification' => ['Menunggu Verifikasi', 'accent'],
        'blocked' => ['Diblokir', 'red'],
        'upcoming' => ['Akan Datang', 'blue'],
        'closed' => ['Ditutup', 'slate'],
    ];
    [$label, $tone] = $map[$status] ?? [ucfirst(str_replace('_', ' ', $status)), 'neutral'];
@endphp

<x-badge :tone="$tone" {{ $attributes }}>{{ $label }}</x-badge>
