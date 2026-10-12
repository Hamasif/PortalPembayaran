@props(['tone' => 'neutral'])

@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-700 ring-brand-600/20',
        'accent' => 'bg-accent-100 text-accent-700 ring-accent-500/30',
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'red' => 'bg-red-50 text-red-700 ring-red-600/20',
        'blue' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-600/20',
        'neutral' => 'bg-slate-100 text-muted ring-slate-500/20',
    ][$tone] ?? 'bg-slate-100 text-muted ring-slate-500/20';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {$tones}"]) }}>
    {{ $slot }}
</span>
