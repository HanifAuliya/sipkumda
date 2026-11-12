{{--
    Judul halaman di bagian atas konten.
    <x-ui.page-header title="Daftar Rancangan" description="Pengajuan Rancangan Produk Hukum" icon="clipboard2-data">
        <x-slot:actions> tombol tambahan... </x-slot:actions>
    </x-ui.page-header>

    - back : URL tombol kembali (default Dashboard; false untuk menyembunyikan)
--}}
@props(['title', 'description' => null, 'icon' => null, 'back' => null])

@php
    $back = $back === false ? null : ($back ?? route('v2.beranda'));
    // Berpindah halaman tanpa memuat ulang, sama seperti menu di sidebar.
    $tautanKembali = new \Illuminate\View\ComponentAttributeBag(['wire:navigate' => '']);
@endphp

<div {{ $attributes->class('mb-6 flex flex-col gap-4 rounded-2xl bg-white p-5 shadow-card sm:flex-row sm:items-center sm:justify-between sm:p-6 dark:bg-navy-900 dark:shadow-none dark:ring-1 dark:ring-white/5') }}>
    <div class="flex min-w-0 items-center gap-3 sm:gap-4">
        @if ($icon)
            <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-primary-50 text-xl text-primary-600 dark:bg-primary-500/15 dark:text-primary-300">
                <i class="bi bi-{{ $icon }}"></i>
            </span>
        @endif
        <div class="min-w-0 flex-1">
            <h1 class="text-lg font-bold sm:text-xl">{{ $title }}</h1>
            @if ($description)
                <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
            @endif
        </div>
        {{-- Di HP cukup ikon di samping judul, supaya isi halaman lebih cepat terlihat. --}}
        @if ($back)
            <x-ui.button :href="$back" variant="ghost" size="sm" square icon="arrow-left" title="Kembali" aria-label="Kembali" class="sm:hidden" :attributes="$tautanKembali" />
        @endif
    </div>

    <div @class(['flex flex-wrap items-center gap-2 xl:shrink-0', 'max-sm:hidden' => ! isset($actions)])>
        {{ $actions ?? '' }}
        @if ($back)
            <x-ui.button :href="$back" variant="ghost" icon="arrow-left" class="max-sm:hidden" :attributes="$tautanKembali">Kembali</x-ui.button>
        @endif
    </div>
</div>
