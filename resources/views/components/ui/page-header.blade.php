{{--
    Judul halaman di bagian atas konten.
    <x-ui.page-header title="Daftar Rancangan" description="Pengajuan Rancangan Produk Hukum" icon="clipboard2-data">
        <x-slot:actions> tombol tambahan... </x-slot:actions>
    </x-ui.page-header>

    - back : URL tombol kembali (default dashboard; false untuk menyembunyikan)
--}}
@props(['title', 'description' => null, 'icon' => null, 'back' => null])

@php
    $back = $back === false ? null : ($back ?? route('dashboard'));
@endphp

<div {{ $attributes->class('mb-6 flex flex-col gap-4 rounded-2xl bg-white p-5 shadow-card sm:flex-row sm:items-center sm:justify-between sm:p-6 dark:bg-navy-900 dark:shadow-none dark:ring-1 dark:ring-white/5') }}>
    <div class="flex min-w-0 items-center gap-4">
        @if ($icon)
            <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-linear-to-br from-primary-500 to-primary-700 text-xl text-white shadow-soft">
                <i class="bi bi-{{ $icon }}"></i>
            </span>
        @endif
        <div class="min-w-0">
            <h1 class="text-lg font-bold sm:text-xl">{{ $title }}</h1>
            @if ($description)
                <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
            @endif
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        {{ $actions ?? '' }}
        @if ($back)
            <x-ui.button :href="$back" variant="outline-primary" icon="arrow-left">Kembali</x-ui.button>
        @endif
    </div>
</div>
