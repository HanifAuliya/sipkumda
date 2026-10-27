<div>
    <x-ui.list-toolbar placeholder="Cari nomor rancangan atau tentang..." :options="[5, 10, 20, 50]" />

    <div class="space-y-4">
        @forelse ($riwayatFasilitasi as $fasilitasi)
            <x-rancangan.item :rancangan="$fasilitasi->rancangan" wire:key="riwayat-fasilitasi-{{ $fasilitasi->id }}">
                <x-slot:meta>
                    <x-rancangan.meta icon="patch-check">Divalidasi {{ tanggal($fasilitasi->tanggal_validasi_fasilitasi) }}</x-rancangan.meta>
                    <x-rancangan.meta icon="file-text">Nota {{ $fasilitasi->notaDinas->nomor_nota ?? '-' }}</x-rancangan.meta>
                </x-slot:meta>

                <x-fasilitasi.tahapan :fasilitasi="$fasilitasi" />

                <x-slot:aside>
                    <x-ui.badge color="success" size="lg" icon="check2-all">Fasilitasi Selesai</x-ui.badge>
                    <div class="flex flex-wrap gap-2 lg:justify-end">
                        <x-ui.button variant="outline" icon="info-circle" x-on:click="Livewire.dispatch('openDetailFasilitasi', { id: {{ $fasilitasi->id }} })">Detail</x-ui.button>
                        @if ($fasilitasi->notaDinas)
                            <x-ui.button variant="outline-danger" icon="file-earmark-pdf"
                                x-on:click="showLoading('Membuat PDF nota dinas...'); $wire.generatePDF({{ $fasilitasi->notaDinas->id }})">Nota Dinas</x-ui.button>
                        @endif
                    </div>
                </x-slot:aside>
            </x-rancangan.item>
        @empty
            <x-ui.empty-state icon="bookmark-star" title="Belum ada fasilitasi yang selesai." class="py-12" />
        @endforelse
    </div>

    <div class="mt-5">{{ $riwayatFasilitasi->links() }}</div>
</div>
