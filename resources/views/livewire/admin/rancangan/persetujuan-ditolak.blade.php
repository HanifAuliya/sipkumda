<div>
    <x-ui.list-toolbar placeholder="Cari nomor rancangan atau tentang..." :options="[3, 5, 10, 50]" />

    <div class="space-y-4">
        @forelse ($berkasRancanganDitolak as $item)
            <x-rancangan.item :rancangan="$item" wire:key="ditolak-{{ $item->id_rancangan }}">
                <x-slot:meta>
                    <x-rancangan.meta icon="person">Pemohon: {{ $item->user->nama_user ?? '-' }}</x-rancangan.meta>
                </x-slot:meta>

                @if ($item->catatan_berkas)
                    <p class="rounded-lg bg-rose-50 px-3.5 py-2.5 text-sm text-rose-800 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-500/30">
                        <span class="font-semibold">Catatan penolakan:</span> {{ $item->catatan_berkas }}
                    </p>
                @endif

                <x-slot:aside>
                    <x-ui.badge color="danger" size="lg" icon="x-circle">Berkas {{ $item->status_berkas }}</x-ui.badge>
                    <p class="text-xs text-slate-500">Pengajuan tahun {{ \Illuminate\Support\Carbon::parse($item->tanggal_pengajuan)->year }}</p>

                    <x-ui.dropdown>
                        <x-slot:trigger>
                            <x-ui.button variant="outline" icon="gear">Tindakan <i class="bi bi-chevron-down text-xs"></i></x-ui.button>
                        </x-slot:trigger>
                        <x-ui.dropdown-item icon="eye"
                            x-on:click="Livewire.dispatch('openModalDetailRancangan', { slug: {{ \Illuminate\Support\Js::from($item->slug) }} })">
                            Lihat Detail
                        </x-ui.dropdown-item>
                        <x-ui.dropdown-item icon="check2-circle"
                            x-on:click="$prompt({ title: 'Setujui berkas rancangan ini?', label: 'Catatan persetujuan', confirmText: 'Setujui' })
                                .then(catatan => catatan && $wire.setujuiBerkas({{ $item->id_rancangan }}, catatan))">
                            Setujui Berkas
                        </x-ui.dropdown-item>
                        <x-ui.dropdown-item icon="arrow-counterclockwise" danger
                            x-on:click="$confirm({ title: 'Reset status berkas?', text: 'Status akan dikembalikan ke “Menunggu Persetujuan”.', confirmText: 'Ya, reset' })
                                .then(async ok => { if (! ok) return; await $wire.setSelectedRancangan({{ $item->id_rancangan }}); await $wire.confirmResetStatus(); })">
                            Reset Status
                        </x-ui.dropdown-item>
                    </x-ui.dropdown>
                </x-slot:aside>
            </x-rancangan.item>
        @empty
            <x-ui.empty-state icon="x-circle" title="Tidak ada berkas rancangan yang ditolak." class="py-12" />
        @endforelse
    </div>

    <div class="mt-5">{{ $berkasRancanganDitolak->links() }}</div>
</div>
