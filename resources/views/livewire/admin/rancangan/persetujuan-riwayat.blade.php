<div>
    <x-ui.list-toolbar placeholder="Cari nomor rancangan atau tentang..." :options="[3, 5, 10, 50]" />

    <div class="space-y-4">
        @forelse ($riwayatRancangan as $item)
            <x-rancangan.item :rancangan="$item" wire:key="riwayat-{{ $item->id_rancangan }}">
                <x-slot:meta>
                    <x-rancangan.meta icon="person">Pemohon: {{ $item->user->nama_user ?? '-' }}</x-rancangan.meta>
                    <x-rancangan.meta icon="calendar-check">Disetujui {{ tanggal($item->tanggal_berkas_disetujui, true, 'belum disetujui') }}</x-rancangan.meta>
                </x-slot:meta>

                <x-slot:aside>
                    <x-ui.badge color="success" size="lg" icon="check-circle">Berkas {{ $item->status_berkas }}</x-ui.badge>
                    <p class="text-xs text-slate-500">Pengajuan tahun {{ \Illuminate\Support\Carbon::parse($item->tanggal_pengajuan)->year }}</p>

                    <x-ui.dropdown>
                        <x-slot:trigger>
                            <x-ui.button variant="outline" icon="gear">Tindakan <i class="bi bi-chevron-down text-xs"></i></x-ui.button>
                        </x-slot:trigger>
                        <x-ui.dropdown-item icon="eye"
                            x-on:click="Livewire.dispatch('openModalDetailRancangan', { slug: {{ \Illuminate\Support\Js::from($item->slug) }} })">
                            Lihat Detail
                        </x-ui.dropdown-item>
                        <x-ui.dropdown-item icon="arrow-counterclockwise" danger
                            x-on:click="$confirm({ title: 'Reset status berkas?', text: 'Status akan dikembalikan ke “Menunggu Persetujuan” dan revisi direset.', confirmText: 'Ya, reset' })
                                .then(async ok => { if (! ok) return; await $wire.setSelectedRancangan({{ $item->id_rancangan }}); await $wire.confirmResetStatus(); })">
                            Reset Status
                        </x-ui.dropdown-item>
                    </x-ui.dropdown>
                </x-slot:aside>
            </x-rancangan.item>
        @empty
            <x-ui.empty-state icon="check2-circle" title="Belum ada berkas yang disetujui." class="py-12" />
        @endforelse
    </div>

    <div class="mt-5">{{ $riwayatRancangan->links() }}</div>
</div>
