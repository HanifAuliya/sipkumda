<div>
    <x-ui.list-toolbar placeholder="Cari nomor rancangan atau tentang..." :options="[3, 5, 10, 50]" />

    <div class="space-y-4">
        @forelse ($revisi as $item)
            @php $revisiItem = $item->revisi->first(); @endphp

            <x-rancangan.item :rancangan="$item" wire:key="riwayat-revisi-{{ $item->id_rancangan }}">
                <x-slot:meta>
                    <x-rancangan.meta icon="calendar2-check">Revisi dikirim {{ tanggal($revisiItem?->tanggal_revisi, true) }}</x-rancangan.meta>
                </x-slot:meta>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-500">
                    <span class="flex items-center gap-1.5">Status revisi <x-ui.status-badge :value="$revisiItem?->status_revisi" /></span>
                    <span class="flex items-center gap-1.5">Validasi <x-ui.status-badge :value="$revisiItem?->status_validasi" /></span>
                </div>

                @if ($revisiItem?->status_validasi === 'Ditolak' && $revisiItem->catatan_validasi)
                    <x-ui.alert type="danger" icon="arrow-repeat">
                        <strong>Perlu revisi ulang.</strong> {{ $revisiItem->catatan_validasi }}
                    </x-ui.alert>
                @endif

                <x-slot:aside>
                    <x-ui.status-badge :value="$revisiItem?->status_validasi" size="lg" />
                    <p class="text-xs text-slate-500">Pengajuan tahun {{ \Illuminate\Support\Carbon::parse($item->tanggal_pengajuan)->year }}</p>

                    <x-ui.dropdown>
                        <x-slot:trigger>
                            <x-ui.button variant="outline" icon="gear">Kelola <i class="bi bi-chevron-down text-xs"></i></x-ui.button>
                        </x-slot:trigger>
                        <x-ui.dropdown-item icon="eye"
                            x-on:click="Livewire.dispatch('openModalDetailRancangan', { slug: {{ \Illuminate\Support\Js::from($item->slug) }} })">
                            Lihat Detail
                        </x-ui.dropdown-item>
                        <x-ui.dropdown-item icon="arrow-repeat" wire:click="openUploadRevisi({{ $item->id_rancangan }})">
                            Upload Ulang Revisi
                        </x-ui.dropdown-item>
                        @if ($revisiItem)
                            <x-ui.dropdown-item icon="trash" danger
                                x-on:click="$confirm({ title: 'Reset revisi?', text: 'File revisi dan catatan akan dihapus dan tidak dapat dikembalikan.', confirmText: 'Ya, reset' })
                                    .then(ok => ok && $wire.resetRevisi({{ $revisiItem->id_revisi }}))">
                                Reset Revisi
                            </x-ui.dropdown-item>
                        @endif
                    </x-ui.dropdown>
                </x-slot:aside>
            </x-rancangan.item>
        @empty
            <x-ui.empty-state icon="clock-history" title="Belum ada riwayat revisi." class="py-12" />
        @endforelse
    </div>

    <div class="mt-5">{{ $revisi->links() }}</div>

    {{-- Unggah ulang hasil penelitian --}}
    <x-ui.modal id="uploadRevisiModal" title="Upload Ulang Revisi" description="Matrik revisi akan dikirim ulang untuk divalidasi."
        size="2xl" static on-close="resetForm">
        <form id="uploadUlangRevisiForm" wire:submit="uploadUlangRevisi" class="space-y-5">
            <x-form.field label="File Revisi Matrik" for="revisiMatrikUlang" error="revisiMatrik" required hint="Word (.doc/.docx), maks. 20MB">
                <x-form.file id="revisiMatrikUlang" wire:model="revisiMatrik" accept=".doc,.docx" />
            </x-form.field>

            <x-form.field label="Catatan Revisi" for="catatanRevisiUlang" error="catatanRevisi" hint="Opsional.">
                <x-form.textarea id="catatanRevisiUlang" wire:model="catatanRevisi" rows="6" placeholder="Tambahkan catatan revisi jika diperlukan..." />
            </x-form.field>
        </form>

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="close()">Batal</x-ui.button>
            <x-ui.button type="submit" form="uploadUlangRevisiForm" variant="navy" icon="arrow-repeat" loading="uploadUlangRevisi, revisiMatrik">Upload Ulang</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
