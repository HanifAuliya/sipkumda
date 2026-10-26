<div>
    <x-ui.list-toolbar placeholder="Cari nomor rancangan atau tentang..." :options="[5, 10, 20, 50]" />

    <div class="space-y-4">
        @forelse ($fasilitasiBerlangsung as $fasilitasi)
            @php $info = $fasilitasi->statusInfo(); @endphp

            <x-rancangan.item :rancangan="$fasilitasi->rancangan" wire:key="fasilitasi-{{ $fasilitasi->id }}">
                <x-slot:meta>
                    <x-rancangan.meta icon="send-check">Fasilitasi diajukan {{ tanggal($fasilitasi->tanggal_fasilitasi, true) }}</x-rancangan.meta>
                </x-slot:meta>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-500">
                    <span class="flex items-center gap-1.5">Persetujuan <x-ui.status-badge :value="$fasilitasi->status_berkas_fasilitasi" /></span>
                    <span class="flex items-center gap-1.5">Validasi <x-ui.status-badge :value="$fasilitasi->status_validasi_fasilitasi" /></span>
                </div>

                @if ($info)
                    <x-ui.alert :type="$info['type']" :title="$info['title']">{{ $info['message'] }}</x-ui.alert>
                @endif

                <x-slot:aside>
                    <x-ui.status-badge :value="$fasilitasi->status_berkas_fasilitasi" size="lg" />
                    <x-ui.dropdown>
                        <x-slot:trigger>
                            <x-ui.button variant="outline" icon="gear">Kelola <i class="bi bi-chevron-down text-xs"></i></x-ui.button>
                        </x-slot:trigger>
                        <x-ui.dropdown-item icon="info-circle" x-on:click="Livewire.dispatch('openDetailFasilitasi', { id: {{ $fasilitasi->id }} })">
                            Detail Fasilitasi
                        </x-ui.dropdown-item>
                        @if ($fasilitasi->status_berkas_fasilitasi === 'Ditolak')
                            <x-ui.dropdown-item icon="upload" wire:click="openUploadUlangRevisi({{ $fasilitasi->id }})">
                                Upload Ulang Fasilitasi
                            </x-ui.dropdown-item>
                        @endif
                        @if ($fasilitasi->status_validasi_fasilitasi === 'Diterima' && $fasilitasi->notaDinas)
                            <x-ui.dropdown-item icon="file-earmark-pdf"
                                x-on:click="showLoading('Membuat PDF nota dinas...'); $wire.generatePDF({{ $fasilitasi->notaDinas->id }})">
                                Cetak Nota Dinas
                            </x-ui.dropdown-item>
                        @endif
                    </x-ui.dropdown>
                </x-slot:aside>
            </x-rancangan.item>
        @empty
            <x-ui.empty-state icon="bookmark-dash" title="Tidak ada fasilitasi yang sedang berlangsung."
                description="Ajukan fasilitasi untuk rancangan yang telah disetujui." class="py-12" />
        @endforelse
    </div>

    <div class="mt-5">{{ $fasilitasiBerlangsung->links() }}</div>

    {{-- Upload ulang berkas fasilitasi yang ditolak --}}
    <x-ui.modal id="modalUploadUlangFasilitasi" title="Upload Ulang Fasilitasi" description="Unggah berkas fasilitasi yang telah diperbaiki."
        size="xl" static>
        <form id="uploadUlangFasilitasiForm" wire:submit="uploadUlangRevisi">
            <x-form.upload model="fileRancanganUlang" :file="$fileRancanganUlang" label="File Rancangan" accept=".pdf" required
                hint="PDF, maks. 20MB." wire:change="resetError('fileRancanganUlang')" />
        </form>

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="close()">Batal</x-ui.button>
            <x-ui.button type="submit" form="uploadUlangFasilitasiForm" variant="navy" icon="upload"
                loading="uploadUlangRevisi, fileRancanganUlang" :disabled="! $fileRancanganUlang">Upload Ulang</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
