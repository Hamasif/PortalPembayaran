@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex items-center justify-between gap-3">
        <p class="text-sm text-muted">
            Menampilkan
            <span class="font-semibold text-ink">{{ $paginator->firstItem() }}</span>–<span class="font-semibold text-ink">{{ $paginator->lastItem() }}</span>
            dari <span class="font-semibold text-ink">{{ $paginator->total() }}</span> data
        </p>

        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-line text-slate-300">
                    <x-lucide-chevron-left class="h-4 w-4" />
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya"
                   class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-line bg-white text-ink hover:bg-slate-50">
                    <x-lucide-chevron-left class="h-4 w-4" />
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inline-flex h-9 min-w-9 items-center justify-center px-1 text-sm text-muted">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg bg-brand-600 px-3 text-sm font-semibold text-white">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-line bg-white px-3 text-sm text-ink hover:bg-slate-50">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya"
                   class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-line bg-white text-ink hover:bg-slate-50">
                    <x-lucide-chevron-right class="h-4 w-4" />
                </a>
            @else
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-line text-slate-300">
                    <x-lucide-chevron-right class="h-4 w-4" />
                </span>
            @endif
        </div>
    </nav>
@endif
