{{--
    Kartu satu naskah dalam daftar. Urutan bacanya: naskah apa, tahap apa, lalu langkah berikutnya.
    <x-naskah.item :naskah="$naskah" />
--}}
@props(['naskah'])

@php
    $tahap = $naskah->tahap;
    $giliran = $naskah->giliranMilik(auth()->user());
    // Warna hanya untuk yang perlu tindakan atau sudah selesai; keterangan biasa dibuat tenang.
    [$warnaPesan, $ikonPesan] = match ($tahap->warna()) {
        'warning' => ['text-orange-700 dark:text-orange-300', 'exclamation-circle'],
        'success' => ['text-emerald-700 dark:text-emerald-300', 'check-circle'],
        default => ['text-slate-600 dark:text-slate-300', 'info-circle'],
    };
    $ikon = match (true) {
        $tahap === App\Enums\TahapNaskah::Selesai => 'check-circle',
        $tahap->terkunci() => 'lock',
        default => null,
    };
@endphp

<article {{ $attributes->class([
    'rounded-xl border border-slate-200 bg-white p-4 transition hover:border-primary-200 hover:shadow-soft sm:p-5 dark:border-navy-800 dark:bg-navy-900 dark:hover:border-primary-500/40',
    'border-l-4 border-l-primary-500 hover:border-l-primary-500 dark:border-l-primary-400 dark:hover:border-l-primary-400' => $giliran,
]) }}>
    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
        <div class="flex min-w-0 flex-wrap items-center gap-2">
            <h4 class="text-base font-bold">{{ $naskah->nomor_tampil }}</h4>
            <x-ui.status-badge :value="$naskah->jenis" />
        </div>
        <x-ui.badge :color="$tahap->warna()" size="lg" :icon="$ikon">{{ $tahap->label() }}</x-ui.badge>
    </div>

    <p class="mt-2 font-semibold text-slate-800 dark:text-slate-100">{{ $naskah->tentang }}</p>

    <ul class="mt-2 flex flex-wrap gap-x-5 gap-y-1.5 text-sm text-slate-500 dark:text-slate-400">
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

    <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 lg:flex-row lg:items-center lg:justify-between dark:border-navy-800">
        <div class="flex min-w-0 items-start gap-2 text-sm {{ $warnaPesan }}">
            <i class="bi bi-{{ $ikonPesan }} mt-0.5 text-base" aria-hidden="true"></i>
            <div class="min-w-0">
                <p>{{ $tahap->keterangan() }}</p>
                @if (filled($naskah->catatan_tahap))
                    <p class="mt-1 text-slate-600 dark:text-slate-300">Catatan: {{ $naskah->catatan_tahap }}</p>
                @endif
            </div>
        </div>

        <div class="flex shrink-0 gap-2 max-lg:w-full lg:justify-end">
            <x-naskah.detail-button :naskah="$naskah" label="Detail" class="max-lg:flex-1" />
            @if (auth()->user()->pemantau())
                <x-ui.button size="rapat" icon="vector-pen" :href="route('v2.naskah.pantau', $naskah)" class="max-lg:flex-1">Pantau</x-ui.button>
            @elseif ($giliran)
                {{-- Langsung ke halaman yang dikerjakan pada tahap ini: matriks, atau Nota Dinas & Tanda Tangan. --}}
                <x-ui.button size="rapat" :icon="$tahap->tahapAkhir() ? 'vector-pen' : 'pencil-square'" :href="$naskah->tautanUntuk(auth()->user())" class="max-lg:flex-1">{{ $tahap->labelKerjakan() }}</x-ui.button>
            @endif
        </div>
    </div>
</article>
