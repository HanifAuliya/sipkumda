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
                        <div class="grid grid-cols-2 gap-3">
                            @foreach ([
                                'Disetujui' => ['check-circle-fill text-emerald-500', 'Setujui berkas', 'bg-emerald-50 text-emerald-700 ring-emerald-300 dark:bg-emerald-500/10 dark:text-emerald-300'],
                                'Ditolak' => ['x-circle-fill text-rose-500', 'Tolak berkas', 'bg-rose-50 text-rose-700 ring-rose-300 dark:bg-rose-500/10 dark:text-rose-300'],
                            ] as $value => [$icon, $label, $activeClass])
                                <label @class([
                                    'flex cursor-pointer items-center gap-2.5 rounded-lg px-4 py-3 text-sm font-semibold ring-1 transition',
                                    $activeClass => $statusBerkas === $value,
                                    'ring-slate-200 hover:bg-slate-50 dark:ring-navy-700 dark:hover:bg-navy-800' => $statusBerkas !== $value,
                                ])>
                                    <input type="radio" wire:model.live="statusBerkas" value="{{ $value }}" class="sr-only">
                                    <i class="bi bi-{{ $icon }}"></i> {{ $label }}
                                </label>
                            @endforeach
                        </div>
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
