<div>
    <x-ui.list-toolbar placeholder="Cari nomor rancangan atau tentang..." :options="[5, 10, 20, 50]" />

    <div class="space-y-4">
        @forelse ($fasilitasiMenunggu as $fasilitasi)
            <x-rancangan.item :rancangan="$fasilitasi->rancangan" wire:key="persetujuan-fasilitasi-{{ $fasilitasi->id }}">
                <x-slot:meta>
                    <x-rancangan.meta icon="person">Pemohon: {{ $fasilitasi->rancangan->user->nama_user ?? '-' }}</x-rancangan.meta>
                    <x-rancangan.meta icon="send-check">Fasilitasi diajukan {{ tanggal($fasilitasi->tanggal_fasilitasi, true) }}</x-rancangan.meta>
                </x-slot:meta>

                <x-ui.file-link folder="fasilitasi/rancangan" :path="$fasilitasi->file_rancangan" label="Lihat berkas fasilitasi" />

                <x-slot:aside>
                    <x-ui.status-badge :value="$fasilitasi->status_berkas_fasilitasi" size="lg" />
                    <x-ui.button variant="navy" icon="check2-square" wire:click="openDetailFasilitasi({{ $fasilitasi->id }})"
                        loading="openDetailFasilitasi({{ $fasilitasi->id }})">Periksa Berkas</x-ui.button>
                </x-slot:aside>
            </x-rancangan.item>
        @empty
            <x-ui.empty-state icon="file-check" title="Tidak ada fasilitasi yang menunggu persetujuan." class="py-12" />
        @endforelse
    </div>

    <div class="mt-5">{{ $fasilitasiMenunggu->links() }}</div>

    {{-- Persetujuan berkas fasilitasi --}}
    <x-ui.modal id="modalPersetujuanFasilitasi" title="Persetujuan Fasilitasi" :description="$selectedFasilitasi?->rancangan?->no_rancangan"
        size="5xl" static on-close="resetForm">
        @if ($selectedFasilitasi)
            <div class="grid gap-5 lg:grid-cols-5">
                <section class="rounded-xl px-4 ring-1 ring-slate-100 lg:col-span-3 dark:ring-navy-800">
                    <x-fasilitasi.info :fasilitasi="$selectedFasilitasi">
                        <x-ui.info-item label="Matrik Hasil Penelitian">
                            <x-ui.file-link folder="revisi/matrik" :path="$selectedFasilitasi->rancangan?->revisi?->last()?->revisi_matrik" label="Lihat Matrik Revisi" />
                        </x-ui.info-item>
                    </x-fasilitasi.info>
                </section>

                <form id="persetujuanFasilitasiForm" wire:submit="updateStatus" class="space-y-5 lg:col-span-2">
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Bandingkan berkas fasilitasi dengan hasil penelitian, lalu tentukan persetujuan.
                    </p>
                    <x-form.field label="Keputusan" error="statusBerkas" required>
                        <x-form.decision model="statusBerkas" :value="$statusBerkas" approve-label="Setujui" reject-label="Tolak" />
                    </x-form.field>
                    <x-form.field label="Catatan" for="catatanFasilitasi" error="catatan" required hint="Minimal 5 karakter.">
                        <x-form.textarea id="catatanFasilitasi" wire:model="catatan" rows="8" placeholder="Catatan koreksi untuk perangkat daerah..." />
                    </x-form.field>
                </form>
            </div>
        @else
            <x-rancangan.detail :rancangan="null" />
        @endif

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="close()">Batal</x-ui.button>
            <x-ui.button type="submit" form="persetujuanFasilitasiForm" variant="navy" icon="check-circle" loading="updateStatus">Simpan</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
