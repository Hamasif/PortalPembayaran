@props(['variant' => 'primary', 'size' => 'md', 'type' => 'button'])

@php
    $classes = [
        'primary' => 'btn-primary',
        'secondary' => 'btn-secondary',
        'danger' => 'btn-danger',
        'ghost' => 'btn-ghost',
        'accent' => 'btn-accent',
    ][$variant] ?? 'btn-primary';

    $sizes = ['sm' => 'px-3 py-2 text-xs', 'md' => '', 'lg' => 'px-6 py-3 text-base'][$size] ?? '';
@endphp

<button {{ $attributes->merge(['type' => $type, 'class' => trim($classes.' '.$sizes)]) }}>
    {{ $slot }}
</button>
