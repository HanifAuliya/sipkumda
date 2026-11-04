<div wire:poll.60s>
    <x-ui.dropdown width="w-[22rem] max-w-[calc(100vw-2rem)]">
        <x-slot:trigger>
            <button type="button" class="relative rounded-lg p-2 text-white/70 transition hover:bg-white/10 hover:text-white" aria-label="Notifikasi, {{ $belumDibaca }} belum dibaca">
                <i class="bi bi-bell text-lg leading-none"></i>
                @if ($belumDibaca)
                    <span class="absolute top-0.5 right-0.5 grid min-w-4 place-items-center rounded-full bg-rose-500 px-1 text-[0.65rem] font-bold text-white">{{ $belumDibaca > 99 ? '99+' : $belumDibaca }}</span>
                @endif
            </button>
        </x-slot:trigger>

        <div class="flex items-center justify-between border-b border-slate-100 px-3 pt-1 pb-2 dark:border-navy-700">
            <p class="text-sm font-semibold text-navy-900 dark:text-white">Notifikasi</p>
            @if ($belumDibaca)
                <button type="button" wire:click="tandaiSemua" class="text-xs font-semibold text-primary-600 hover:underline dark:text-primary-300">Tandai semua dibaca</button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto pt-1">
            @forelse ($daftar as $n)
                <button type="button" wire:click="buka('{{ $n->id }}')" wire:key="notif-{{ $n->id }}"
                    @class(['block w-full rounded-lg px-3 py-2 text-left text-sm transition hover:bg-slate-100 dark:hover:bg-navy-700', 'bg-primary-50/70 dark:bg-primary-500/10' => ! $n->read_at])>
                    <span class="block font-semibold text-navy-900 dark:text-slate-100">{{ $n->data['title'] ?? 'Notifikasi' }}</span>
                    <span class="mt-0.5 line-clamp-2 block text-xs text-slate-500 dark:text-slate-400">{{ $n->data['message'] ?? '' }}</span>
                    <span class="mt-0.5 block text-[0.7rem] text-slate-400">{{ $n->created_at->diffForHumans() }}</span>
                </button>
            @empty
                <p class="px-3 py-6 text-center text-sm text-slate-500">Belum ada notifikasi.</p>
            @endforelse
        </div>
    </x-ui.dropdown>
</div>
