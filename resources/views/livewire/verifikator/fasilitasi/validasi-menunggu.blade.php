<div>
    <x-ui.list-toolbar placeholder="Cari nomor rancangan atau tentang..." :options="[3, 5, 10, 50]" />

    <div class="space-y-4">
        @forelse ($fasilitasiMenunggu as $fasilitasi)
            <x-rancangan.item :rancangan="$fasilitasi->rancangan" wire:key="validasi-fasilitasi-{{ $fasilitasi->id }}">
                <x-slot:meta>
                    <x-rancangan.meta icon="person">Pemohon: {{ $fasilitasi->rancangan->user->nama_user ?? '-' }}</x-rancangan.meta>
                    <x-rancangan.meta icon="check2-square">Berkas diperiksa {{ tanggal($fasilitasi->tanggal_persetujuan_berkas, true) }}</x-rancangan.meta>
                </x-slot:meta>

                <x-ui.file-link folder="fasilitasi/rancangan" :path="$fasilitasi->file_rancangan" label="Lihat berkas fasilitasi" />

                <x-slot:aside>
                    <x-ui.status-badge :value="$fasilitasi->status_validasi_fasilitasi" size="lg" />
                    <p class="text-xs text-slate-500">Berkas <x-ui.status-badge :value="$fasilitasi->status_berkas_fasilitasi" /></p>
                    <x-ui.button variant="navy" icon="patch-check" wire:click="openModalValidasiFasilitasi({{ $fasilitasi->id }})"
                        loading="openModalValidasiFasilitasi({{ $fasilitasi->id }})">Validasi</x-ui.button>
                </x-slot:aside>
            </x-rancangan.item>
        @empty
            <x-ui.empty-state icon="patch-check" title="Tidak ada fasilitasi yang menunggu validasi." class="py-12" />
        @endforelse
    </div>

    <div class="mt-5">{{ $fasilitasiMenunggu->links() }}</div>

    {{-- Validasi fasilitasi --}}
    <x-ui.modal id="modalValidasiFasilitasi" title="Validasi Fasilitasi" :description="$selectedFasilitasi?->rancangan?->no_rancangan"
        size="5xl" static on-close="resetData">
        @if ($selectedFasilitasi)
            <div class="grid gap-5 lg:grid-cols-5">
                <section class="rounded-xl px-4 ring-1 ring-slate-100 lg:col-span-3 dark:ring-navy-800">
                    <x-fasilitasi.info :fasilitasi="$selectedFasilitasi" />
                </section>

                <form id="validasiFasilitasiForm" wire:submit="validasiFasilitasi" class="space-y-5 lg:col-span-2">
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Periksa hasil persetujuan peneliti, lalu tentukan validasi fasilitasi.
                    </p>
                    <x-form.field label="Status Validasi" error="statusValidasi" required>
                        <x-form.decision model="statusValidasi" :value="$statusValidasi" approve="Diterima" reject="Ditolak"
                            approve-label="Terima" reject-label="Tolak" />
                    </x-form.field>
                    <x-form.field label="Catatan Validasi" for="catatanValidasi" error="catatanValidasi" required hint="Maksimal 1000 karakter.">
                        <x-form.textarea id="catatanValidasi" wire:model="catatanValidasi" rows="8" placeholder="Tambahkan catatan validasi..." />
                    </x-form.field>
                </form>
            </div>
        @else
            <x-rancangan.detail :rancangan="null" />
        @endif

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="close()">Batal</x-ui.button>
            <x-ui.button type="submit" form="validasiFasilitasiForm" variant="navy" icon="check-circle" loading="validasiFasilitasi">Simpan Validasi</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
