<div>
    <x-ui.list-toolbar placeholder="Cari nomor rancangan atau tentang..." :options="[3, 5, 10, 50]" />

    <div class="space-y-4">
        @forelse ($rancanganMenunggu as $item)
            <x-rancangan.item :rancangan="$item" wire:key="menunggu-{{ $item->id_rancangan }}">
                <x-slot:meta>
                    <x-rancangan.meta icon="person">Pemohon: {{ $item->user->nama_user ?? '-' }}</x-rancangan.meta>
                </x-slot:meta>

                <x-rancangan.ai-status :rancangan="$item" can-check />

                <x-slot:aside>
                    <x-ui.badge color="warning" size="lg" icon="hourglass-split">Berkas {{ $item->status_berkas }}</x-ui.badge>
                    <p class="text-xs text-slate-500">Pengajuan tahun {{ \Illuminate\Support\Carbon::parse($item->tanggal_pengajuan)->year }}</p>
                    <x-ui.button variant="navy" icon="check2-square" wire:click="openModal({{ $item->id_rancangan }})"
                        loading="openModal({{ $item->id_rancangan }})">Verifikasi Berkas</x-ui.button>
                </x-slot:aside>
            </x-rancangan.item>
        @empty
            <x-ui.empty-state icon="inbox" title="Tidak ada berkas yang menunggu persetujuan." class="py-12" />
        @endforelse
    </div>

    <div class="mt-5">{{ $rancanganMenunggu->links() }}</div>

    {{-- Verifikasi berkas --}}
    <x-ui.modal id="modalPersetujuan" title="Verifikasi Berkas Rancangan" :description="$selectedRancangan?->no_rancangan"
        size="6xl" static on-close="resetForm">
        @if ($selectedRancangan)
            <div class="grid gap-5 lg:grid-cols-2">
                <section class="rounded-xl ring-1 ring-slate-100 dark:ring-navy-800">
                    <h4 class="border-b border-slate-100 px-4 py-3 text-sm font-semibold dark:border-navy-800">Informasi Utama</h4>
                    <x-rancangan.info :rancangan="$selectedRancangan" class="px-4" />
                </section>

                <form id="persetujuanForm" wire:submit="updateStatus" class="space-y-5 rounded-xl p-4 ring-1 ring-slate-100 dark:ring-navy-800">
                    <x-ui.alert type="warning" icon="exclamation-triangle">
                        Berkas <strong>{{ $selectedRancangan->status_berkas }}</strong>. Periksa berkas lalu tentukan persetujuan.
                    </x-ui.alert>

                    <x-form.field label="Keputusan" error="statusBerkas" required>
                        <x-form.decision model="statusBerkas" :value="$statusBerkas" approve-label="Setujui berkas" reject-label="Tolak berkas" />
                    </x-form.field>

                    <x-form.field label="Catatan" for="catatanPersetujuan" error="catatan" required
                        hint="Berikan masukan atau alasan penolakan untuk perangkat daerah.">
                        <x-form.textarea id="catatanPersetujuan" wire:model="catatan" rows="10" placeholder="Tambahkan catatan..." />
                    </x-form.field>
                </form>
            </div>
        @else
            <x-rancangan.detail :rancangan="null" />
        @endif

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="close()">Batal</x-ui.button>
            <x-ui.button type="submit" form="persetujuanForm" variant="navy" icon="check-circle" loading="updateStatus">Simpan Verifikasi</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
