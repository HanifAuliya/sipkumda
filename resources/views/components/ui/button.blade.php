{{--
    Tombol / link bergaya tombol.
    <x-ui.button variant="primary" icon="plus-lg" wire:click="simpan" loading="simpan">Simpan</x-ui.button>
    <x-ui.button href="{{ route('dashboard') }}" variant="outline">Kembali</x-ui.button>

    - variant : primary | navy | success | danger | warning | info | outline | outline-primary | outline-danger | soft-primary | soft-danger | ghost
    - size    : xs | sm | md | lg
    - icon    : nama Bootstrap Icon tanpa awalan "bi-"
    - loading : target wire:loading (nama method/properti) untuk spinner & disable otomatis
    - square  : tombol ikon persegi tanpa teks
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'href' => null,
    'loading' => null,
    'square' => false,
])

@php
    $variants = [
        'primary' => 'bg-primary-500 text-white shadow-soft hover:bg-primary-600 focus-visible:ring-primary-200',
        'navy' => 'bg-navy-900 text-white shadow-soft hover:bg-navy-800 focus-visible:ring-navy-200 dark:bg-navy-700 dark:hover:bg-navy-600',
        'success' => 'bg-emerald-500 text-white shadow-soft hover:bg-emerald-600 focus-visible:ring-emerald-200',
        'danger' => 'bg-rose-500 text-white shadow-soft hover:bg-rose-600 focus-visible:ring-rose-200',
        'warning' => 'bg-accent-500 text-white shadow-soft hover:bg-accent-600 focus-visible:ring-orange-200',
        'info' => 'bg-cyan-500 text-white shadow-soft hover:bg-cyan-600 focus-visible:ring-cyan-200',
        'outline' => 'bg-white text-slate-700 ring-1 ring-slate-200 ring-inset hover:bg-slate-50 focus-visible:ring-primary-200 dark:bg-navy-900 dark:text-slate-200 dark:ring-navy-700 dark:hover:bg-navy-800',
        'outline-primary' => 'bg-white text-primary-600 ring-1 ring-primary-200 ring-inset hover:bg-primary-50 focus-visible:ring-primary-200 dark:bg-transparent dark:text-primary-300 dark:ring-primary-500/40 dark:hover:bg-primary-500/10',
        'outline-danger' => 'bg-white text-rose-600 ring-1 ring-rose-200 ring-inset hover:bg-rose-50 focus-visible:ring-rose-200 dark:bg-transparent dark:text-rose-300 dark:ring-rose-500/40 dark:hover:bg-rose-500/10',
        'soft-primary' => 'bg-primary-50 text-primary-600 hover:bg-primary-100 focus-visible:ring-primary-200 dark:bg-primary-500/10 dark:text-primary-300 dark:hover:bg-primary-500/20',
        'soft-danger' => 'bg-rose-50 text-rose-600 hover:bg-rose-100 focus-visible:ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/20',
        'ghost' => 'text-slate-600 hover:bg-slate-100 focus-visible:ring-slate-200 dark:text-slate-300 dark:hover:bg-navy-800',
    ];

    $sizes = $square
        ? ['xs' => 'size-7 text-xs', 'sm' => 'size-8 text-sm', 'md' => 'size-10 text-base', 'lg' => 'size-12 text-lg']
        : ['xs' => 'px-2.5 py-1 text-xs', 'sm' => 'px-3 py-1.5 text-xs', 'md' => 'px-4 py-2.5 text-sm', 'lg' => 'px-5 py-3 text-base'];

    $classes = implode(' ', [
        'inline-flex shrink-0 items-center justify-center gap-2 rounded-lg font-semibold whitespace-nowrap transition',
        'focus-visible:outline-none focus-visible:ring-3 disabled:cursor-not-allowed disabled:opacity-60',
        $variants[$variant] ?? $variants['primary'],
        $sizes[$size] ?? $sizes['md'],
    ]);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)
            <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $attributes->get('type', 'button') }}"
        @if ($loading) wire:loading.attr="disabled" wire:target="{{ $loading }}" @endif
        {{ $attributes->except('type')->class($classes) }}>
        @if ($loading)
            <x-ui.spinner class="size-4" wire:loading wire:target="{{ $loading }}" />
        @endif
        @if ($icon)
            <i class="bi bi-{{ $icon }}" aria-hidden="true"
                @if ($loading) wire:loading.remove wire:target="{{ $loading }}" @endif></i>
        @endif
        {{ $slot }}
    </button>
@endif
