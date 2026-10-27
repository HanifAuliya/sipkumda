{{--
    Layout halaman publik (beranda & JDIH). Halaman ini dilayani tanpa sesi,
    jadi jangan memakai csrf_token(), session(), auth(), atau komponen Livewire.

    <x-publik-layout title="Cari Produk Hukum" description="..." :transparan="true"> ... </x-publik-layout>
    - transparan : header transparan di atas hero (beranda)
--}}
@props([
    'title' => null,
    'description' => 'JDIH SIFKUMDA — pencarian produk hukum daerah Kabupaten Hulu Sungai Tengah: Peraturan Bupati dan Keputusan Bupati.',
    'image' => null,
    'transparan' => false,
])

@php
    $nav = [
        [route('beranda'), 'Beranda', request()->routeIs('beranda')],
        [route('jdih.index'), 'Produk Hukum', request()->routeIs('jdih.*')],
        [route('beranda') . '#about', 'Tentang', false],
        [route('beranda') . '#contact', 'Kontak', false],
    ];
    $judul = $title ? "{$title} | JDIH SIFKUMDA" : 'JDIH SIFKUMDA — Produk Hukum Daerah Kabupaten Hulu Sungai Tengah';
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
    <meta property="og:site_name" content="JDIH SIFKUMDA">
    <meta property="og:title" content="{{ $judul }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:image" content="{{ $image ?? asset('assets/img/theme/sulangking.jpg') }}">
    {{ $head ?? '' }}

    <script>
        (() => {
            let theme = null;
            try { theme = localStorage.getItem('theme'); } catch (e) {}
            const dark = theme ? theme === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="flex min-h-screen flex-col bg-white text-slate-700 dark:bg-navy-950 dark:text-slate-300">
    <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-navy-900">Lewati ke konten</a>

    <header x-data="{ scrolled: {{ $transparan ? 'false' : 'true' }}, open: false }"
        @if ($transparan) x-on:scroll.window="scrolled = window.scrollY > 40" @endif
        x-bind:class="scrolled || open ? 'bg-navy-950/95 shadow-lg backdrop-blur' : 'bg-transparent'"
        @class(['inset-x-0 top-0 z-50 transition-colors duration-300', 'fixed' => $transparan, 'sticky bg-navy-950' => ! $transparan])>
        <div class="mx-auto flex h-18 max-w-7xl items-center justify-between gap-6 px-5 lg:px-8">
            <a href="{{ route('beranda') }}" class="flex items-center gap-2.5">
                <img src="{{ asset('assets/img/brand/barabai.png') }}" alt="Logo Kabupaten Hulu Sungai Tengah" class="h-9">
                <span class="leading-tight">
                    <span class="block text-base font-extrabold tracking-tight whitespace-nowrap text-white sm:text-lg">JDIH SIFKUMDA<span class="text-brand-500">.</span></span>
                    <span class="hidden text-[0.7rem] text-white/60 sm:block">Kabupaten Hulu Sungai Tengah</span>
                </span>
            </a>

            <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigasi utama">
                @foreach ($nav as [$href, $label, $aktif])
                    <a href="{{ $href }}" @if ($aktif) aria-current="page" @endif
                        @class(['rounded-lg px-3 py-2 text-sm font-medium transition hover:bg-white/10 hover:text-white', 'bg-white/10 text-white' => $aktif, 'text-white/80' => ! $aktif])>{{ $label }}</a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2">
                <button type="button" class="rounded-lg p-2 text-white/80 hover:bg-white/10 hover:text-white" aria-label="Ganti tema"
                    x-on:click="const d = document.documentElement.classList.toggle('dark'); try { localStorage.setItem('theme', d ? 'dark' : 'light') } catch (e) {}">
                    <i class="bi bi-moon-stars dark:hidden"></i><i class="bi bi-sun hidden dark:inline"></i>
                </button>
                <a href="{{ route('login') }}" class="rounded-lg border border-brand-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-500">Masuk</a>
                <button type="button" class="rounded-lg p-2 text-white lg:hidden" x-on:click="open = !open" aria-label="Menu" x-bind:aria-expanded="open">
                    <i class="bi text-2xl leading-none" x-bind:class="open ? 'bi-x-lg' : 'bi-list'"></i>
                </button>
            </div>
        </div>

        <nav x-show="open" x-cloak x-collapse class="border-t border-white/10 lg:hidden" aria-label="Navigasi seluler">
            <div class="space-y-1 px-5 py-3">
                @foreach ($nav as [$href, $label])
                    <a href="{{ $href }}" x-on:click="open = false" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-white/85 hover:bg-white/10">{{ $label }}</a>
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
                <p class="text-2xl font-extrabold text-white">JDIH SIFKUMDA<span class="text-brand-500">.</span></p>
                <p class="mt-3 max-w-md text-sm leading-relaxed">
                    Jaringan Dokumentasi dan Informasi Hukum Bagian Hukum Sekretariat Daerah Kabupaten Hulu Sungai Tengah.
                    C98M+5R3, Jl. Perwira, Barabai Sel., Kec. Barabai, Kalimantan Selatan 71315.
                </p>
                <div class="mt-5 flex gap-3">
                    <a href="https://www.facebook.com/SetdaHSTMenyala" target="_blank" rel="noopener" aria-label="Facebook"
                        class="grid size-10 place-items-center rounded-lg bg-white/5 text-lg text-white transition hover:bg-brand-500"><i class="bi bi-facebook"></i></a>
                    <a href="https://www.instagram.com/setdahstmenyala/" target="_blank" rel="noopener" aria-label="Instagram"
                        class="grid size-10 place-items-center rounded-lg bg-white/5 text-lg text-white transition hover:bg-brand-500"><i class="bi bi-instagram"></i></a>
                </div>
            </div>

            <div>
                <h2 class="text-sm font-bold tracking-wider text-white uppercase">Jelajahi</h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('jdih.index') }}" class="transition hover:text-brand-500">Semua produk hukum</a></li>
                    <li><a href="{{ route('jdih.index', ['jenis' => 'Peraturan Bupati']) }}" class="transition hover:text-brand-500">Peraturan Bupati</a></li>
                    <li><a href="{{ route('jdih.index', ['urut' => 'terbaru']) }}" class="transition hover:text-brand-500">Terbaru ditetapkan</a></li>
                    <li><a href="{{ route('login') }}" class="transition hover:text-brand-500">Masuk SIFKUMDA</a></li>
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
