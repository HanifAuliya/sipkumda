{{--
    Detail lengkap rancangan dalam dua kolom: informasi utama & hasil penelitian.
    Dipakai di dalam modal detail di berbagai halaman.
    <x-rancangan.detail :rancangan="$selectedRancangan" />
--}}
@props(['rancangan', 'revisi' => null])

@php
    $revisi ??= $rancangan?->revisi?->last();
@endphp

@if ($rancangan)
    <div class="grid gap-5 lg:grid-cols-2">
        <section class="rounded-xl ring-1 ring-slate-100 dark:ring-navy-800">
            <h4 class="flex items-center gap-2 border-b border-slate-100 px-4 py-3 text-sm font-semibold dark:border-navy-800">
                <i class="bi bi-file-earmark-text text-primary-500"></i> Informasi Utama
            </h4>
            <x-rancangan.info :rancangan="$rancangan" class="px-4" />
        </section>

        <section class="rounded-xl ring-1 ring-slate-100 dark:ring-navy-800">
            <h4 class="flex items-center gap-2 border-b border-slate-100 px-4 py-3 text-sm font-semibold dark:border-navy-800">
                <i class="bi bi-journal-check text-emerald-500"></i> Hasil Penelitian
            </h4>
            <x-rancangan.revisi-info :revisi="$revisi" class="px-4" />
        </section>
    </div>
    {{ $slot }}
@else
    <div class="flex flex-col items-center gap-3 py-16 text-slate-500">
        <x-ui.spinner class="size-8 text-primary-500" />
        <p class="text-sm">Sedang memuat data, harap tunggu...</p>
    </div>
@endif
