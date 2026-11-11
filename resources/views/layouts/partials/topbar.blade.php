{{-- Topbar navy: tombol menu, judul aplikasi, tema, notifikasi, menu pengguna. --}}
<header class="sticky top-0 z-30 bg-navy-900 text-white shadow-md dark:shadow-none dark:ring-1 dark:ring-white/5">
    <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
        {{-- Menu (mobile: buka laci, desktop: ciutkan) --}}
        <button type="button" class="grid size-10 shrink-0 place-items-center rounded-lg text-white/80 transition hover:bg-white/10 hover:text-white lg:hidden"
            x-on:click="sidebarOpen = true" aria-label="Buka menu">
            <i class="bi bi-list text-2xl" aria-hidden="true"></i>
        </button>
        <button type="button" class="hidden size-10 shrink-0 place-items-center rounded-lg text-white/80 transition hover:bg-white/10 hover:text-white lg:grid"
            x-on:click="sidebarCollapsed = !sidebarCollapsed" x-bind:aria-label="sidebarCollapsed ? 'Lebarkan menu' : 'Ciutkan menu'">
            {{-- Panah ke kiri = ciutkan, ke kanan = lebarkan. --}}
            <i class="bi text-xl" aria-hidden="true" x-bind:class="sidebarCollapsed ? 'bi-text-indent-right' : 'bi-text-indent-left'"></i>
        </button>

        <p class="truncate text-sm font-semibold tracking-wide text-white/90">
            <span class="hidden md:inline">Sistem Fasilitasi Produk Hukum Daerah</span>
            <span class="md:hidden">SIFKUMDA</span>
        </p>

        <div class="ml-auto flex items-center gap-1 sm:gap-2">
            @include('layouts.partials.menu-pengguna')
        </div>
    </div>
</header>
