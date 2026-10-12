@props(['name' => null, 'label' => null, 'hint' => null, 'required' => false])

@if ($label)
    <label for="{{ $name }}" class="label">
        {{ $label }}
        @if ($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
@endif

{{ $slot }}

@if ($hint)
    <p class="mt-1.5 text-xs text-muted">{{ $hint }}</p>
@endif

@error($name)
    <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600">
        <x-lucide-alert-circle class="h-3.5 w-3.5" /> {{ $message }}
    </p>
@enderror
