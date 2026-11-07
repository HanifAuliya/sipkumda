{{-- Diperiksa tiap 60 detik, dan langsung saat pengguna kembali ke tab ini. --}}
<div wire:poll.60s="periksa" x-data x-on:visibilitychange.document="document.visibilityState === 'visible' && $wire.periksa()">
    <x-ui.dropdown width="w-[26rem] max-w-[calc(100vw-1.5rem)]">
        <x-slot:trigger>
            <button type="button" class="relative grid size-10 shrink-0 place-items-center rounded-lg text-current/80 transition hover:bg-current/10 hover:text-current" aria-label="Notifikasi, {{ $belumDibaca }} belum dibaca">
                <i class="bi bi-bell text-xl" aria-hidden="true"></i>
                @if ($belumDibaca)
                    <span class="absolute top-0.5 right-0.5 grid h-4.5 min-w-4.5 place-items-center rounded-full bg-rose-500 px-1 dark:bg-rose-600 text-xs leading-none font-bold text-white">{{ $belumDibaca > 99 ? '99+' : $belumDibaca }}</span>
                @endif
            </button>
        </x-slot:trigger>

        <div class="flex min-h-10 items-center justify-between gap-3 border-b border-slate-100 px-3 pb-1.5 dark:border-navy-700">
            <p class="text-base font-semibold text-navy-900 dark:text-white">Notifikasi</p>
            @if ($belumDibaca)
                <button type="button" wire:click="tandaiSemua" class="-mr-1 rounded-lg px-2 py-1.5 text-sm font-semibold text-primary-600 hover:bg-primary-50 dark:text-primary-300 dark:hover:bg-primary-500/10">Tandai semua dibaca</button>
            @endif
        </div>

        <div class="max-h-[26rem] space-y-0.5 overflow-y-auto py-1 max-sm:max-h-[60vh]">
            @forelse ($daftar as $n)
                <x-naskah.notifikasi :notifikasi="$n" wire:key="notif-{{ $n->id }}" />
            @empty
                <x-ui.empty-state icon="bell" title="Belum ada notifikasi." class="py-8" />
            @endforelse
        </div>

        <div class="border-t border-slate-100 pt-1.5 dark:border-navy-700">
            <a href="{{ route('v2.notifikasi') }}" class="flex min-h-10 items-center justify-center gap-1.5 rounded-lg text-sm font-semibold text-primary-600 transition hover:bg-primary-50 dark:text-primary-300 dark:hover:bg-primary-500/10">
                Lihat semua notifikasi <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </x-ui.dropdown>
</div>
