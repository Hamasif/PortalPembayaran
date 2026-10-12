{{-- Wadah toast + flash session --}}
@php
    $flashType = session('status_type');
    $flashMessage = session('status_message') ?? ($flashType === 'error' ? session('error') : session('status'));
@endphp

<div id="flash-data" data-type="{{ $flashType }}" data-message="{{ $flashMessage }}" class="hidden"></div>

<div id="toast-container" class="pointer-events-none fixed bottom-4 right-4 z-[100] flex w-full max-w-sm flex-col gap-2 px-4 sm:px-0" aria-live="polite"></div>
