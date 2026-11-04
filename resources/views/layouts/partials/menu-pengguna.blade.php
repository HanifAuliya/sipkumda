{{--
    Tema, notifikasi, dan menu pengguna. Warnanya mengikuti warna teks pembungkusnya,
    jadi cocok untuk topbar navy maupun header putih lembar kerja.
--}}
<button type="button" x-on:click="toggleTheme()"
    class="rounded-lg p-2 text-current/80 transition hover:bg-current/10 hover:text-current" aria-label="Ganti tema">
    <i class="bi bi-moon-stars text-lg leading-none dark:hidden"></i>
    <i class="bi bi-brightness-high hidden text-lg leading-none dark:inline"></i>
</button>

@if (\App\Support\Navigation::modeV2())
    <livewire:v2.lonceng wire:key="lonceng-pengguna" />
@else
    <livewire:notification-dropdown />
@endif

<x-ui.dropdown width="w-60">
    <x-slot:trigger>
        <button type="button" class="flex items-center gap-2.5 rounded-full py-1 pr-2 pl-1 transition hover:bg-current/10">
            <img src="{{ asset('assets/img/theme/profile.png') }}" alt="" class="size-8 rounded-full ring-2 ring-current/20">
            <span class="hidden max-w-40 truncate text-sm font-semibold lg:block">{{ auth()->user()->nama_user }}</span>
            <i class="bi bi-chevron-down hidden text-xs text-current/60 lg:block"></i>
        </button>
    </x-slot:trigger>

    <div class="border-b border-slate-100 px-3 pt-1.5 pb-2.5 dark:border-navy-700">
        <p class="truncate text-sm font-semibold text-navy-900 dark:text-white">{{ auth()->user()->nama_user }}</p>
        <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->getRoleNames()->join(', ') }}</p>
    </div>
    <div class="pt-1.5">
        <x-ui.dropdown-item :href="route('profile.edit')" icon="person">Profil Saya</x-ui.dropdown-item>
        <x-ui.dropdown-item :href="route('aplikasi.pilih')" icon="arrow-left-right">Pindah Aplikasi</x-ui.dropdown-item>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-ui.dropdown-item type="submit" icon="box-arrow-right" danger>Keluar</x-ui.dropdown-item>
        </form>
    </div>
</x-ui.dropdown>
