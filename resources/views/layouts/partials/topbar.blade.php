{{-- Topbar navy: tombol menu, judul aplikasi, tema, notifikasi, menu pengguna. --}}
<header class="sticky top-0 z-30 bg-navy-900 text-white shadow-md dark:bg-navy-950 dark:ring-1 dark:ring-white/5">
    <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
        {{-- Menu (mobile: buka laci, desktop: ciutkan) --}}
        <button type="button" class="rounded-lg p-2 text-white/80 transition hover:bg-white/10 hover:text-white lg:hidden"
            x-on:click="sidebarOpen = true" aria-label="Buka menu">
            <i class="bi bi-list text-2xl leading-none"></i>
        </button>
        <button type="button" class="hidden rounded-lg p-2 text-white/80 transition hover:bg-white/10 hover:text-white lg:block"
            x-on:click="sidebarCollapsed = !sidebarCollapsed" aria-label="Ciutkan menu">
            <i class="bi bi-text-indent-right text-xl leading-none" x-bind:class="sidebarCollapsed && 'bi-text-indent-left'"></i>
        </button>

        <p class="truncate text-sm font-semibold tracking-wide text-white/90">
            <span class="hidden md:inline">Sistem Fasilitasi Produk Hukum Daerah</span>
            <span class="md:hidden">SIFKUMDA</span>
        </p>

        <div class="ml-auto flex items-center gap-1 sm:gap-2">
            {{-- Tema terang / gelap --}}
            <button type="button" x-on:click="toggleTheme()"
                class="rounded-lg p-2 text-white/80 transition hover:bg-white/10 hover:text-white" aria-label="Ganti tema">
                <i class="bi bi-moon-stars text-lg leading-none dark:hidden"></i>
                <i class="bi bi-brightness-high hidden text-lg leading-none dark:inline"></i>
            </button>

            <livewire:notification-dropdown />

            {{-- Menu pengguna --}}
            <x-ui.dropdown width="w-60">
                <x-slot:trigger>
                    <button type="button" class="flex items-center gap-2.5 rounded-full py-1 pr-2 pl-1 transition hover:bg-white/10">
                        <img src="{{ asset('assets/img/theme/profile.png') }}" alt="" class="size-8 rounded-full ring-2 ring-white/20">
                        <span class="hidden max-w-40 truncate text-sm font-semibold lg:block">{{ auth()->user()->nama_user }}</span>
                        <i class="bi bi-chevron-down hidden text-xs text-white/60 lg:block"></i>
                    </button>
                </x-slot:trigger>

                <div class="border-b border-slate-100 px-3 pt-1.5 pb-2.5 dark:border-navy-700">
                    <p class="truncate text-sm font-semibold text-navy-900 dark:text-white">{{ auth()->user()->nama_user }}</p>
                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->getRoleNames()->join(', ') }}</p>
                </div>
                <div class="pt-1.5">
                    <x-ui.dropdown-item :href="route('profile.edit')" icon="person">Profil Saya</x-ui.dropdown-item>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-ui.dropdown-item type="submit" icon="box-arrow-right" danger>Keluar</x-ui.dropdown-item>
                    </form>
                </div>
            </x-ui.dropdown>
        </div>
    </div>
</header>
