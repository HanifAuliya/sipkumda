<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    @include('layouts.partials.head')
    <title>{{ $title ?? 'SIFKUMDA' }} | SIFKUMDA</title>
</head>

<body class="min-h-full bg-slate-100 text-slate-800 dark:bg-navy-950 dark:text-slate-200">
    @php
        $menu = 'rounded-lg px-3 py-1.5 font-medium transition';
        $aktif = 'bg-white/10 text-white';
        $biasa = 'text-white/60 hover:text-white';
    @endphp

    <header x-data class="sticky top-0 z-40 border-b border-white/10 bg-navy-950 text-white">
        <div class="flex h-14 items-center gap-6 px-4 lg:px-6">
            <a href="{{ route('v2.beranda') }}" wire:navigate class="flex items-center gap-2.5">
                <span class="grid size-8 place-items-center rounded-lg bg-brand-500 text-sm font-extrabold text-navy-950">S</span>
                <span class="text-sm font-bold tracking-tight">SIFKUMDA</span>
            </a>

            <nav class="hidden items-center gap-1 text-sm sm:flex">
                <a href="{{ route('v2.beranda') }}" wire:navigate @class([$menu, $aktif => request()->routeIs('v2.beranda'), $biasa => ! request()->routeIs('v2.beranda')])>Pekerjaan</a>
                <a href="{{ route('jdih.index') }}" @class([$menu, $biasa])>JDIH</a>
                @hasanyrole('Admin|Verifikator|Super Admin')
                    <a href="{{ route('v2.pengaturan') }}" wire:navigate @class([$menu, $aktif => request()->routeIs('v2.pengaturan'), $biasa => ! request()->routeIs('v2.pengaturan')])>Pengaturan</a>
                @endhasanyrole
            </nav>

            <div class="ml-auto flex items-center gap-1 text-sm sm:gap-2">
                <button type="button" x-on:click="toggleTheme()" class="rounded-lg p-2 text-white/70 transition hover:bg-white/10 hover:text-white" aria-label="Ganti tema">
                    <i class="bi bi-moon-stars text-lg leading-none dark:hidden"></i>
                    <i class="bi bi-brightness-high hidden text-lg leading-none dark:inline"></i>
                </button>

                <livewire:v2.lonceng />

                <x-ui.dropdown width="w-60">
                    <x-slot:trigger>
                        <button type="button" class="flex items-center gap-2 rounded-full py-1 pr-2 pl-1 transition hover:bg-white/10">
                            <span class="grid size-8 place-items-center rounded-full bg-white/10 text-xs font-bold">{{ mb_strtoupper(mb_substr(auth()->user()->nama_user, 0, 1)) }}</span>
                            <span class="hidden max-w-40 truncate text-xs font-semibold md:block">{{ auth()->user()->nama_user }}</span>
                            <i class="bi bi-chevron-down hidden text-xs text-white/50 md:block"></i>
                        </button>
                    </x-slot:trigger>

                    <div class="border-b border-slate-100 px-3 pt-1.5 pb-2.5 dark:border-navy-700">
                        <p class="truncate text-sm font-semibold text-navy-900 dark:text-white">{{ auth()->user()->nama_user }}</p>
                        <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->getRoleNames()->join(', ') }}</p>
                    </div>
                    <div class="pt-1.5">
                        <x-ui.dropdown-item :href="route('profile.edit')" icon="person">Profil Saya</x-ui.dropdown-item>
                        <x-ui.dropdown-item :href="route('dashboard')" icon="box-arrow-up-left">Aplikasi lama</x-ui.dropdown-item>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-ui.dropdown-item type="submit" icon="box-arrow-right" danger>Keluar</x-ui.dropdown-item>
                        </form>
                    </div>
                </x-ui.dropdown>
            </div>
        </div>
    </header>

    {{ $slot }}

    @livewireScriptConfig
    @stack('scripts')
</body>

</html>
