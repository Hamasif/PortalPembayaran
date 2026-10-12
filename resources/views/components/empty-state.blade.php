@props(['icon' => 'inbox', 'title' => 'Belum ada data', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-xl border border-dashed border-line bg-white px-6 py-14 text-center']) }}>
    <span class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
        <x-lucide-{{ $icon }} class="h-7 w-7 text-muted" />
    </span>
    <h3 class="text-base font-semibold text-ink">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1.5 max-w-sm text-sm leading-relaxed text-muted">{{ $description }}</p>
    @endif
    @if (isset($slot) && trim($slot) !== '')
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
