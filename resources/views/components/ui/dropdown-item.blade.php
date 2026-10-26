@props(['icon' => null, 'href' => null, 'danger' => false])

@php
    $classes = 'flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm transition '
        . ($danger
            ? 'text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-500/10'
            : 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-navy-700');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)<i class="bi bi-{{ $icon }} text-base opacity-70"></i>@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $attributes->get('type', 'button') }}" {{ $attributes->except('type')->class($classes) }}>
        @if ($icon)<i class="bi bi-{{ $icon }} text-base opacity-70"></i>@endif
        {{ $slot }}
    </button>
@endif
