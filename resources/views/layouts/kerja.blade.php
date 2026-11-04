<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    @include('layouts.partials.head')
    <title>{{ $title ?? 'Naskah' }} | SIFKUMDA Hulu Sungai Tengah</title>
</head>

{{-- Layar kerja naskah: tanpa topbar dan sidebar menu aplikasi. Headernya ada di halaman itu sendiri, karena memuat data naskah. --}}
<body class="h-full overflow-hidden"
    x-data="{ sidebarOpen: false }"
    x-on:keydown.escape.window="sidebarOpen = false">

    {{ $slot }}

    <livewire:v2.detail-naskah />

    @livewireScriptConfig
    @stack('scripts')
</body>

</html>
