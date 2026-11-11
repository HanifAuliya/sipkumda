{{--
    Tema, notifikasi, dan menu pengguna. Warnanya mengikuti warna teks pembungkusnya,
    jadi cocok untuk topbar navy maupun header putih lembar kerja.
--}}
<x-ui.tombol-tema />

<livewire:v2.lonceng wire:key="lonceng-pengguna" />

<x-ui.dropdown width="w-60">
    <x-slot:trigger>
        <button type="button" class="flex min-h-10 items-center gap-2.5 rounded-full py-1 pr-1 pl-1 transition hover:bg-current/10 lg:pr-2.5" aria-label="Menu pengguna">
            <img src="{{ auth()->user()->fotoUrl() }}" alt="" class="size-8 rounded-full object-cover ring-2 ring-current/20" x-on:foto-berubah.window="$el.src = $event.detail.url">
            <span class="hidden max-w-40 truncate text-sm font-semibold lg:block">{{ auth()->user()->nama_user }}</span>
            <i class="bi bi-chevron-down hidden text-sm text-current/60 transition-transform duration-150 lg:block" x-bind:class="open && 'rotate-180'" aria-hidden="true"></i>
        </button>
    </x-slot:trigger>

    <div class="border-b border-slate-100 px-3 pt-1.5 pb-2.5 dark:border-navy-700">
        <p class="truncate text-sm font-semibold text-navy-900 dark:text-white">{{ auth()->user()->nama_user }}</p>
        <p class="truncate text-sm text-slate-500 dark:text-slate-400">{{ auth()->user()->getRoleNames()->join(', ') }}</p>
    </div>
    <div class="pt-1.5">
        <x-ui.dropdown-item :href="route('profile.edit')" icon="person">Profil Saya</x-ui.dropdown-item>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-ui.dropdown-item type="submit" icon="box-arrow-right" danger>Keluar</x-ui.dropdown-item>
        </form>
    </div>
</x-ui.dropdown>
