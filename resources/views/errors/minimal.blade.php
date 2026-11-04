{{-- Layout semua halaman error. Halaman per kode hanya mengisi title, code, message, dan (opsional) icon/description. --}}
<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    @include('layouts.partials.head')
    <title>@yield('title') | SIFKUMDA</title>
</head>

<body class="grid min-h-full place-items-center px-6 py-16">
    <main class="max-w-lg text-center">
        <span class="mx-auto grid size-20 place-items-center rounded-full bg-primary-50 text-4xl text-primary-500 dark:bg-primary-500/15">
            <i class="bi bi-@yield('icon', 'exclamation-triangle')"></i>
        </span>
        <p class="mt-6 text-7xl font-extrabold tracking-tight text-navy-900 dark:text-white">@yield('code')</p>
        <h1 class="mt-3 text-2xl font-bold">@yield('message')</h1>
        <p class="mt-3 text-slate-500 dark:text-slate-400">@yield('description', 'Silakan kembali ke halaman sebelumnya atau ke dashboard.')</p>

        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <button type="button" onclick="history.back()"
                class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-soft ring-1 ring-slate-200 transition hover:bg-slate-50 dark:bg-navy-900 dark:text-slate-200 dark:ring-navy-700">
                <i class="bi bi-arrow-left"></i> Kembali
            </button>
            <a href="{{ auth()->check() ? route('dashboard') : url('/') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition hover:bg-primary-600">
                <i class="bi bi-house"></i> {{ auth()->check() ? 'Ke Dashboard' : 'Ke Beranda' }}
            </a>
        </div>

        <p class="mt-12 flex items-center justify-center gap-2 text-xs text-slate-400">
            <img src="{{ asset('assets/img/brand/loading_screen.png') }}" alt="" class="size-5"> SIFKUMDA Hulu Sungai Tengah
        </p>
    </main>

    @livewireScriptConfig
</body>

</html>
