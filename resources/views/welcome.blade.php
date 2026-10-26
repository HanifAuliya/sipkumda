@php
    $nav = [
        ['#hero', 'Beranda'],
        ['#about', 'Tentang SIFKUMDA'],
        ['#services', 'Layanan'],
        ['#stats', 'Produk Hukum'],
        ['#contact', 'Kontak'],
    ];

    $services = [
        ['upload', 'Pengajuan Produk Hukum', 'Ajukan rancangan produk hukum daerah secara digital dengan proses yang lebih cepat dan transparan.'],
        ['check-circle', 'Pemeriksaan dan Validasi', 'Verifikasi dan validasi dokumen dilakukan secara sistematis untuk memastikan kelengkapan dan keabsahan.'],
        ['pencil-square', 'Penyusunan dan Penelitian', 'Dokumen yang diajukan dapat diteliti dengan mudah, memungkinkan kolaborasi yang lebih efektif.'],
        ['archive', 'Dokumentasi dan Arsip', 'Seluruh dokumen yang telah difasilitasi tersimpan dalam sistem secara aman dan dapat diakses kapan saja.'],
        ['globe2', 'Akses Mudah dan Terintegrasi', 'Platform berbasis web yang dapat diakses seluruh pihak terkait secara aman dan efisien.'],
        ['chat-square-text', 'Bantuan dan Dukungan', 'Dapatkan bantuan teknis dan informasi terkait proses fasilitasi produk hukum.'],
    ];

    $stats = [
        ['file-earmark-text', $sk_rancangan, 'Rancangan', 'Surat Keputusan'],
        ['file-earmark-ruled', $pb_rancangan, 'Rancangan', 'Peraturan Bupati'],
        ['folder-check', $sk_dokumentasi, 'Dokumentasi', 'Surat Keputusan'],
        ['folder2-open', $pb_dokumentasi, 'Dokumentasi', 'Peraturan Bupati'],
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

    // Elemen muncul halus saat di-scroll ke layar.
    $reveal = 'x-data="{ shown: false }" x-intersect.once.margin.-80px="shown = true" x-bind:class="shown ? \'opacity-100 translate-y-0\' : \'opacity-0 translate-y-6\'"';
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    @include('layouts.partials.head')
    <title>SIFKUMDA — Sistem Fasilitasi Produk Hukum Daerah Hulu Sungai Tengah</title>
    <meta property="og:title" content="SIFKUMDA — Sistem Fasilitasi Produk Hukum Daerah">
    <meta property="og:image" content="{{ asset('assets/img/theme/sulangking.jpg') }}">
</head>

<body class="bg-white dark:bg-navy-950">
    {{-- Header --}}
    <header x-data="{ scrolled: false, open: false }" x-on:scroll.window="scrolled = window.scrollY > 40"
        x-bind:class="scrolled || open ? 'bg-navy-950/95 shadow-lg backdrop-blur' : 'bg-transparent'"
        class="fixed inset-x-0 top-0 z-50 transition-colors duration-300">
        <div class="mx-auto flex h-18 max-w-7xl items-center justify-between gap-6 px-5 lg:px-8">
            <a href="#hero" class="flex items-center gap-2.5">
                <img src="{{ asset('assets/img/brand/barabai.png') }}" alt="Logo Kabupaten HST" class="h-9">
                <img src="{{ asset('assets/img/brand/loading_screen.png') }}" alt="" class="size-9">
                <span class="text-xl font-extrabold tracking-tight text-white">SIFKUMDA<span class="text-brand-500">.</span></span>
            </a>

            <nav class="hidden items-center gap-1 lg:flex">
                @foreach ($nav as [$href, $label])
                    <a href="{{ $href }}" class="rounded-lg px-3 py-2 text-sm font-medium text-white/80 transition hover:bg-white/10 hover:text-white">{{ $label }}</a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2">
                <a href="{{ auth()->check() ? route('dashboard') : route('login') }}"
                    class="rounded-lg border border-brand-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-500">
                    {{ auth()->check() ? 'Dashboard' : 'Login' }}
                </a>
                <button type="button" class="rounded-lg p-2 text-white lg:hidden" x-on:click="open = !open" aria-label="Menu">
                    <i class="bi text-2xl leading-none" x-bind:class="open ? 'bi-x-lg' : 'bi-list'"></i>
                </button>
            </div>
        </div>

        <nav x-show="open" x-cloak x-collapse class="border-t border-white/10 lg:hidden">
            <div class="space-y-1 px-5 py-3">
                @foreach ($nav as [$href, $label])
                    <a href="{{ $href }}" x-on:click="open = false" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-white/85 hover:bg-white/10">{{ $label }}</a>
                @endforeach
            </div>
        </nav>
    </header>

    <main>
        {{-- Hero --}}
        <section id="hero" class="relative isolate flex min-h-[92vh] items-center overflow-hidden pt-24 pb-16">
            <img src="{{ asset('assets/img/theme/sulangking.jpg') }}" alt="" class="absolute inset-0 -z-20 size-full object-cover">
            <div class="absolute inset-0 -z-10 bg-linear-to-b from-navy-950/80 via-navy-950/70 to-navy-950/90"></div>

            <div class="mx-auto w-full max-w-5xl px-5 text-center text-white">
                <p class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-xs font-semibold tracking-wide ring-1 ring-white/15 backdrop-blur">
                    <i class="bi bi-bank text-brand-500"></i> Bagian Hukum Setda Kabupaten Hulu Sungai Tengah
                </p>
                <h1 class="mt-6 text-4xl leading-tight font-extrabold text-white sm:text-5xl lg:text-6xl">
                    Sistem Fasilitasi<br class="hidden sm:block"> Produk Hukum Daerah
                </h1>
                <p class="mx-auto mt-5 max-w-2xl text-base text-white/75 sm:text-lg">
                    Platform digital yang mempermudah fasilitasi produk hukum daerah secara cepat, transparan, dan efisien.
                </p>

                <div class="mx-auto mt-12 grid max-w-3xl gap-4 text-left sm:grid-cols-2">
                    <a href="{{ route('login') }}" class="group rounded-2xl bg-white/10 p-6 ring-1 ring-white/15 backdrop-blur transition hover:-translate-y-1 hover:bg-white/15">
                        <span class="grid size-12 place-items-center rounded-xl bg-brand-500 text-xl text-white shadow-lg"><i class="bi bi-file-earmark-text"></i></span>
                        <h2 class="mt-4 text-lg font-bold text-white">Pengajuan Produk Hukum</h2>
                        <p class="mt-1 text-sm text-white/70">Ajukan rancangan produk hukum daerah dengan mudah dan cepat melalui sistem digital.</p>
                        <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-500">Masuk <i class="bi bi-arrow-right transition group-hover:translate-x-1"></i></span>
                    </a>
                    <a href="{{ asset('assets/document/SIFKUMDA-PanduanLogin.pdf') }}" target="_blank" rel="noopener" class="group rounded-2xl bg-white/10 p-6 ring-1 ring-white/15 backdrop-blur transition hover:-translate-y-1 hover:bg-white/15">
                        <span class="grid size-12 place-items-center rounded-xl bg-amber-500 text-xl text-white shadow-lg"><i class="bi bi-person-lock"></i></span>
                        <h2 class="mt-4 text-lg font-bold text-white">Panduan Login</h2>
                        <p class="mt-1 text-sm text-white/70">Pelajari cara masuk ke sistem dan mengakses fitur SIFKUMDA.</p>
                        <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-amber-400">Buka panduan <i class="bi bi-box-arrow-up-right"></i></span>
                    </a>
                </div>
            </div>
        </section>

        {{-- Tentang --}}
        <section id="about" class="scroll-mt-16 py-20 lg:py-28">
            <div class="mx-auto grid max-w-7xl items-center gap-12 px-5 transition duration-700 lg:grid-cols-2 lg:px-8" {!! $reveal !!}>
                <div>
                    <p class="text-sm font-bold tracking-wider text-brand-600 uppercase">Tentang</p>
                    <h2 class="mt-2 text-3xl font-extrabold sm:text-4xl">SIFKUMDA — Sistem Informasi Fasilitasi Produk Hukum Daerah</h2>
                    <p class="mt-5 text-lg text-slate-600 italic dark:text-slate-300">
                        Platform digital yang dirancang untuk mempermudah proses penyusunan dan fasilitasi produk hukum daerah secara efektif dan transparan.
                    </p>
                    <ul class="mt-6 space-y-4">
                        @foreach ([
                            'Mengelola produk hukum daerah seperti <strong>Peraturan Bupati</strong> dan <strong>Surat Keputusan Bupati</strong>.',
                            'Memfasilitasi <strong>pengajuan, pemeriksaan, penelitian, dan penyimpanan</strong> dokumen hukum secara digital.',
                            'Meningkatkan efisiensi dan transparansi dalam penyusunan produk hukum dengan sistem yang terintegrasi.',
                        ] as $point)
                            <li class="flex gap-3 text-slate-600 dark:text-slate-300">
                                <i class="bi bi-check2-all mt-0.5 text-xl text-brand-500"></i>
                                <span>{!! $point !!}</span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-6 text-slate-600 dark:text-slate-300">
                        Dengan SIFKUMDA, setiap tahap pembentukan produk hukum dapat dilakukan lebih mudah dan cepat, memastikan setiap
                        dokumen hukum telah melalui proses yang sesuai dengan standar dan regulasi yang berlaku.
                    </p>
                </div>
                <img src="{{ asset('assets/img/theme/Surat-Keputusan.jpg') }}" alt="Dokumen Surat Keputusan" class="w-full rounded-3xl object-cover shadow-2xl">
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
        <section id="services" class="scroll-mt-16 py-20 lg:py-28">
            <div class="mx-auto max-w-7xl px-5 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-bold tracking-wider text-brand-600 uppercase">Layanan Kami</p>
                    <h2 class="mt-2 text-3xl font-extrabold sm:text-4xl">Fasilitasi produk hukum daerah secara cepat dan transparan</h2>
                </div>
                <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($services as $i => [$icon, $title, $desc])
                        <div class="rounded-2xl border border-slate-100 bg-white p-7 shadow-card transition duration-700 hover:-translate-y-1 dark:border-navy-800 dark:bg-navy-900 dark:shadow-none"
                            style="transition-delay: {{ $i * 60 }}ms" {!! $reveal !!}>
                            <span class="grid size-12 place-items-center rounded-xl bg-brand-500/10 text-2xl text-brand-600 dark:text-brand-500"><i class="bi bi-{{ $icon }}"></i></span>
                            <h3 class="mt-5 text-lg font-bold">{{ $title }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $desc }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Statistik --}}
        <section id="stats" class="scroll-mt-16 bg-slate-50 py-20 lg:py-28 dark:bg-navy-900">
            <div class="mx-auto grid max-w-7xl items-center gap-12 px-5 lg:grid-cols-2 lg:px-8">
                <img src="{{ asset('assets/img/theme/card-1.jpg') }}" alt="" class="w-full rounded-3xl object-cover shadow-2xl">
                <div>
                    <p class="text-sm font-bold tracking-wider text-brand-600 uppercase">Statistik</p>
                    <h2 class="mt-2 text-3xl font-extrabold sm:text-4xl">Produk hukum yang tercatat</h2>
                    <p class="mt-4 text-slate-600 dark:text-slate-300">
                        Sistem ini mencatat berbagai jenis produk hukum daerah, termasuk <strong>Peraturan Bupati</strong> dan <strong>Surat Keputusan Bupati</strong>.
                    </p>
                    <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        @foreach ($stats as [$icon, $value, $group, $jenis])
                            <div class="flex items-center gap-4 rounded-2xl bg-white p-5 shadow-card dark:bg-navy-950 dark:shadow-none">
                                <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-primary-50 text-2xl text-primary-500 dark:bg-primary-500/15"><i class="bi bi-{{ $icon }}"></i></span>
                                <div>
                                    <p class="text-3xl font-extrabold text-navy-900 dark:text-white"
                                        x-data="{ v: {{ $value }} }"
                                        x-intersect.once="v = 0; const to = {{ $value }}, s = performance.now(); const t = (n) => { const p = Math.min((n - s) / 1000, 1); v = Math.round(to * p); if (p < 1) requestAnimationFrame(t); }; requestAnimationFrame(t);"
                                        x-text="v">{{ $value }}</p>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $group }} <strong class="text-slate-700 dark:text-slate-200">{{ $jenis }}</strong></p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- Kontak --}}
        <section id="contact" class="scroll-mt-16 py-20 lg:py-28">
            <div class="mx-auto max-w-7xl px-5 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-bold tracking-wider text-brand-600 uppercase">Kontak Kami</p>
                    <h2 class="mt-2 text-3xl font-extrabold sm:text-4xl">Hubungi kami untuk informasi lebih lanjut</h2>
                </div>
                <div class="mt-12 overflow-hidden rounded-3xl shadow-card ring-1 ring-slate-100 dark:ring-navy-800">
                    <iframe class="h-80 w-full border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen
                        title="Lokasi Sekretariat Daerah Kabupaten Hulu Sungai Tengah"
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15934.265820741635!2d115.38191621594372!3d-2.5891951377680394!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2de420cf5f1f93b7%3A0xa81c2d6ec6b4e62d!2sJl.%20Perwira%2C%20Barabai%20Sel.%2C%20Kec.%20Barabai%2C%20Kabupaten%20Hulu%20Sungai%20Tengah%2C%20Kalimantan%20Selatan%2071315!5e0!3m2!1sen!2sid!4v1713456789012!5m2!1sen!2sid"></iframe>
                </div>
            </div>
        </section>
    </main>

    {{-- Footer --}}
    <footer class="bg-navy-950 text-slate-400">
        <div class="mx-auto grid max-w-7xl gap-10 px-5 py-16 sm:grid-cols-2 lg:grid-cols-4 lg:px-8">
            <div class="lg:col-span-2">
                <p class="text-2xl font-extrabold text-white">SIFKUMDA<span class="text-brand-500">.</span></p>
                <p class="mt-4 max-w-sm text-sm leading-relaxed">
                    C98M+5R3, Jl. Perwira, Barabai Sel., Kec. Barabai, Kabupaten Hulu Sungai Tengah, Kalimantan Selatan 71315
                </p>
                <div class="mt-6 flex gap-3">
                    <a href="https://www.facebook.com/SetdaHSTMenyala" target="_blank" rel="noopener" aria-label="Facebook"
                        class="grid size-10 place-items-center rounded-lg bg-white/5 text-lg text-white transition hover:bg-brand-500"><i class="bi bi-facebook"></i></a>
                    <a href="https://www.instagram.com/setdahstmenyala/" target="_blank" rel="noopener" aria-label="Instagram"
                        class="grid size-10 place-items-center rounded-lg bg-white/5 text-lg text-white transition hover:bg-brand-500"><i class="bi bi-instagram"></i></a>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-bold tracking-wider text-white uppercase">Navigasi</h3>
                <ul class="mt-4 space-y-2.5 text-sm">
                    @foreach ($nav as [$href, $label])
                        <li><a href="{{ $href }}" class="transition hover:text-brand-500">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h3 class="text-sm font-bold tracking-wider text-white uppercase">Unit Terkait</h3>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="https://hstkab.go.id" target="_blank" rel="noopener" class="transition hover:text-brand-500">Pemerintah Kabupaten HST</a></li>
                    <li><a href="https://diskominfo.hstkab.go.id" target="_blank" rel="noopener" class="transition hover:text-brand-500">Dinas Kominfo HST</a></li>
                    <li><a href="https://opendata.hstkab.go.id" target="_blank" rel="noopener" class="transition hover:text-brand-500">Open Data HST</a></li>
                    <li><a href="https://lapor.go.id" target="_blank" rel="noopener" class="transition hover:text-brand-500">Layanan Aspirasi Publik</a></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-white/5 py-6 text-center text-xs">
            &copy; {{ date('Y') }} <strong class="text-white">SIFKUMDA</strong> · Dikembangkan oleh
            <span class="text-brand-500">Bagian Hukum Sekretariat Daerah Kabupaten Hulu Sungai Tengah</span>
        </div>
    </footer>

    @livewireScriptConfig
</body>

</html>
