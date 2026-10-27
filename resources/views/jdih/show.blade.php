{{-- Detail produk hukum. Data dari JdihController@show. --}}
@php
    $jenis = App\Support\Jdih::labelJenis($dokumen->jenis_dokumentasi);
    $judul = "{$jenis} {$dokumen->nomor_formatted}";
    $deskripsi = Str::limit("{$judul} tentang {$dokumen->tentang_dokumentasi}. Kabupaten Hulu Sungai Tengah.", 160);
    $urlPdf = route('jdih.dokumen', $dokumen);
    $adaFile = filled($dokumen->file_produk_hukum);

    $metadata = [
        ['Jenis', $jenis],
        ['Nomor', $dokumen->nomor],
        ['Tahun', $dokumen->tahun],
        ['Tentang', $dokumen->tentang_dokumentasi],
        ['Tanggal penetapan', tanggal($dokumen->tanggal_penetapan)],
        ['Berita Daerah', $dokumen->nomor_tahun_berita ?: '-'],
        ['Pemrakarsa', $dokumen->perangkatDaerah->nama_perangkat_daerah ?? '-'],
        ['T.E.U. Badan', 'Kabupaten Hulu Sungai Tengah'],
        ['Tempat penetapan', 'Barabai'],
        ['Bahasa', 'Indonesia'],
        ['Diarsipkan', tanggal($dokumen->tanggal_pengarsipan)],
    ];

    $schema = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Legislation',
        'name' => "{$judul} tentang {$dokumen->tentang_dokumentasi}",
        'legislationType' => $jenis,
        'legislationIdentifier' => $dokumen->nomor_formatted,
        'legislationDate' => $dokumen->tanggal_penetapan ? \Illuminate\Support\Carbon::parse($dokumen->tanggal_penetapan)->toDateString() : null,
        'legislationJurisdiction' => 'Kabupaten Hulu Sungai Tengah',
        'inLanguage' => 'id',
        'url' => route('jdih.show', $dokumen),
        'encoding' => $adaFile ? ['@type' => 'LegislationObject', 'encodingFormat' => 'application/pdf', 'contentUrl' => $urlPdf] : null,
    ]);
@endphp

<x-publik-layout :title="$judul" :description="$deskripsi">
    <x-slot:head>
        <script type="application/ld+json">@json($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
    </x-slot:head>

    <section class="bg-navy-900 pt-8 pb-12 text-white">
        <div class="mx-auto max-w-7xl px-5 lg:px-8">
            <nav aria-label="Breadcrumb" class="text-sm text-white/60">
                <ol class="flex flex-wrap items-center gap-2">
                    <li><a href="{{ route('beranda') }}" class="hover:text-white">Beranda</a></li>
                    <li aria-hidden="true"><i class="bi bi-chevron-right text-xs"></i></li>
                    <li><a href="{{ route('jdih.index') }}" class="hover:text-white">Produk Hukum</a></li>
                    <li aria-hidden="true"><i class="bi bi-chevron-right text-xs"></i></li>
                    <li><a href="{{ route('jdih.index', ['jenis' => $jenis]) }}" class="hover:text-white">{{ $jenis }}</a></li>
                </ol>
            </nav>

            <div class="mt-5 flex flex-wrap items-center gap-2">
                <x-ui.status-badge :value="$jenis" size="lg" />
                <x-ui.badge color="success" icon="patch-check" size="lg">Berlaku</x-ui.badge>
            </div>
            <h1 class="mt-3 text-2xl font-extrabold text-white sm:text-3xl">{{ $judul }}</h1>
            <p class="mt-2 max-w-4xl text-lg text-white/80">tentang {{ $dokumen->tentang_dokumentasi }}</p>

            <div class="mt-6 flex flex-wrap gap-2" x-data="{ disalin: false }">
                @if ($adaFile)
                    <a href="{{ $urlPdf }}?unduh=1" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition hover:bg-brand-600">
                        <i class="bi bi-download"></i> Unduh PDF
                    </a>
                    <a href="{{ $urlPdf }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-white/15 transition hover:bg-white/20">
                        <i class="bi bi-box-arrow-up-right"></i> Buka di tab baru
                    </a>
                @endif
                <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-white/15 transition hover:bg-white/20"
                    x-on:click="navigator.clipboard?.writeText(location.href).then(() => { disalin = true; setTimeout(() => disalin = false, 2000) })">
                    <i class="bi" x-bind:class="disalin ? 'bi-check2' : 'bi-link-45deg'"></i>
                    <span x-text="disalin ? 'Tautan disalin' : 'Salin tautan'">Salin tautan</span>
                </button>
                <a href="https://wa.me/?text={{ rawurlencode($judul . ' tentang ' . $dokumen->tentang_dokumentasi . ' ' . route('jdih.show', $dokumen)) }}" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-white/15 transition hover:bg-white/20">
                    <i class="bi bi-whatsapp"></i> Bagikan
                </a>
            </div>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-8 px-5 py-10 lg:grid-cols-[minmax(0,22rem)_minmax(0,1fr)] lg:px-8">
        <aside class="space-y-6">
            <section class="rounded-2xl bg-white ring-1 ring-slate-200/70 dark:bg-navy-900 dark:ring-navy-800">
                <h2 class="border-b border-slate-100 px-5 py-4 text-sm font-bold text-navy-900 dark:border-navy-800 dark:text-white">Metadata</h2>
                <dl class="divide-y divide-slate-100 px-5 text-sm dark:divide-navy-800">
                    @foreach ($metadata as [$label, $nilai])
                        <div class="grid grid-cols-[8.5rem_1fr] gap-3 py-2.5">
                            <dt class="text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                            <dd class="font-medium text-slate-800 dark:text-slate-200">{{ $nilai }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            @if ($terkait->isNotEmpty())
                <section class="rounded-2xl bg-white ring-1 ring-slate-200/70 dark:bg-navy-900 dark:ring-navy-800">
                    <h2 class="border-b border-slate-100 px-5 py-4 text-sm font-bold text-navy-900 dark:border-navy-800 dark:text-white">{{ $jenis }} lainnya</h2>
                    <ul class="divide-y divide-slate-100 dark:divide-navy-800">
                        @foreach ($terkait as $lain)
                            <li>
                                <a href="{{ route('jdih.show', $lain) }}" class="block px-5 py-3 transition hover:bg-slate-50 dark:hover:bg-navy-800">
                                    <span class="block text-xs font-semibold text-slate-500">{{ $lain->nomor_formatted }}</span>
                                    <span class="mt-0.5 line-clamp-2 block text-sm text-navy-900 dark:text-slate-200">{{ $lain->tentang_dokumentasi }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </aside>

        <section aria-label="Dokumen" class="min-w-0">
            @if ($adaFile)
                <div class="overflow-hidden rounded-2xl bg-slate-100 ring-1 ring-slate-200/70 dark:bg-navy-900 dark:ring-navy-800">
                    <iframe src="{{ $urlPdf }}#view=FitH" title="Dokumen {{ $judul }}" loading="lazy" class="h-[80vh] min-h-[32rem] w-full border-0"></iframe>
                </div>
                <p class="mt-3 text-center text-xs text-slate-500">
                    Dokumen tidak tampil? <a href="{{ $urlPdf }}" target="_blank" rel="noopener" class="font-semibold text-primary-600 hover:underline dark:text-primary-300">Buka PDF</a>
                    atau <a href="{{ $urlPdf }}?unduh=1" class="font-semibold text-primary-600 hover:underline dark:text-primary-300">unduh</a>.
                </p>
            @else
                <x-ui.empty-state icon="file-earmark-x" title="Dokumen belum tersedia." description="Silakan hubungi Bagian Hukum Setda Kabupaten Hulu Sungai Tengah." class="rounded-2xl py-16 ring-1 ring-slate-200/70 dark:ring-navy-800" />
            @endif
        </section>
    </div>
</x-publik-layout>
