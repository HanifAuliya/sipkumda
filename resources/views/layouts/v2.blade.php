<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    @include('layouts.partials.head')
    <title>{{ $title ?? 'SIFKUMDA' }} | SIFKUMDA</title>
</head>

<body class="min-h-full bg-slate-100 text-slate-800 dark:bg-navy-950 dark:text-slate-200">
    <header class="sticky top-0 z-40 border-b border-white/10 bg-navy-950 text-white">
        <div class="flex h-14 items-center gap-6 px-4 lg:px-6">
            <a href="{{ route('v2.beranda') }}" wire:navigate class="flex items-center gap-2.5">
                <span class="grid size-8 place-items-center rounded-lg bg-brand-500 text-sm font-extrabold text-navy-950">S</span>
                <span class="text-sm font-bold tracking-tight">SIFKUMDA</span>
            </a>

            <nav class="hidden items-center gap-1 text-sm sm:flex">
                <a href="{{ route('v2.beranda') }}" wire:navigate
                    @class(['rounded-lg px-3 py-1.5 font-medium transition', 'bg-white/10 text-white' => request()->routeIs('v2.beranda'), 'text-white/60 hover:text-white' => ! request()->routeIs('v2.beranda')])>
                    Pekerjaan
                </a>
                @hasanyrole('Admin|Verifikator|Super Admin')
                    <a href="{{ route('v2.pengaturan') }}" wire:navigate
                        @class(['rounded-lg px-3 py-1.5 font-medium transition', 'bg-white/10 text-white' => request()->routeIs('v2.pengaturan'), 'text-white/60 hover:text-white' => ! request()->routeIs('v2.pengaturan')])>
                        Pengaturan
                    </a>
                @endhasanyrole
            </nav>

            <div class="ml-auto flex items-center gap-4 text-sm">
                <a href="{{ route('dashboard') }}" class="hidden text-white/50 transition hover:text-white md:inline">Aplikasi lama</a>
                <span class="hidden text-right leading-tight sm:block">
                    <span class="block text-xs font-semibold">{{ auth()->user()->nama_user }}</span>
                    <span class="block text-[0.7rem] text-white/50">{{ auth()->user()->getRoleNames()->first() }}</span>
                </span>
                <span class="grid size-8 place-items-center rounded-full bg-white/10 text-xs font-bold">
                    {{ mb_strtoupper(mb_substr(auth()->user()->nama_user, 0, 1)) }}
                </span>
            </div>
        </div>
    </header>

    {{ $slot }}

    @livewireScriptConfig
    @stack('scripts')
</body>

</html>
