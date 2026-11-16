{{-- Detail produk hukum. Data dari JdihController@show. --}}
@php
    $jenis = App\Support\Jdih::labelJenis($dokumen->jenis_dokumentasi);
    $judul = "{$jenis} {$dokumen->nomor_formatted}";
    $deskripsi = Str::limit("{$judul} tentang {$dokumen->tentang_dokumentasi}. Kabupaten Hulu Sungai Tengah.", 160);
    $urlPdf = route('jdih.dokumen', $dokumen);
    $adaFile = filled($dokumen->file_produk_hukum);

    // [label, nilai, lebar penuh?]
    $metadata = [
        ['Jenis', $jenis, false],
        ['Nomor', $dokumen->nomor_lengkap ?? $dokumen->nomor, false],
        ['Tahun', $dokumen->tahun, false],
        ['Tanggal penetapan', tanggal($dokumen->tanggal_penetapan), false],
        ['Berita Daerah', $dokumen->berita_daerah ?? '-', false],
        ['Tempat penetapan', 'Barabai', false],
        ['Tentang', $dokumen->tentang_dokumentasi, true],
        ['Pemrakarsa', $dokumen->perangkatDaerah->nama_perangkat_daerah ?? '-', true],
        ['T.E.U. Badan', 'Kabupaten Hulu Sungai Tengah', true],
        ['Bahasa', 'Indonesia', false],
        ['Diarsipkan', tanggal($dokumen->tanggal_pengarsipan), false],
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

    $tombolTerang = 'inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition';
@endphp

<x-publik-layout :title="$judul" :description="$deskripsi">
    <x-slot:head>
        <script type="application/ld+json">@json($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
    </x-slot:head>

    {{-- Kepala halaman --}}
    <section class="relative overflow-hidden bg-navy-900">
        <div class="pointer-events-none absolute -top-24 right-0 size-80 rounded-full bg-primary-500/20 blur-3xl"></div>
        <div class="relative mx-auto max-w-7xl px-5 pt-8 pb-10 lg:px-8">
            <nav aria-label="Breadcrumb" class="text-sm text-white/60">
                <ol class="flex flex-wrap items-center gap-2">
                    <li><a href="{{ route('beranda') }}" class="hover:text-white">Beranda</a></li>
                    <li aria-hidden="true"><i class="bi bi-chevron-right text-xs"></i></li>
                    <li><a href="{{ route('jdih.index') }}" class="hover:text-white">Produk Hukum</a></li>
                    <li aria-hidden="true"><i class="bi bi-chevron-right text-xs"></i></li>
                    <li><a href="{{ route('jdih.index', ['jenis' => $jenis]) }}" class="hover:text-white">{{ $jenis }}</a></li>
                </ol>
            </nav>

            <div class="mt-6 flex flex-wrap items-center gap-2">
                <x-ui.status-badge :value="$jenis" size="lg" />
                <x-ui.badge color="success" icon="patch-check" size="lg">Berlaku</x-ui.badge>
            </div>
            <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ $judul }}</h1>
            <p class="mt-2 max-w-4xl text-base text-white/80 sm:text-lg">tentang {{ $dokumen->tentang_dokumentasi }}</p>

            <div class="mt-6 flex flex-wrap gap-2" x-data="{ disalin: false }">
                @if ($adaFile)
                    <a href="{{ $urlPdf }}?unduh=1" class="{{ $tombolTerang }} bg-brand-500 text-white shadow-lg shadow-brand-500/20 dark:text-navy-950 hover:bg-brand-600">
                        <i class="bi bi-download"></i> Unduh PDF
                    </a>
                @endif
                <button type="button" class="{{ $tombolTerang }} bg-white/10 text-white ring-1 ring-white/15 hover:bg-white/20"
                    x-on:click="navigator.clipboard?.writeText(location.href).then(() => { disalin = true; setTimeout(() => disalin = false, 2000) })">
                    <i class="bi" x-bind:class="disalin ? 'bi-check2' : 'bi-link-45deg'"></i>
                    <span x-text="disalin ? 'Tautan disalin' : 'Salin tautan'">Salin tautan</span>
                </button>
                <a href="https://wa.me/?text={{ rawurlencode($judul . ' tentang ' . $dokumen->tentang_dokumentasi . ' ' . route('jdih.show', $dokumen)) }}" target="_blank" rel="noopener"
                    class="{{ $tombolTerang }} bg-white/10 text-white ring-1 ring-white/15 hover:bg-white/20">
                    <i class="bi bi-whatsapp"></i> Bagikan
                </a>
            </div>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl items-start gap-8 px-5 py-10 lg:grid-cols-[minmax(0,1fr)_22rem] lg:px-8">
        {{-- Metadata: paling atas di layar kecil, sidebar di layar lebar --}}
        <aside class="lg:sticky lg:top-24 lg:order-2">
            <section class="rounded-2xl bg-white ring-1 ring-slate-200/70 dark:bg-navy-900 dark:ring-navy-800">
                <h2 class="flex items-center gap-2 border-b border-slate-100 px-5 py-4 text-sm font-bold text-navy-900 dark:border-navy-800 dark:text-white">
                    <i class="bi bi-info-circle text-primary-500"></i> Informasi produk hukum
                </h2>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-4 p-5">
                    @foreach ($metadata as [$label, $nilai, $penuh])
                        <div @class(['min-w-0', 'col-span-2' => $penuh])>
                            <dt class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                            {{-- Nomor SK panjang (140/31/412.1.6/TAHUN 2026) boleh dipatah setelah "/", bukan di tengah kata. --}}
                            <dd class="mt-0.5 text-sm font-semibold break-words text-navy-900 dark:text-slate-100">{!! str_replace('/', '/<wbr>', e($nilai)) !!}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </aside>

        <div class="min-w-0 space-y-8 lg:order-1">
            {{-- Dokumen --}}
            <section aria-labelledby="judul-dokumen" class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/70 dark:bg-navy-900 dark:ring-navy-800">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-3.5 dark:border-navy-800">
                    <h2 id="judul-dokumen" class="flex min-w-0 items-center gap-2 text-sm font-bold text-navy-900 dark:text-white">
                        <i class="bi bi-file-earmark-pdf text-rose-500"></i> <span class="truncate">Dokumen {{ $judul }}</span>
                    </h2>
                    @if ($adaFile)
                        <div class="flex gap-2">
                            <a href="{{ $urlPdf }}" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50 dark:text-slate-300 dark:ring-navy-700 dark:hover:bg-navy-800">
                                <i class="bi bi-box-arrow-up-right"></i> Buka
                            </a>
                            <a href="{{ $urlPdf }}?unduh=1"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-primary-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-600">
                                <i class="bi bi-download"></i> Unduh
                            </a>
                        </div>
                    @endif
                </div>

                @if ($adaFile)
                    {{-- Pratinjau PDF di layar lebar; di ponsel PDF dalam iframe sering tidak tampil. --}}
                    <iframe src="{{ $urlPdf }}#view=FitH" title="Dokumen {{ $judul }}" loading="lazy"
                        class="hidden h-[78vh] min-h-[36rem] w-full border-0 bg-slate-100 lg:block dark:bg-navy-950"></iframe>
                    <div class="flex flex-col items-center gap-4 px-6 py-10 text-center lg:hidden">
                        <span class="grid size-16 place-items-center rounded-2xl bg-rose-50 text-3xl text-rose-500 dark:bg-rose-500/10"><i class="bi bi-file-earmark-pdf"></i></span>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Buka dokumen untuk membaca isi lengkapnya.</p>
                        <a href="{{ $urlPdf }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-navy-900 px-5 py-2.5 text-sm font-semibold text-white dark:bg-primary-500">
                            <i class="bi bi-box-arrow-up-right"></i> Buka PDF
                        </a>
                    </div>
                @else
                    <x-ui.empty-state icon="file-earmark-x" title="Dokumen belum tersedia." description="Silakan hubungi Bagian Hukum Setda Kabupaten Hulu Sungai Tengah." class="py-16" />
                @endif
            </section>

            {{-- Terkait --}}
            @if ($terkait->isNotEmpty())
                <section aria-labelledby="judul-terkait">
                    <div class="flex items-center justify-between gap-4">
                        <h2 id="judul-terkait" class="text-lg font-bold text-navy-900 dark:text-white">{{ $jenis }} lainnya</h2>
                        <a href="{{ route('jdih.index', ['jenis' => $jenis]) }}" class="text-sm font-semibold text-primary-600 hover:underline dark:text-primary-300">Lihat semua</a>
                    </div>
                    <div class="mt-4 space-y-3">
                        @foreach ($terkait as $lain)
                            <x-jdih.baris :dokumen="$lain" />
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-publik-layout>
