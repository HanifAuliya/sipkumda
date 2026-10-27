<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    @include('layouts.partials.head')
    <title>@yield('title', 'Login') | SIFKUMDA</title>
    <meta property="og:title" content="SIFKUMDA — Sistem Fasilitasi Produk Hukum Daerah">
    <meta property="og:description" content="Pengajuan, fasilitasi, dan dokumentasi produk hukum daerah Kabupaten Hulu Sungai Tengah.">
    <meta property="og:image" content="{{ asset('assets/img/brand/loading_screen.png') }}">
    <link rel="canonical" href="{{ url()->current() }}">
</head>

<body class="min-h-full">
    <div class="grid min-h-screen lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)]">
        {{-- Panel identitas --}}
        <aside class="relative hidden flex-col items-center justify-center overflow-hidden px-12 py-16 text-center lg:flex">
            <div class="pointer-events-none absolute -top-32 -left-32 size-96 rounded-full bg-primary-100/60 blur-3xl dark:bg-primary-500/10"></div>
            <div class="pointer-events-none absolute -right-24 -bottom-32 size-96 rounded-full bg-emerald-100/60 blur-3xl dark:bg-emerald-500/10"></div>

            <div class="relative max-w-lg">
                <img src="{{ asset('assets/img/brand/loading_screen.png') }}" alt="Logo SIFKUMDA" class="mx-auto size-44 drop-shadow-xl">
                <h1 class="mt-6 text-5xl font-extrabold tracking-tight text-amber-500 dark:text-amber-400">SIFKUMDA</h1>
                <p class="mt-2 text-lg font-semibold text-navy-900 dark:text-slate-200">Sistem Fasilitasi Produk Hukum Daerah</p>
                <p class="mt-6 leading-relaxed text-slate-500 dark:text-slate-400">
                    Platform digital yang mempermudah
                    <span class="font-semibold text-primary-600 dark:text-primary-300">pengajuan</span>,
                    <span class="font-semibold text-primary-600 dark:text-primary-300">fasilitasi</span>, dan
                    <span class="font-semibold text-primary-600 dark:text-primary-300">dokumentasi</span>
                    produk hukum daerah secara elektronik — lebih
                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">transparan</span>,
                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">efisien</span>, dan
                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">terintegrasi</span>.
                </p>
            </div>
        </aside>

        {{-- Form --}}
        <main class="flex flex-col justify-center bg-white px-6 py-10 shadow-[-1rem_0_3rem_-2rem_rgb(23_43_77/0.15)] sm:px-12 lg:rounded-l-[2.5rem] dark:bg-navy-900 dark:shadow-none">
            <div class="mx-auto w-full max-w-sm">
                {{-- Logo ringkas di layar kecil --}}
                <a href="{{ url('/') }}" class="mb-8 flex items-center justify-center gap-3 lg:hidden">
                    <img src="{{ asset('assets/img/brand/loading_screen.png') }}" alt="" class="size-12">
                    <span class="text-2xl font-extrabold text-amber-500">SIFKUMDA</span>
                </a>

                {{ $slot }}

                <p class="mt-10 text-center text-xs text-slate-400">
                    &copy; {{ date('Y') }} Bagian Hukum Sekretariat Daerah Kabupaten Hulu Sungai Tengah
                </p>
            </div>
        </main>
    </div>

    @livewireScriptConfig
</body>

</html>
