<div>
    <x-form.input wire:model.live.debounce.400ms="search" icon="search" class="mb-5" placeholder="Cari nama peneliti..." aria-label="Cari peneliti" />

    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($peneliti as $user)
            <article wire:key="peneliti-{{ $user->id }}" class="rounded-xl border border-slate-200 p-4 dark:border-navy-800">
                <header class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-full bg-primary-50 font-bold text-primary-600 dark:bg-primary-500/15 dark:text-primary-300">
                            {{ \Illuminate\Support\Str::of($user->nama_user)->explode(' ')->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->join('') }}
                        </span>
                        <h4 class="font-semibold">{{ $user->nama_user }}</h4>
                    </div>
                    <x-ui.badge :color="$user->revisi->count() ? 'warning' : 'success'">{{ $user->revisi->count() }} penelitian aktif</x-ui.badge>
                </header>

                <ul class="mt-3 space-y-2">
                    @forelse ($user->revisi as $revisi)
                        <li class="rounded-lg bg-slate-50 px-3 py-2 text-sm dark:bg-navy-950/40">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-semibold text-navy-900 dark:text-white">{{ $revisi->rancangan->no_rancangan ?? '-' }}</span>
                                <x-ui.status-badge :value="$revisi->rancangan?->jenis_rancangan" />
                            </div>
                            <p class="mt-0.5 text-slate-500 dark:text-slate-400">{{ $revisi->rancangan->tentang ?? '-' }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-slate-400 italic">Tidak ada penelitian berlangsung.</li>
                    @endforelse
                </ul>
            </article>
        @empty
            <x-ui.empty-state icon="people" title="Tidak ada peneliti ditemukan." class="py-12 md:col-span-2" />
        @endforelse
    </div>

    <div class="mt-5">{{ $peneliti->links() }}</div>
</div>
