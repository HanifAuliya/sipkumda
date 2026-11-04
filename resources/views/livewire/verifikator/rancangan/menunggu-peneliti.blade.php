<div>
    <x-ui.list-toolbar placeholder="Cari nomor rancangan atau tentang..." :options="[3, 5, 10, 50]" />

    <div class="space-y-4">
        @forelse ($rancangan as $item)
            @php $revisi = $item->revisi->first(); @endphp

            <x-rancangan.item :rancangan="$item" wire:key="menunggu-peneliti-{{ $item->id_rancangan }}">
                <x-slot:meta>
                    <x-rancangan.meta icon="person">Pemohon: {{ $item->user->nama_user ?? '-' }}</x-rancangan.meta>
                    <x-rancangan.meta icon="calendar-check">Berkas disetujui {{ tanggal($item->tanggal_berkas_disetujui, true) }}</x-rancangan.meta>
                </x-slot:meta>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-500">
                    <span class="flex items-center gap-1.5">Persetujuan berkas <x-ui.status-badge :value="$item->status_berkas" /></span>
                    <span class="flex items-center gap-1.5">Status revisi <x-ui.status-badge :value="$revisi?->status_revisi" /></span>
                </div>

                <x-slot:aside>
                    @if ($revisi?->peneliti)
                        <x-ui.badge color="success" size="lg" icon="person-check">{{ $revisi->peneliti->nama_user }}</x-ui.badge>
                    @else
                        <x-ui.badge color="danger" size="lg" icon="person-exclamation">Menunggu pemilihan peneliti</x-ui.badge>
                    @endif
                    <p class="text-xs text-slate-500">Pengajuan tahun {{ \Illuminate\Support\Carbon::parse($item->tanggal_pengajuan)->year }}</p>
                    <x-ui.button variant="navy" icon="person-plus" wire:click="openModal({{ $item->id_rancangan }})"
                        loading="openModal({{ $item->id_rancangan }})">Pilih Peneliti</x-ui.button>
                </x-slot:aside>
            </x-rancangan.item>
        @empty
            <x-ui.empty-state icon="person-check" title="Tidak ada rancangan yang menunggu peneliti." class="py-12" />
        @endforelse
    </div>

    <div class="mt-5">{{ $rancangan->links() }}</div>

    {{-- Pilih peneliti --}}
    @php $penelitiTerpilih = $selectedRancangan?->revisi->first()?->peneliti; @endphp
    <x-ui.modal id="modalPilihPeneliti" title="Pilih Peneliti" :description="$selectedRancangan?->no_rancangan" size="5xl" on-close="resetDetail">
        @if ($selectedRancangan)
            <div class="grid gap-5 lg:grid-cols-5">
                <section class="rounded-xl ring-1 ring-slate-100 lg:col-span-3 dark:ring-navy-800">
                    <h4 class="border-b border-slate-100 px-4 py-3 text-sm font-semibold dark:border-navy-800">Informasi Rancangan</h4>
                    <x-rancangan.info :rancangan="$selectedRancangan" class="px-4" />
                </section>

                <div class="space-y-4 lg:col-span-2">
                    @if ($penelitiTerpilih)
                        <x-ui.alert type="info" icon="info-circle">
                            <strong>{{ $penelitiTerpilih->nama_user }}</strong> telah ditetapkan sebagai peneliti.
                            Lihat tab <strong>Peneliti Ditugaskan</strong> untuk informasi lebih lanjut.
                        </x-ui.alert>
                    @else
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Pilih peneliti untuk menugaskan penelitian rancangan ini. Peneliti dan perangkat daerah akan menerima notifikasi.
                        </p>
                        <x-form.field label="Peneliti" for="selectedPeneliti" error="selectedPeneliti" required>
                            <x-form.select id="selectedPeneliti" wire:model="selectedPeneliti" icon="person-badge">
                                <option value="" hidden>Pilih peneliti...</option>
                                @foreach ($listPeneliti as $peneliti)
                                    <option value="{{ $peneliti->id }}">{{ $peneliti->nama_user }}</option>
                                @endforeach
                            </x-form.select>
                        </x-form.field>
                    @endif
                </div>
            </div>
        @else
            <x-rancangan.detail :rancangan="null" />
        @endif

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="close()">Batal</x-ui.button>
            @unless ($penelitiTerpilih)
                <x-ui.button variant="navy" icon="person-add" wire:click="assignPeneliti" loading="assignPeneliti">Tugaskan Peneliti</x-ui.button>
            @endunless
        </x-slot:footer>
    </x-ui.modal>
</div>
