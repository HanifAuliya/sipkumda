<div>
    <x-ui.list-toolbar placeholder="Cari nomor rancangan atau tentang..." :options="[5, 10, 20]" />

    <div class="space-y-4">
        @forelse ($riwayatValidasi as $item)
            @php $revisi = $item->revisi->last(); @endphp

            <x-rancangan.item :rancangan="$item" wire:key="riwayat-validasi-{{ $item->id_rancangan }}">
                <x-slot:meta>
                    <x-rancangan.meta icon="person-gear">Peneliti: {{ $revisi?->peneliti?->nama_user ?? '-' }}</x-rancangan.meta>
                    <x-rancangan.meta icon="calendar-check">Divalidasi {{ tanggal($revisi?->tanggal_validasi, true) }}</x-rancangan.meta>
                </x-slot:meta>

                @if ($revisi?->catatan_validasi)
                    <p class="rounded-lg bg-slate-50 px-3.5 py-2.5 text-sm text-slate-600 ring-1 ring-slate-200 dark:bg-navy-950/40 dark:text-slate-300 dark:ring-navy-700">
                        <span class="font-semibold">Catatan validasi:</span> {{ $revisi->catatan_validasi }}
                    </p>
                @endif

                <x-slot:aside>
                    <x-ui.status-badge :value="$revisi?->status_validasi" size="lg" />
                    <p class="text-xs text-slate-500">Pengajuan tahun {{ \Illuminate\Support\Carbon::parse($item->tanggal_pengajuan)->year }}</p>

                    <x-ui.dropdown>
                        <x-slot:trigger>
                            <x-ui.button variant="outline" icon="gear">Kelola <i class="bi bi-chevron-down text-xs"></i></x-ui.button>
                        </x-slot:trigger>
                        <x-ui.dropdown-item icon="eye"
                            x-on:click="Livewire.dispatch('openModalDetailRancangan', { slug: {{ \Illuminate\Support\Js::from($item->slug) }} })">
                            Lihat Detail
                        </x-ui.dropdown-item>
                        @if ($revisi)
                            <x-ui.dropdown-item icon="arrow-counterclockwise" danger
                                x-on:click="$confirm({ title: 'Reset validasi?', text: 'Validasi rancangan ini akan direset dan peneliti diminta mengunggah ulang.', confirmText: 'Ya, reset' })
                                    .then(ok => ok && $wire.resetValidasi({{ $revisi->id_revisi }}))">
                                Reset Validasi
                            </x-ui.dropdown-item>
                        @endif
                    </x-ui.dropdown>
                </x-slot:aside>
            </x-rancangan.item>
        @empty
            <x-ui.empty-state icon="clock-history" title="Belum ada riwayat validasi." class="py-12" />
        @endforelse
    </div>

    <div class="mt-5">{{ $riwayatValidasi->links() }}</div>
</div>
