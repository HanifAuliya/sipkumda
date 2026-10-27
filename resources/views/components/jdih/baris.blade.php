{{-- Satu baris hasil pencarian JDIH. <x-jdih.baris :dokumen="$d" /> --}}
@props(['dokumen'])

@php
    $jenis = App\Support\Jdih::labelJenis($dokumen->jenis_dokumentasi);
    $ikon = $jenis === 'Peraturan Bupati'
        ? ['file-earmark-ruled', 'bg-primary-50 text-primary-600 dark:bg-primary-500/15 dark:text-primary-300']
        : ['file-earmark-text', 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300'];
@endphp

<article {{ $attributes->class('group relative flex items-start gap-4 rounded-2xl border border-slate-200/80 bg-white p-4 transition hover:border-primary-200 hover:shadow-card sm:p-5 dark:border-navy-800 dark:bg-navy-900 dark:hover:border-primary-500/40') }}>
    <span class="hidden size-12 shrink-0 place-items-center rounded-xl text-xl sm:grid {{ $ikon[1] }}">
        <i class="bi bi-{{ $ikon[0] }}"></i>
    </span>

    <div class="min-w-0 flex-1">
        <p class="flex flex-wrap items-center gap-x-2 gap-y-1">
            <x-ui.status-badge :value="$jenis" />
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $dokumen->nomor_formatted }}</span>
        </p>

        <h3 class="mt-2 leading-snug font-semibold text-navy-900 group-hover:text-primary-700 dark:text-white dark:group-hover:text-primary-300">
            <a href="{{ route('jdih.show', $dokumen) }}" class="after:absolute after:inset-0 after:rounded-2xl focus:outline-none">
                {{ $dokumen->tentang_dokumentasi }}
            </a>
        </h3>

        <p class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
            <span class="inline-flex items-center gap-1.5"><i class="bi bi-calendar-check"></i> {{ tanggal($dokumen->tanggal_penetapan) }}</span>
            @if ($dokumen->nomor_tahun_berita)
                <span class="inline-flex items-center gap-1.5"><i class="bi bi-journal-text"></i> BD {{ $dokumen->nomor_tahun_berita }}</span>
            @endif
            <span class="inline-flex min-w-0 items-center gap-1.5"><i class="bi bi-building"></i>
                <span class="truncate">{{ $dokumen->perangkatDaerah->nama_perangkat_daerah ?? 'Pemerintah Kabupaten HST' }}</span>
            </span>
        </p>
    </div>

    @if ($dokumen->file_produk_hukum)
        <a href="{{ route('jdih.dokumen', $dokumen) }}?unduh=1" title="Unduh PDF" aria-label="Unduh PDF {{ $dokumen->nomor_formatted }}"
            class="relative z-10 hidden size-10 shrink-0 place-items-center self-center rounded-xl text-slate-500 ring-1 ring-slate-200 transition hover:bg-primary-50 hover:text-primary-600 hover:ring-primary-200 sm:grid dark:text-slate-400 dark:ring-navy-700 dark:hover:bg-primary-500/10">
            <i class="bi bi-download"></i>
        </a>
    @endif
</article>
