<div>
    <x-ui.list-toolbar placeholder="Cari nomor rancangan atau tentang..." :options="[3, 5, 10, 50]" />

    <div class="space-y-4">
        @forelse ($riwayat as $item)
            @php $revisi = $item->revisi->first(); @endphp

            <x-rancangan.item :rancangan="$item" wire:key="riwayat-{{ $item->id_rancangan }}">
                <x-slot:meta>
                    <x-rancangan.meta icon="person">Pemohon: {{ $item->user->nama_user ?? '-' }}</x-rancangan.meta>
                    <x-rancangan.meta icon="person-gear">Peneliti: {{ $revisi?->peneliti?->nama_user ?? 'Belum ditentukan' }}</x-rancangan.meta>
                </x-slot:meta>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-500">
                    <span class="flex items-center gap-1.5">Persetujuan berkas <x-ui.status-badge :value="$item->status_berkas" /></span>
                    <span class="flex items-center gap-1.5">Status revisi <x-ui.status-badge :value="$revisi?->status_revisi" /></span>
                </div>

                <x-ui.alert type="success" icon="clipboard2-check" dismissible>
                    <strong>Rancangan diterima.</strong> Lanjutkan ke menu <em>Fasilitasi</em> dengan catatan dan file dari revisi yang dikirim.
                </x-ui.alert>

                <x-slot:aside>
                    <x-ui.badge color="success" size="lg" icon="check-circle">Rancangan {{ $item->status_rancangan }}</x-ui.badge>
                    <p class="text-xs text-slate-500">Pengajuan tahun {{ \Illuminate\Support\Carbon::parse($item->tanggal_pengajuan)->year }}</p>

                    <x-ui.dropdown>
                        <x-slot:trigger>
                            <x-ui.button variant="outline" icon="gear">Detail <i class="bi bi-chevron-down text-xs"></i></x-ui.button>
                        </x-slot:trigger>
                        <x-ui.dropdown-item icon="info-circle"
                            x-on:click="Livewire.dispatch('openModalDetailRancangan', { slug: {{ \Illuminate\Support\Js::from($item->slug) }} })">
                            Lihat Detail
                        </x-ui.dropdown-item>
                        <x-ui.dropdown-item icon="file-earmark-text" wire:click="loadDokumenRevisi({{ $item->id_rancangan }})">
                            Dokumen Revisi
                        </x-ui.dropdown-item>
                    </x-ui.dropdown>
                </x-slot:aside>
            </x-rancangan.item>
        @empty
            <x-ui.empty-state icon="clipboard2-check" title="Belum ada pengajuan yang diterima." class="py-12" />
        @endforelse
    </div>

    <div class="mt-5">{{ $riwayat->links() }}</div>

    {{-- Dokumen hasil revisi --}}
    @php $revisiTerakhir = $selectedRancangan?->revisi?->last(); @endphp
    <x-ui.modal id="berkasModal" title="Dokumen Revisi" :description="$selectedRancangan?->no_rancangan" size="2xl" on-close="resetDetail">
        @if ($revisiTerakhir)
            <div class="space-y-4">
                <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200 dark:bg-navy-950/40 dark:ring-navy-700">
                    <span class="flex items-center gap-2 text-sm font-semibold"><i class="bi bi-file-earmark-word-fill text-lg text-emerald-500"></i> Matrik hasil revisi</span>
                    <x-ui.file-link folder="revisi/matrik" :path="$revisiTerakhir->revisi_matrik" label="Lihat" />
                </div>

                <section x-data class="rounded-xl ring-1 ring-slate-200 dark:ring-navy-700" x-ref="catatan">
                    <header class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5 dark:border-navy-800">
                        <span class="flex items-center gap-2 text-sm font-semibold"><i class="bi bi-stickies text-amber-500"></i> Catatan Revisi</span>
                        <button type="button" class="rounded-md p-1.5 text-slate-500 hover:bg-slate-100 dark:hover:bg-navy-800" title="Layar penuh"
                            x-on:click="document.fullscreenElement ? document.exitFullscreen() : $refs.catatan.requestFullscreen()">
                            <i class="bi bi-arrows-fullscreen"></i>
                        </button>
                    </header>
                    <div class="max-h-64 overflow-y-auto bg-white p-4 text-base [:fullscreen_&]:max-h-none dark:bg-navy-900">
                        <p class="prose-note text-base">{{ $revisiTerakhir->catatan_revisi ?: 'Tidak ada catatan revisi.' }}</p>
                    </div>
                </section>
            </div>
        @else
            <x-ui.empty-state icon="file-earmark-x" title="Data dokumen tidak tersedia." />
        @endif

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="close()">Tutup</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
