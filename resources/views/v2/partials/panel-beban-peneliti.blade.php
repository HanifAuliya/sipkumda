{{--
    Halaman Pilih Peneliti: rancangan yang sedang dipegang tiap peneliti, sebagai bahan menugaskan.
    Variabel: $bebanPeneliti (BebanPeneliti::daftar()).
--}}
<x-ui.card title="Beban Peneliti" description="Rancangan yang sedang dipegang tiap peneliti. Klik rancangan untuk melihat detailnya." class="xl:sticky xl:top-20">
    @if ($bebanPeneliti->isEmpty())
        <p class="text-sm text-slate-500 dark:text-slate-400">Belum ada akun Peneliti.</p>
    @else
        <div class="-my-2 divide-y divide-slate-100 dark:divide-navy-800">
            @foreach ($bebanPeneliti as $orang)
                <div x-data="{ buka: false }" class="py-2" wire:key="beban-peneliti-{{ $orang->id }}">
                    <button type="button" x-on:click="buka = ! buka" x-bind:aria-expanded="buka"
                        class="flex w-full items-center gap-3 rounded-lg py-1.5 text-left">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary-50 text-sm font-bold text-primary-600 dark:bg-primary-500/10 dark:text-primary-300">
                            {{ \Illuminate\Support\Str::of($orang->nama_user)->before(',')->explode(' ')->filter()->take(2)->map(fn ($k) => mb_substr($k, 0, 1))->implode('') }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-navy-900 dark:text-white">{{ $orang->nama_user }}</span>
                            <span class="block text-sm text-slate-500 dark:text-slate-400">
                                {{ $orang->beban_aktif }} berjalan
                                @if ($orang->beban_menunggu)
                                    · <span class="font-medium text-orange-600 dark:text-orange-300">{{ $orang->beban_menunggu }} menunggu dikerjakan</span>
                                @endif
                            </span>
                        </span>
                        @if ($orang->beban_aktif)
                            <i class="bi bi-chevron-down text-slate-400 transition-transform" x-bind:class="buka && 'rotate-180'" aria-hidden="true"></i>
                        @endif
                    </button>
                    @if ($orang->beban_aktif)
                        <div x-show="buka" x-collapse x-cloak class="mt-1 pl-12">
                            @include('v2.partials.naskah-peneliti', ['daftar' => $orang->naskahAktif, 'bisaDibuka' => true])
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-ui.card>
