<div>
    <x-ui.list-toolbar placeholder="Cari nomor rancangan atau tentang..." :options="[3, 5, 10, 50]" />

    <div class="space-y-4">
        @forelse ($revisi as $item)
            @php $revisiTerakhir = $item->revisi->last(); @endphp

            <x-rancangan.item :rancangan="$item" wire:key="menunggu-revisi-{{ $item->id_rancangan }}">
                <x-slot:meta>
                    <x-rancangan.meta icon="calendar-event">Ditugaskan {{ tanggal($revisiTerakhir?->tanggal_peneliti_ditunjuk, true) }}</x-rancangan.meta>
                </x-slot:meta>

                <x-ui.alert type="primary" icon="pencil-square">
                    Teliti rancangan ini lalu unggah <strong>matrik hasil penelitian</strong> beserta catatan untuk perangkat daerah.
                </x-ui.alert>

                <x-slot:aside>
                    <x-ui.status-badge :value="$revisiTerakhir?->status_revisi" size="lg" />
                    <p class="text-xs text-slate-500">Pengajuan tahun {{ \Illuminate\Support\Carbon::parse($item->tanggal_pengajuan)->year }}</p>
                    <div class="flex flex-wrap gap-2 lg:justify-end">
                        <x-rancangan.detail-button :rancangan="$item" label="Detail" variant="outline" size="md" />
                        @if ($revisiTerakhir)
                            <x-ui.button variant="navy" icon="upload" wire:click="selectRevisi({{ $revisiTerakhir->id_revisi }})"
                                loading="selectRevisi({{ $revisiTerakhir->id_revisi }})">Unggah Revisi</x-ui.button>
                        @endif
                    </div>
                </x-slot:aside>
            </x-rancangan.item>
        @empty
            <x-ui.empty-state icon="journal-check" title="Tidak ada rancangan yang menunggu penelitian." class="py-12" />
        @endforelse
    </div>

    <div class="mt-5">{{ $revisi->links() }}</div>

    {{-- Unggah hasil penelitian --}}
    <x-ui.modal id="uploadRevisiModal" title="Unggah Hasil Penelitian" description="Matrik revisi akan dikirim untuk divalidasi verifikator."
        size="2xl" static on-close="resetForm">
        <form id="uploadRevisiForm" wire:submit="uploadRevisi" class="space-y-5">
            <x-form.field label="File Revisi Matrik" for="revisiMatrik" error="revisiMatrik" required hint="Word (.doc/.docx), maks. 20MB">
                <x-form.file id="revisiMatrik" wire:model="revisiMatrik" accept=".doc,.docx" />
            </x-form.field>

            <x-form.field label="Catatan Revisi" for="catatanRevisi" error="catatanRevisi" required
                hint="Catatan untuk perangkat daerah terkait perbaikan dokumen.">
                <x-form.textarea id="catatanRevisi" wire:model="catatanRevisi" rows="6" placeholder="Tambahkan catatan revisi..." />
            </x-form.field>
        </form>

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="close()">Batal</x-ui.button>
            <x-ui.button type="submit" form="uploadRevisiForm" variant="navy" icon="file-earmark-arrow-up" loading="uploadRevisi, revisiMatrik">Unggah Revisi</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
