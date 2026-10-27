{{-- Beranda publik: pencarian JDIH + informasi SIFKUMDA. Data dari JdihController@beranda (ter-cache). --}}
@php
    $services = [
        ['upload', 'Pengajuan Produk Hukum', 'Ajukan rancangan produk hukum daerah secara digital dengan proses yang lebih cepat dan transparan.'],
        ['check-circle', 'Pemeriksaan dan Validasi', 'Verifikasi dan validasi dokumen dilakukan secara sistematis untuk memastikan kelengkapan dan keabsahan.'],
        ['pencil-square', 'Penyusunan dan Penelitian', 'Dokumen yang diajukan dapat diteliti dengan mudah, memungkinkan kolaborasi yang lebih efektif.'],
        ['archive', 'Dokumentasi dan Arsip', 'Seluruh produk hukum yang ditetapkan terarsip dan dapat dicari publik melalui JDIH.'],
        ['globe2', 'Akses Mudah dan Terintegrasi', 'Platform berbasis web yang dapat diakses seluruh pihak terkait secara aman dan efisien.'],
        ['chat-square-text', 'Bantuan dan Dukungan', 'Dapatkan bantuan teknis dan informasi terkait proses fasilitasi produk hukum.'],
    ];

    $logo = fn ($file) => 'https://ik.imagekit.io/bertawedding/ppid/logo%20lembaga/' . $file;
    $links = [
        ['https://diskominfo.hstkab.go.id', $logo('Kominfo%20(1).png?updatedAt=1728977390499'), 'Diskominfo HST'],
        ['https://hstkab.go.id', $logo('Pemkab.png?updatedAt=1728977715130'), 'Pemkab HST'],
        ['https://opendata.hstkab.go.id', $logo('OpenData.png?updatedAt=1728978182768'), 'Open Data HST'],
        ['https://www.youtube.com/@mediacenterhst', $logo('Kominfo%20(2).png?updatedAt=1729128401636'), 'Media Center HST'],
        ['https://ppidutama.kalselprov.go.id', $logo('PPID%20UTAMA.png?updatedAt=1728978540123'), 'PPID Utama Kalsel'],
        ['https://lapor.go.id', $logo('LAPOR.png?updatedAt=1729127779226'), 'LAPOR'],
    ];

    $ikonJenis = ['Peraturan Bupati' => 'file-earmark-ruled', 'Keputusan Bupati' => 'file-earmark-text', 'Surat Keputusan' => 'file-earmark-text'];
    $maksTahun = max([1, ...array_values($perTahun)]);

    // Elemen muncul halus saat di-scroll ke layar.
    $reveal = 'x-data="{ shown: false }" x-intersect.once.margin.-80px="shown = true" x-bind:class="shown ? \'opacity-100 translate-y-0\' : \'opacity-0 translate-y-6\'"';
@endphp

<x-publik-layout :transparan="true">
    {{-- Hero + pencarian --}}
    <section class="relative isolate overflow-hidden pt-32 pb-20 sm:pt-40 sm:pb-28">
        <img src="{{ asset('assets/img/theme/sulangking.jpg') }}" alt="" class="absolute inset-0 -z-20 size-full object-cover" fetchpriority="high">
        <div class="absolute inset-0 -z-10 bg-linear-to-b from-navy-950/85 via-navy-950/75 to-navy-950/95"></div>

        <div class="mx-auto max-w-4xl px-5 text-center text-white">
            <p class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-xs font-semibold tracking-wide ring-1 ring-white/15 backdrop-blur">
                <i class="bi bi-bank text-brand-500"></i> Bagian Hukum Setda Kabupaten Hulu Sungai Tengah
            </p>
            <h1 class="mt-6 text-3xl leading-tight font-extrabold text-white sm:text-5xl">
                Jaringan Dokumentasi dan<br class="hidden sm:block"> Informasi Hukum Daerah
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-base text-white/75 sm:text-lg">
                Temukan Peraturan Bupati dan Keputusan Bupati Kabupaten Hulu Sungai Tengah secara cepat dan terbuka untuk umum.
            </p>

            <x-jdih.form-cari :opsi="$opsi" besar class="mx-auto mt-10 max-w-3xl text-left" />

            <div class="mt-5 flex flex-wrap items-center justify-center gap-2 text-sm">
                <span class="text-white/60">Telusuri:</span>
                @foreach ($perJenis as $jenis => $jumlah)
                    <a href="{{ route('jdih.index', ['jenis' => $jenis]) }}"
                        class="rounded-full bg-white/10 px-3.5 py-1.5 font-medium text-white ring-1 ring-white/15 transition hover:bg-white/20">
                        {{ $jenis }} <span class="text-white/60">· {{ number_format($jumlah, 0, ',', '.') }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Ringkasan --}}
    <section class="relative z-10 mx-auto -mt-10 max-w-6xl px-5 lg:px-8">
        <div class="grid gap-4 rounded-3xl bg-white p-4 shadow-card ring-1 ring-slate-100 sm:grid-cols-2 lg:grid-cols-4 dark:bg-navy-900 dark:ring-navy-800">
            <div class="flex items-center gap-4 rounded-2xl bg-navy-900 p-5 text-white dark:bg-navy-800">
                <span class="grid size-12 place-items-center rounded-xl bg-white/10 text-2xl"><i class="bi bi-collection"></i></span>
                <div>
                    <p class="text-3xl font-extrabold">{{ number_format($total, 0, ',', '.') }}</p>
                    <p class="text-sm text-white/70">Total produk hukum</p>
                </div>
            </div>
            @foreach (array_slice($perJenis, 0, 2, true) as $jenis => $jumlah)
                <a href="{{ route('jdih.index', ['jenis' => $jenis]) }}" class="flex items-center gap-4 rounded-2xl p-5 transition hover:bg-slate-50 dark:hover:bg-navy-800">
                    <span class="grid size-12 place-items-center rounded-xl bg-primary-50 text-2xl text-primary-500 dark:bg-primary-500/15"><i class="bi bi-{{ $ikonJenis[$jenis] ?? 'file-earmark' }}"></i></span>
                    <div>
                        <p class="text-3xl font-extrabold text-navy-900 dark:text-white">{{ number_format($jumlah, 0, ',', '.') }}</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $jenis }}</p>
                    </div>
                </a>
            @endforeach
            <a href="{{ route('jdih.index', ['urut' => 'terbaru']) }}" class="flex items-center gap-4 rounded-2xl p-5 transition hover:bg-slate-50 dark:hover:bg-navy-800">
                <span class="grid size-12 place-items-center rounded-xl bg-emerald-50 text-2xl text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400"><i class="bi bi-calendar2-week"></i></span>
                <div>
                    <p class="text-3xl font-extrabold text-navy-900 dark:text-white">{{ array_key_first($perTahun) ?? '-' }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Tahun terbaru</p>
                </div>
            </a>
        </div>
    </section>

    {{-- Terbaru --}}
    <section class="py-16 lg:py-20">
        <div class="mx-auto max-w-7xl px-5 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-bold tracking-wider text-brand-600 uppercase">Terbaru</p>
                    <h2 class="mt-1 text-2xl font-extrabold text-navy-900 sm:text-3xl dark:text-white">Produk hukum terbaru ditetapkan</h2>
                </div>
                <a href="{{ route('jdih.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary-600 hover:underline dark:text-primary-300">
                    Lihat semua <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            @if ($terbaru->isEmpty())
                <x-ui.empty-state icon="folder2-open" title="Belum ada produk hukum yang dipublikasikan." class="mt-10 py-12" />
            @else
                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($terbaru as $dokumen)
                        <x-jdih.kartu :dokumen="$dokumen" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Per tahun --}}
    @if ($perTahun)
        <section class="bg-slate-50 py-16 lg:py-20 dark:bg-navy-900">
            <div class="mx-auto max-w-7xl px-5 lg:px-8">
                <p class="text-sm font-bold tracking-wider text-brand-600 uppercase">Arsip</p>
                <h2 class="mt-1 text-2xl font-extrabold text-navy-900 sm:text-3xl dark:text-white">Telusuri berdasarkan tahun</h2>

                <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($perTahun as $tahunArsip => $jumlah)
                        <a href="{{ route('jdih.index', ['tahun' => $tahunArsip]) }}"
                            class="group rounded-2xl bg-white p-5 ring-1 ring-slate-200/70 transition hover:-translate-y-0.5 hover:ring-primary-300 dark:bg-navy-950 dark:ring-navy-800">
                            <div class="flex items-baseline justify-between">
                                <span class="text-2xl font-extrabold text-navy-900 dark:text-white">{{ $tahunArsip }}</span>
                                <span class="text-sm text-slate-500">{{ $jumlah }} dokumen</span>
                            </div>
                            <span class="mt-3 block h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-navy-800">
                                <span class="block h-full rounded-full bg-primary-500 transition group-hover:bg-brand-500" style="width: {{ round($jumlah / $maksTahun * 100) }}%"></span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Tentang --}}
    <section id="about" class="scroll-mt-20 py-20 lg:py-28">
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-5 transition duration-700 lg:grid-cols-2 lg:px-8" {!! $reveal !!}>
            <div>
                <p class="text-sm font-bold tracking-wider text-brand-600 uppercase">Tentang</p>
                <h2 class="mt-2 text-3xl font-extrabold text-navy-900 sm:text-4xl dark:text-white">SIFKUMDA — Sistem Fasilitasi Produk Hukum Daerah</h2>
                <p class="mt-5 text-lg text-slate-600 italic dark:text-slate-300">
                    Platform digital untuk penyusunan, fasilitasi, dan dokumentasi produk hukum daerah secara efektif dan transparan.
                </p>
                <ul class="mt-6 space-y-4">
                    @foreach ([
                        'Mengelola produk hukum daerah seperti <strong>Peraturan Bupati</strong> dan <strong>Keputusan Bupati</strong>.',
                        'Memfasilitasi <strong>pengajuan, pemeriksaan, penelitian, dan penyimpanan</strong> dokumen hukum secara digital.',
                        'Produk hukum yang telah ditetapkan <strong>terbuka untuk umum</strong> melalui pencarian JDIH di halaman ini.',
                    ] as $point)
                        <li class="flex gap-3 text-slate-600 dark:text-slate-300">
                            <i class="bi bi-check2-all mt-0.5 text-xl text-brand-500"></i>
                            <span>{!! $point !!}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-xl bg-navy-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-navy-800">
                        <i class="bi bi-box-arrow-in-right"></i> Masuk untuk pengajuan
                    </a>
                    <a href="{{ asset('assets/document/SIFKUMDA-PanduanLogin.pdf') }}" target="_blank" rel="noopener"
                        class="inline-flex items-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold text-navy-900 ring-1 ring-slate-200 transition hover:bg-slate-50 dark:text-white dark:ring-navy-700 dark:hover:bg-navy-800">
                        <i class="bi bi-person-lock"></i> Panduan login
                    </a>
                </div>
            </div>
            <img src="{{ asset('assets/img/theme/Surat-Keputusan.jpg') }}" alt="Dokumen Surat Keputusan" class="w-full rounded-3xl object-cover shadow-2xl" loading="lazy">
        </div>
    </section>

    {{-- Tautan penting --}}
    <section class="border-y border-slate-100 bg-slate-50 py-10 dark:border-navy-800 dark:bg-navy-900">
        <h2 class="text-center text-sm font-bold tracking-wider text-slate-500 uppercase">Tautan Penting</h2>
        <div class="group relative mt-6 overflow-hidden [mask-image:linear-gradient(to_right,transparent,black_10%,black_90%,transparent)]">
            <div class="flex w-max animate-marquee items-center gap-16 group-hover:[animation-play-state:paused]">
                @foreach ([...$links, ...$links] as $i => [$href, $img, $alt])
                    <a href="{{ $href }}" target="_blank" rel="noopener" @if ($i >= count($links)) aria-hidden="true" tabindex="-1" @endif
                        class="opacity-70 grayscale transition hover:opacity-100 hover:grayscale-0">
                        <img src="{{ $img }}" alt="{{ $alt }}" class="h-12 w-auto" loading="lazy">
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Layanan --}}
    <section id="services" class="scroll-mt-20 py-20 lg:py-28">
        <div class="mx-auto max-w-7xl px-5 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-bold tracking-wider text-brand-600 uppercase">Layanan Kami</p>
                <h2 class="mt-2 text-3xl font-extrabold text-navy-900 sm:text-4xl dark:text-white">Fasilitasi produk hukum daerah secara cepat dan transparan</h2>
            </div>
            <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $i => [$icon, $title, $desc])
                    <div class="rounded-2xl border border-slate-100 bg-white p-7 shadow-card transition duration-700 hover:-translate-y-1 dark:border-navy-800 dark:bg-navy-900 dark:shadow-none"
                        style="transition-delay: {{ $i * 60 }}ms" {!! $reveal !!}>
                        <span class="grid size-12 place-items-center rounded-xl bg-brand-500/10 text-2xl text-brand-600 dark:text-brand-500"><i class="bi bi-{{ $icon }}"></i></span>
                        <h3 class="mt-5 text-lg font-bold text-navy-900 dark:text-white">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Kontak --}}
    <section id="contact" class="scroll-mt-20 bg-slate-50 py-20 lg:py-28 dark:bg-navy-900">
        <div class="mx-auto max-w-7xl px-5 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-bold tracking-wider text-brand-600 uppercase">Kontak Kami</p>
                <h2 class="mt-2 text-3xl font-extrabold text-navy-900 sm:text-4xl dark:text-white">Hubungi kami untuk informasi lebih lanjut</h2>
            </div>
            <div class="mt-12 overflow-hidden rounded-3xl shadow-card ring-1 ring-slate-100 dark:ring-navy-800">
                <iframe class="h-80 w-full border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen
                    title="Lokasi Sekretariat Daerah Kabupaten Hulu Sungai Tengah"
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15934.265820741635!2d115.38191621594372!3d-2.5891951377680394!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2de420cf5f1f93b7%3A0xa81c2d6ec6b4e62d!2sJl.%20Perwira%2C%20Barabai%20Sel.%2C%20Kec.%20Barabai%2C%20Kabupaten%20Hulu%20Sungai%20Tengah%2C%20Kalimantan%20Selatan%2071315!5e0!3m2!1sen!2sid!4v1713456789012!5m2!1sen!2sid"></iframe>
            </div>
        </div>
    </section>
</x-publik-layout>
