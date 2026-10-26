{{--
    Kotak pesan.
    <x-ui.alert type="info" icon="info-circle" title="Info!">Pesan...</x-ui.alert>
    type: info | success | warning | danger | primary | navy
--}}
@props(['type' => 'info', 'icon' => null, 'title' => null, 'dismissible' => false])

@php
    $types = [
        'primary' => ['bg-primary-50 text-primary-800 ring-primary-200 dark:bg-primary-500/10 dark:text-primary-200 dark:ring-primary-500/30', 'info-circle-fill'],
        'info' => ['bg-cyan-50 text-cyan-800 ring-cyan-200 dark:bg-cyan-500/10 dark:text-cyan-200 dark:ring-cyan-500/30', 'info-circle-fill'],
        'success' => ['bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/30', 'check-circle-fill'],
        'warning' => ['bg-orange-50 text-orange-800 ring-orange-200 dark:bg-orange-500/10 dark:text-orange-200 dark:ring-orange-500/30', 'exclamation-triangle-fill'],
        'danger' => ['bg-rose-50 text-rose-800 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-500/30', 'x-circle-fill'],
        'navy' => ['bg-navy-900 text-white ring-navy-900 dark:bg-navy-800 dark:ring-navy-700', 'info-circle-fill'],
    ];
    [$classes, $defaultIcon] = $types[$type] ?? $types['info'];
@endphp

<div x-data="{ show: true }" x-show="show" x-transition.opacity role="alert"
    {{ $attributes->class(['flex items-start gap-3 rounded-xl px-4 py-3 text-sm ring-1 ring-inset', $classes]) }}>
    <i class="bi bi-{{ $icon ?? $defaultIcon }} mt-0.5 text-base" aria-hidden="true"></i>
    <div class="min-w-0 flex-1">
        @if ($title)
            <span class="font-semibold">{{ $title }}</span>
        @endif
        {{ $slot }}
    </div>
    @if ($dismissible)
        <button type="button" x-on:click="show = false" class="-m-1 rounded p-1 opacity-60 transition hover:opacity-100" aria-label="Tutup">
            <i class="bi bi-x-lg"></i>
        </button>
    @endif
</div>
