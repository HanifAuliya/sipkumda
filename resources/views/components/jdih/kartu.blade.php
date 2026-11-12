{{-- Kartu produk hukum pada daftar JDIH. <x-jdih.kartu :dokumen="$d" /> --}}
@props(['dokumen'])

<article {{ $attributes->class('group relative flex flex-col rounded-2xl border border-slate-200/70 bg-white p-5 transition hover:-translate-y-0.5 hover:border-primary-200 hover:shadow-card dark:border-navy-800 dark:bg-navy-900 dark:hover:border-primary-500/40') }}>
    <div class="flex flex-wrap items-center gap-2">
        <x-ui.status-badge :value="App\Support\Jdih::labelJenis($dokumen->jenis_dokumentasi)" />
        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $dokumen->nomor_formatted }}</span>
    </div>

    <h3 class="mt-3 text-base leading-snug font-semibold text-navy-900 dark:text-white">
        <a href="{{ route('jdih.show', $dokumen) }}" class="after:absolute after:inset-0 focus:outline-none">
            {{ $dokumen->tentang_dokumentasi }}
        </a>
    </h3>

    <dl class="mt-auto grid grid-cols-2 gap-x-4 gap-y-1.5 pt-4 text-xs text-slate-500 dark:text-slate-400">
        <div>
            <dt class="sr-only">Tanggal penetapan</dt>
            <dd class="flex items-center gap-1.5"><i class="bi bi-calendar-check"></i> {{ tanggal($dokumen->tanggal_penetapan) }}</dd>
        </div>
        <div>
            <dt class="sr-only">Berita daerah</dt>
            <dd class="flex items-center gap-1.5"><i class="bi bi-journal-text"></i> BD {{ $dokumen->berita_daerah ?? '-' }}</dd>
        </div>
        <div class="col-span-2">
            <dt class="sr-only">Pemrakarsa</dt>
            <dd class="flex items-center gap-1.5 truncate"><i class="bi bi-building"></i> {{ $dokumen->perangkatDaerah->nama_perangkat_daerah ?? 'Pemerintah Kabupaten HST' }}</dd>
        </div>
    </dl>

    <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-primary-600 dark:text-primary-300">
        Lihat detail <i class="bi bi-arrow-right transition group-hover:translate-x-1"></i>
    </span>
</article>
