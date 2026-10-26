<div>
    <x-ui.list-toolbar placeholder="Cari nomor rancangan atau tentang..." :options="[3, 5, 10, 50]" />

    <div class="space-y-4">
        @forelse ($rancanganMenunggu as $item)
            @php $revisi = $item->revisi->last(); @endphp

            <x-rancangan.item :rancangan="$item" wire:key="validasi-{{ $item->id_rancangan }}">
                <x-slot:meta>
                    <x-rancangan.meta icon="person">Pemohon: {{ $item->user->nama_user ?? '-' }}</x-rancangan.meta>
                    <x-rancangan.meta icon="calendar2-check">Revisi dikirim {{ tanggal($revisi?->tanggal_revisi, true) }}</x-rancangan.meta>
                </x-slot:meta>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-500">
                    <span class="flex items-center gap-1.5">Persetujuan berkas <x-ui.status-badge :value="$item->status_berkas" /></span>
                    <span class="flex items-center gap-1.5">Status revisi <x-ui.status-badge :value="$revisi?->status_revisi" /></span>
                    <span class="flex items-center gap-1.5">Validasi <x-ui.status-badge :value="$revisi?->status_validasi" /></span>
                </div>

                <x-slot:aside>
                    <x-ui.badge color="primary" size="lg" icon="person-check">{{ $revisi?->peneliti?->nama_user ?? 'Tanpa peneliti' }}</x-ui.badge>
                    <p class="text-xs text-slate-500">Pengajuan tahun {{ \Illuminate\Support\Carbon::parse($item->tanggal_pengajuan)->year }}</p>
                    <x-ui.button variant="navy" icon="patch-check" wire:click="openModalValidasiRancangan({{ $item->id_rancangan }})"
                        loading="openModalValidasiRancangan({{ $item->id_rancangan }})">Validasi Revisi</x-ui.button>
                </x-slot:aside>
            </x-rancangan.item>
        @empty
            <x-ui.empty-state icon="patch-check" title="Tidak ada revisi yang menunggu validasi." class="py-12" />
        @endforelse
    </div>

    <div class="mt-5">{{ $rancanganMenunggu->links() }}</div>

    {{-- Validasi hasil penelitian --}}
    <x-ui.modal id="modalValidasiRancangan" title="Validasi Hasil Penelitian" :description="$selectedRancangan?->no_rancangan"
        size="6xl" static on-close="resetDetail">
        <x-rancangan.detail :rancangan="$selectedRancangan" />

        @if ($selectedRancangan)
            <form id="validasiForm" wire:submit="validasiRevisi" class="mt-5 grid gap-5 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200 md:grid-cols-3 dark:bg-navy-950/40 dark:ring-navy-700">
                <x-form.field label="Status Validasi" for="statusValidasi" error="statusValidasi" required>
                    <x-form.select id="statusValidasi" wire:model="statusValidasi" icon="patch-question">
                        <option value="" hidden>Pilih status...</option>
                        <option value="Diterima">Diterima</option>
                        <option value="Ditolak">Ditolak (revisi ulang)</option>
                    </x-form.select>
                </x-form.field>

                <x-form.field label="Catatan Validasi" for="catatanValidasi" error="catatanValidasi" required class="md:col-span-2">
                    <x-form.textarea id="catatanValidasi" wire:model="catatanValidasi" rows="3" placeholder="Tambahkan catatan terkait validasi ini..." />
                </x-form.field>
            </form>
        @endif

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="close()">Batal</x-ui.button>
            <x-ui.button type="submit" form="validasiForm" variant="navy" icon="send-check" loading="validasiRevisi">Simpan Validasi</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
