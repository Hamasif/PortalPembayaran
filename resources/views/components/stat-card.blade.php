@props(['label', 'value', 'icon' => 'bar-chart-3', 'tone' => 'brand', 'hint' => null])

@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-700',
        'accent' => 'bg-accent-100 text-accent-700',
        'red' => 'bg-red-50 text-red-600',
        'sky' => 'bg-sky-50 text-sky-600',
        'slate' => 'bg-slate-100 text-slate-600',
    ][$tone] ?? 'bg-brand-50 text-brand-700';
@endphp

<div {{ $attributes->merge(['class' => 'card p-5']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm font-medium text-muted">{{ $label }}</p>
            <p class="mt-2 truncate text-2xl font-bold tracking-tight text-ink">{{ $value }}</p>
            @if ($hint)
                <p class="mt-1.5 text-xs text-muted">{{ $hint }}</p>
            @endif
        </div>
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg {{ $tones }}">
            <x-lucide-{{ $icon }} class="h-5 w-5" />
        </span>
    </div>
</div>
