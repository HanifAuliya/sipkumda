<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    @include('layouts.partials.head')
    <title>@isset($title){{ $title }}@else @yield('title', 'Dashboard')@endisset | SIFKUMDA Hulu Sungai Tengah</title>
</head>

<body class="min-h-full"
    x-data="{ sidebarOpen: false, sidebarCollapsed: $persist(false).as('sidebar-collapsed') }"
    x-effect="gantiMenuCiut(sidebarCollapsed)"
    x-on:keydown.escape.window="sidebarOpen = false">

    @include('layouts.partials.splash')
    @include('layouts.partials.sidebar')

    <div class="flex min-h-screen flex-col lg:pl-72" data-konten-utama>
        @include('layouts.partials.topbar')

        <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto w-full max-w-[1600px] animate-fade-in" data-isi-halaman>
                {{ $slot }}
            </div>
        </main>

        @include('layouts.partials.footer')
    </div>

    {{-- Modal detail naskah bersama: Livewire.dispatch('bukaDetailNaskah', { slug }) --}}
    <livewire:v2.detail-naskah />

    @include('layouts.partials.tur')

    {{-- Dipakai resources/js/navigasi.js bila server lambat saat berpindah menu. --}}
    <template id="kerangka-halaman">@include('layouts.partials.kerangka')</template>

    @livewireScriptConfig
    @stack('scripts')
</body>

</html>
