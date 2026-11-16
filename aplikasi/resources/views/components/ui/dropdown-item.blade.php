{{-- description: penjelasan singkat di bawah label, untuk pilihan yang perlu diterangkan. --}}
@props(['icon' => null, 'href' => null, 'danger' => false, 'description' => null])

@php
    $classes = 'group flex min-h-10 w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm transition active:scale-[0.98] '
        . ($danger
            ? 'text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-500/10'
            : 'text-slate-700 hover:bg-primary-50 hover:text-primary-700 dark:text-slate-200 dark:hover:bg-primary-500/15 dark:hover:text-white');
    $ikon = 'bi bi-' . $icon . ' text-base opacity-70 transition-[opacity,translate] duration-150 group-hover:translate-x-0.5 group-hover:opacity-100';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)<i class="{{ $ikon }}" aria-hidden="true"></i>@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $attributes->get('type', 'button') }}" {{ $attributes->except('type')->class($classes) }}>
        @if ($icon)<i @class([$ikon, 'mt-0.5 self-start' => $description]) aria-hidden="true"></i>@endif
        @if ($description)
            <span class="min-w-0">
                <span class="block font-semibold">{{ $slot }}</span>
                <span class="mt-0.5 block text-slate-500 dark:text-slate-400">{{ $description }}</span>
            </span>
        @else
            {{ $slot }}
        @endif
    </button>
@endif
