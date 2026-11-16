{{--
    Tombol kirim form non-Livewire dengan status loading.
    Letakkan di dalam <form x-data="{ loading: false }" x-on:submit="loading = true">.
--}}
@props(['loadingText' => 'Memproses...'])

<button type="submit" x-bind:disabled="loading"
    {{ $attributes->class('inline-flex w-full items-center justify-center gap-2 rounded-lg bg-navy-900 px-4 py-3 text-sm font-semibold text-white shadow-soft transition hover:bg-navy-800 focus-visible:ring-3 focus-visible:ring-navy-200 focus-visible:outline-none disabled:opacity-70 dark:bg-primary-500 dark:hover:bg-primary-600') }}>
    <x-ui.spinner class="size-4" x-show="loading" x-cloak />
    <span x-show="!loading">{{ $slot }}</span>
    <span x-show="loading" x-cloak>{{ $loadingText }}</span>
</button>
