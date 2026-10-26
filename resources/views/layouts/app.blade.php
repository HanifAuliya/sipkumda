<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    @include('layouts.partials.head')
    <title>@yield('title', 'Dashboard') | SIFKUMDA Hulu Sungai Tengah</title>
</head>

<body class="min-h-full"
    x-data="{ sidebarOpen: false, sidebarCollapsed: $persist(false).as('sidebar-collapsed') }"
    x-on:keydown.escape.window="sidebarOpen = false">

    @include('layouts.partials.splash')
    @include('layouts.partials.sidebar')

    <div class="flex min-h-screen flex-col transition-[padding] duration-200"
        x-bind:class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-72'">
        @include('layouts.partials.topbar')

        <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto w-full max-w-[1600px]">
                {{ $slot }}
            </div>
        </main>

        @include('layouts.partials.footer')
    </div>

    {{-- Modal detail rancangan yang dibuka dari dropdown notifikasi. --}}
    <livewire:notification-modal.detail-rancangan />

    @livewireScriptConfig
    @stack('scripts')
</body>

</html>
