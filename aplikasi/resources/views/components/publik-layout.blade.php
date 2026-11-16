{{--
    Layout halaman publik SIFKUMDA (beranda & sub-halaman JDIH). Halaman ini dilayani
    tanpa sesi, jadi jangan memakai csrf_token(), session(), auth(), atau komponen Livewire.

    <x-publik-layout title="Produk Hukum" description="..." :transparan="true"> ... </x-publik-layout>
    - transparan : header transparan di atas hero (beranda)
--}}
@props([
    'title' => null,
    'description' => 'SIFKUMDA — Sistem Informasi Fasilitasi Produk Hukum Daerah Kabupaten Hulu Sungai Tengah: pengajuan, penelitian, fasilitasi, dan dokumentasi produk hukum secara digital.',
    'image' => null,
    'transparan' => false,
])

@php
    $beranda = route('beranda');
    $nav = [
        [$beranda, 'Beranda', request()->routeIs('beranda'), null],
        [$beranda . '#alur', 'Alur', false, null],
        [$beranda . '#layanan', 'Layanan', false, null],
        [route('jdih.index'), 'Produk Hukum', request()->routeIs('jdih.*'), 'JDIH'],
        [$beranda . '#kontak', 'Kontak', false, null],
    ];
    $judul = $title ? "{$title} | SIFKUMDA" : 'SIFKUMDA — Sistem Informasi Fasilitasi Produk Hukum Daerah Kabupaten Hulu Sungai Tengah';
@endphp

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="theme-color" content="#172b4d">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" href="{{ asset('assets/img/brand/favicon.ico') }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="SIFKUMDA">
    <meta property="og:title" content="{{ $judul }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:image" content="{{ $image ?? asset('assets/img/theme/sulangking.jpg') }}">
    {{ $head ?? '' }}

    {{-- Tampilan awal selalu terang; tema gelap hanya lewat tombol di halaman. --}}
    <meta name="color-scheme" content="only light">
    <script>
        (() => {
            let theme = null;
            try { theme = localStorage.getItem('theme'); } catch (e) {}
            document.documentElement.classList.toggle('dark', theme === 'dark');
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="flex min-h-screen flex-col bg-white text-slate-700 dark:bg-navy-950 dark:text-slate-300">
    <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-navy-900">Lewati ke konten</a>

    <header x-data="{ scrolled: {{ $transparan ? 'false' : 'true' }}, open: false }"
        @if ($transparan) x-init="scrolled = window.scrollY > 40" x-on:scroll.window="scrolled = window.scrollY > 40" @endif
        x-bind:class="scrolled || open ? 'bg-navy-950/90 shadow-lg shadow-navy-950/10 backdrop-blur-md' : 'bg-transparent'"
        @class(['inset-x-0 top-0 z-50 transition-colors duration-300', 'fixed' => $transparan, 'sticky bg-navy-950' => ! $transparan])>
        <div class="mx-auto flex h-18 max-w-7xl items-center justify-between gap-6 px-5 lg:px-8">
            <a href="{{ $beranda }}" class="flex items-center gap-3">
                <img src="{{ asset('assets/img/brand/barabai.png') }}" alt="Logo Kabupaten Hulu Sungai Tengah" class="h-9">
                <img src="{{ asset('assets/img/brand/loading_screen_terang.png') }}" alt="" class="hidden size-9 sm:block">
                <span class="leading-tight">
                    <span class="block text-lg font-extrabold tracking-tight text-white">SIFKUMDA<span class="text-brand-500">.</span></span>
                    <span class="hidden text-[0.7rem] text-white/60 sm:block">Kabupaten Hulu Sungai Tengah</span>
                </span>
            </a>

            <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigasi utama">
                @foreach ($nav as [$href, $label, $aktif, $lencana])
                    <a href="{{ $href }}" @if ($aktif) aria-current="page" @endif
                        @class(['inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium transition hover:bg-white/10 hover:text-white', 'bg-white/10 text-white' => $aktif, 'text-white/80' => ! $aktif])>
                        {{ $label }}
                        @if ($lencana)
                            <span class="rounded bg-brand-500/20 px-1.5 py-px text-[0.6rem] font-bold tracking-wider text-brand-500 dark:text-emerald-300">{{ $lencana }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2 text-white">
                <x-ui.tombol-tema />
                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-brand-500/20 transition hover:bg-brand-600 dark:text-navy-950">
                    <i class="bi bi-box-arrow-in-right"></i> Masuk
                </a>
                <button type="button" class="rounded-lg p-2 text-white lg:hidden" x-on:click="open = !open" aria-label="Menu" x-bind:aria-expanded="open">
                    <i class="bi text-2xl leading-none" x-bind:class="open ? 'bi-x-lg' : 'bi-list'"></i>
                </button>
            </div>
        </div>

        <nav x-show="open" x-cloak x-collapse class="border-t border-white/10 lg:hidden" aria-label="Navigasi seluler">
            <div class="space-y-1 px-5 py-3">
                @foreach ($nav as [$href, $label, $aktif, $lencana])
                    <a href="{{ $href }}" x-on:click="open = false" class="flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium text-white/85 hover:bg-white/10">
                        {{ $label }}
                        @if ($lencana)<span class="rounded bg-brand-500/20 px-1.5 text-[0.6rem] font-bold text-brand-500">{{ $lencana }}</span>@endif
                    </a>
                @endforeach
            </div>
        </nav>
    </header>

    <main id="konten" class="flex-1">
        {{ $slot }}
    </main>

    <footer class="bg-navy-950 text-slate-400">
        <div class="mx-auto grid max-w-7xl gap-10 px-5 py-14 sm:grid-cols-2 lg:grid-cols-4 lg:px-8">
            <div class="lg:col-span-2">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('assets/img/brand/loading_screen_terang.png') }}" alt="" class="size-10">
                    <p class="text-2xl font-extrabold text-white">SIFKUMDA<span class="text-brand-500">.</span></p>
                </div>
                <p class="mt-4 max-w-md text-sm leading-relaxed">
                    Sistem Informasi Fasilitasi Produk Hukum Daerah — Bagian Hukum Sekretariat Daerah Kabupaten Hulu Sungai Tengah.
                </p>
                <p class="mt-3 flex max-w-md gap-2 text-sm leading-relaxed">
                    <i class="bi bi-geo-alt mt-0.5 text-brand-500"></i>
                    C98M+5R3, Jl. Perwira, Barabai Sel., Kec. Barabai, Kabupaten Hulu Sungai Tengah, Kalimantan Selatan 71315
                </p>
                <div class="mt-5 flex gap-3">
                    <a href="https://www.facebook.com/SetdaHSTMenyala" target="_blank" rel="noopener" aria-label="Facebook"
                        class="grid size-10 place-items-center rounded-lg bg-white/5 text-lg text-white transition hover:bg-brand-500"><i class="bi bi-facebook"></i></a>
                    <a href="https://www.instagram.com/setdahstmenyala/" target="_blank" rel="noopener" aria-label="Instagram"
                        class="grid size-10 place-items-center rounded-lg bg-white/5 text-lg text-white transition hover:bg-brand-500"><i class="bi bi-instagram"></i></a>
                </div>
            </div>

            <div>
                <h2 class="text-sm font-bold tracking-wider text-white uppercase">SIFKUMDA</h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ $beranda }}#alur" class="transition hover:text-brand-500">Alur fasilitasi</a></li>
                    <li><a href="{{ $beranda }}#layanan" class="transition hover:text-brand-500">Layanan</a></li>
                    <li><a href="{{ route('jdih.index') }}" class="transition hover:text-brand-500">Produk hukum (JDIH)</a></li>
                    <li><a href="{{ route('login') }}" class="transition hover:text-brand-500">Masuk</a></li>
                </ul>
            </div>

            <div>
                <h2 class="text-sm font-bold tracking-wider text-white uppercase">Unit Terkait</h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="https://hstkab.go.id" target="_blank" rel="noopener" class="transition hover:text-brand-500">Pemerintah Kabupaten HST</a></li>
                    <li><a href="https://diskominfo.hstkab.go.id" target="_blank" rel="noopener" class="transition hover:text-brand-500">Dinas Kominfo HST</a></li>
                    <li><a href="https://opendata.hstkab.go.id" target="_blank" rel="noopener" class="transition hover:text-brand-500">Open Data HST</a></li>
                    <li><a href="https://lapor.go.id" target="_blank" rel="noopener" class="transition hover:text-brand-500">Layanan Aspirasi Publik</a></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-white/5 py-6 text-center text-xs">
            &copy; {{ date('Y') }} <strong class="text-white">SIFKUMDA</strong> · Bagian Hukum Sekretariat Daerah Kabupaten Hulu Sungai Tengah
        </div>
    </footer>

    {{-- Wajib ada: tanpa ini Livewire menyisipkan skripnya sendiri dan Alpine termuat dua kali. --}}
    @livewireScriptConfig
</body>

</html>
