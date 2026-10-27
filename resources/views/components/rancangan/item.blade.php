{{--
    Kartu satu rancangan dalam daftar.

    <x-rancangan.item :rancangan="$item">
        <x-slot:meta> <x-rancangan.meta icon="person">...</x-rancangan.meta> </x-slot:meta>   (opsional, ditambahkan ke meta bawaan)
        ...konten tambahan (status, alert)...
        <x-slot:aside> status & tombol aksi </x-slot:aside>
    </x-rancangan.item>
--}}
@props(['rancangan'])

<article {{ $attributes->class('rounded-xl border border-slate-200 bg-white p-4 transition hover:border-primary-200 hover:shadow-soft sm:p-5 dark:border-navy-800 dark:bg-navy-900 dark:hover:border-primary-500/40') }}>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 flex-1 space-y-2.5">
            <div class="flex flex-wrap items-center gap-2">
                <h4 class="text-base font-bold">{{ $rancangan->no_rancangan }}</h4>
                <x-ui.status-badge :value="$rancangan->jenis_rancangan" />
                {{ $badges ?? '' }}
            </div>

            <p class="font-semibold text-slate-700 dark:text-slate-200">{{ $rancangan->tentang }}</p>

            <ul class="flex flex-wrap gap-x-5 gap-y-1.5 text-sm text-slate-500 dark:text-slate-400">
                {{ $meta ?? '' }}
                <x-rancangan.meta icon="building">{{ $rancangan->user->perangkatDaerah->nama_perangkat_daerah ?? '-' }}</x-rancangan.meta>
                <x-rancangan.meta icon="calendar3">Diajukan {{ tanggal($rancangan->tanggal_pengajuan, true) }}</x-rancangan.meta>
            </ul>

            {{ $slot }}
        </div>

        @isset($aside)
            <div class="flex shrink-0 flex-col items-start gap-2.5 lg:min-w-52 lg:items-end lg:text-right">
                {{ $aside }}
            </div>
        @endisset
    </div>
</article>
