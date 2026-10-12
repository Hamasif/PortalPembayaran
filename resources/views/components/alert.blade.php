@props(['type' => 'success', 'dismissible' => false])

@php
    $styles = [
        'success' => ['border-brand-200 bg-brand-50 text-brand-800', 'check-circle'],
        'error' => ['border-red-200 bg-red-50 text-red-800', 'alert-circle'],
        'warning' => ['border-accent-200 bg-accent-100 text-accent-700', 'alert-triangle'],
        'info' => ['border-sky-200 bg-sky-50 text-sky-800', 'info'],
    ][$type] ?? ['border-line bg-slate-50 text-ink', 'info'];
@endphp

<div {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-lg border px-4 py-3 text-sm {$styles[0]}"]) }} role="alert">
    <x-lucide-{{ $styles[1] }} class="mt-0.5 h-4 w-4 shrink-0" />
    <div class="flex-1 leading-relaxed">{{ $slot }}</div>
</div>
