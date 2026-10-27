<div>
    <x-ui.list-toolbar placeholder="Cari nomor rancangan atau tentang..." :options="[3, 5, 10, 50]" />

    <div class="space-y-4">
        @forelse ($rancangan as $item)
            @php $revisi = $item->revisi->first(); @endphp

            <x-rancangan.item :rancangan="$item" wire:key="ditugaskan-{{ $item->id_rancangan }}">
                <x-slot:meta>
                    <x-rancangan.meta icon="person-gear">Peneliti: <span class="font-semibold text-primary-600 dark:text-primary-300">{{ $revisi?->peneliti?->nama_user ?? 'Belum ditentukan' }}</span></x-rancangan.meta>
                </x-slot:meta>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-500">
                    <span class="flex items-center gap-1.5">Persetujuan berkas <x-ui.status-badge :value="$item->status_berkas" /></span>
                    <span class="flex items-center gap-1.5">Status revisi <x-ui.status-badge :value="$revisi?->status_revisi" /></span>
                </div>

                <x-slot:aside>
                    <x-ui.badge :color="$revisi?->peneliti ? 'success' : 'danger'" size="lg" icon="person-check">
                        {{ $revisi?->peneliti?->nama_user ?? 'Belum ada peneliti' }}
                    </x-ui.badge>
                    <p class="text-xs text-slate-500">Pengajuan tahun {{ \Illuminate\Support\Carbon::parse($item->tanggal_pengajuan)->year }}</p>

                    <x-ui.dropdown>
                        <x-slot:trigger>
                            <x-ui.button variant="outline" icon="gear">Kelola <i class="bi bi-chevron-down text-xs"></i></x-ui.button>
                        </x-slot:trigger>
                        <x-ui.dropdown-item icon="eye"
                            x-on:click="Livewire.dispatch('openModalDetailRancangan', { slug: {{ \Illuminate\Support\Js::from($item->slug) }} })">
                            Lihat Detail
                        </x-ui.dropdown-item>
                        <x-ui.dropdown-item icon="arrow-repeat"
                            x-on:click="$choose({ title: 'Pilih ulang peneliti', label: 'Peneliti baru akan menggantikan peneliti saat ini.', options: {{ \Illuminate\Support\Js::from($listPeneliti) }} })
                                .then(id => id && $wire.pilihUlangPeneliti({{ $item->id_rancangan }}, id))">
                            Pilih Ulang Peneliti
                        </x-ui.dropdown-item>
                        <x-ui.dropdown-item icon="person-dash" danger
                            x-on:click="$confirm({ title: 'Reset peneliti?', text: 'Peneliti akan dilepas dan status revisi kembali ke “Menunggu Peneliti”.', confirmText: 'Ya, reset' })
                                .then(ok => ok && $wire.resetPeneliti({{ $item->id_rancangan }}))">
                            Reset Peneliti
                        </x-ui.dropdown-item>
                    </x-ui.dropdown>
                </x-slot:aside>
            </x-rancangan.item>
        @empty
            <x-ui.empty-state icon="person-check" title="Belum ada peneliti yang ditugaskan." class="py-12" />
        @endforelse
    </div>

    <div class="mt-5">{{ $rancangan->links() }}</div>
</div>
