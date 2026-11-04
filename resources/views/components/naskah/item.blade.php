{{--
    Kartu satu naskah dalam daftar, sama bentuknya dengan <x-rancangan.item> pada aplikasi lama.
    <x-naskah.item :naskah="$naskah" />
--}}
@props(['naskah'])

@php
    $tahap = $naskah->tahap;
    // Pesan biasa memakai ungu lembut tema; oranye dan hijau hanya untuk yang perlu tindakan atau sudah selesai.
    $jenisPesan = in_array($tahap->warna(), ['warning', 'success'], true) ? $tahap->warna() : 'primary';
    $ikon = match (true) {
        $tahap === App\Enums\TahapNaskah::Selesai => 'check-circle',
        $tahap->terkunci() => 'lock',
        default => 'gear',
    };
@endphp

<article {{ $attributes->class('rounded-xl border border-slate-200 bg-white p-4 transition hover:border-primary-200 hover:shadow-soft sm:p-5 dark:border-navy-800 dark:bg-navy-900 dark:hover:border-primary-500/40') }}>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 flex-1 space-y-2.5">
            <div class="flex flex-wrap items-center gap-2">
                <h4 class="text-base font-bold">{{ $naskah->nomor }}</h4>
                <x-ui.status-badge :value="$naskah->jenis" />
            </div>

            <p class="font-semibold text-slate-700 dark:text-slate-200">{{ $naskah->tentang }}</p>

            <ul class="flex flex-wrap gap-x-5 gap-y-1.5 text-sm text-slate-500 dark:text-slate-400">
                @if ($tahap->langkah() >= 1)
                    <x-rancangan.meta icon="person-gear">
                        Peneliti:
                        <span @class(['font-semibold', 'text-primary-600 dark:text-primary-300' => $naskah->peneliti, 'text-rose-500' => ! $naskah->peneliti])>
                            {{ $naskah->peneliti->nama_user ?? 'Belum ditentukan' }}
                        </span>
                    </x-rancangan.meta>
                @endif
                <x-rancangan.meta icon="building">{{ $naskah->pemrakarsa->perangkatDaerah->nama_perangkat_daerah ?? $naskah->pemrakarsa->nama_user }}</x-rancangan.meta>
                <x-rancangan.meta icon="calendar3">{{ $naskah->diajukan_at ? 'Diajukan '.tanggal($naskah->diajukan_at, true) : 'Belum diajukan' }}</x-rancangan.meta>
            </ul>

            <x-ui.alert :type="$jenisPesan" dismissible>
                {{ $tahap->keterangan() }}
                @if (filled($naskah->catatan_tahap))
                    <span class="mt-1 block opacity-90">Catatan: {{ $naskah->catatan_tahap }}</span>
                @endif
            </x-ui.alert>
        </div>

        <div class="flex shrink-0 flex-col items-start gap-2.5 lg:min-w-52 lg:items-end lg:text-right">
            <x-ui.badge :color="$tahap->warna()" size="lg" :icon="$ikon">{{ $tahap->label() }}</x-ui.badge>
            <p class="text-sm text-slate-500 dark:text-slate-400">Dibuat tahun {{ $naskah->created_at->year }}</p>
            <x-naskah.detail-button :naskah="$naskah" label="Detail" />
        </div>
    </div>
</article>
