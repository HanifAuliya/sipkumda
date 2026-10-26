@section('title', 'Daftar Fasilitasi')

<div>
    <x-ui.page-header title="Daftar Fasilitasi" description="Seluruh fasilitasi rancangan produk hukum" icon="collection">
        <x-slot:actions>
            <x-ui.button variant="outline-danger" icon="file-earmark-pdf"
                x-on:click="showLoading('Menyiapkan PDF...'); $wire.exportPDF()">Export PDF</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.list-toolbar placeholder="Cari nomor rancangan atau tentang..." :options="[5, 10, 20, 50]">
            <x-form.select wire:model.live="tahun" icon="calendar3" class="sm:w-44" aria-label="Tahun">
                <option value="">Semua tahun</option>
                @foreach ($tahunOptions as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </x-form.select>
            <x-form.select wire:model.live="jenisRancangan" icon="tags" class="sm:w-56" aria-label="Jenis rancangan">
                <option value="">Semua jenis</option>
                <option value="Peraturan Bupati">Peraturan Bupati</option>
                <option value="Surat Keputusan">Surat Keputusan</option>
            </x-form.select>
        </x-ui.list-toolbar>

        <x-ui.table>
            <x-slot:head>
                <th>Rancangan</th>
                <th>Status</th>
                <th>Tahapan Paraf</th>
                <th class="text-right">Aksi</th>
            </x-slot:head>

            @forelse ($fasilitasiList as $fasilitasi)
                <tr wire:key="daftar-fasilitasi-{{ $fasilitasi->id }}">
                    <td class="min-w-72">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-navy-900 dark:text-white">{{ $fasilitasi->rancangan->no_rancangan ?? '-' }}</span>
                            <x-ui.status-badge :value="$fasilitasi->rancangan?->jenis_rancangan" />
                        </div>
                        <p class="mt-0.5 text-slate-600 dark:text-slate-300">{{ $fasilitasi->rancangan->tentang ?? '-' }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">
                            {{ $fasilitasi->rancangan->user->perangkatDaerah->nama_perangkat_daerah ?? '-' }} · {{ tanggal($fasilitasi->tanggal_fasilitasi) }}
                        </p>
                    </td>
                    <td class="min-w-40">
                        <dl class="space-y-1.5 text-xs">
                            <div class="flex items-center gap-2"><dt class="w-12 text-slate-500">Berkas</dt><dd><x-ui.status-badge :value="$fasilitasi->status_berkas_fasilitasi" /></dd></div>
                            <div class="flex items-center gap-2"><dt class="w-12 text-slate-500">Validasi</dt><dd><x-ui.status-badge :value="$fasilitasi->status_validasi_fasilitasi" /></dd></div>
                        </dl>
                    </td>
                    <td class="min-w-96"><x-fasilitasi.progres :fasilitasi="$fasilitasi" /></td>
                    <td class="text-right whitespace-nowrap">
                        <x-ui.button variant="navy" size="sm" square icon="eye" title="Lihat detail"
                            x-on:click="Livewire.dispatch('openDetailFasilitasi', { id: {{ $fasilitasi->id }} })" />
                        @can('delete', $fasilitasi)
                            <x-ui.button variant="soft-danger" size="sm" square icon="trash" title="Hapus"
                                x-on:click="$confirm({ title: 'Hapus fasilitasi?', text: 'Berkas fasilitasi dan nota dinas terkait akan ikut terhapus.', confirmText: 'Ya, hapus' })
                                    .then((ok) => ok && $wire.deleteFasilitasi({{ $fasilitasi->id }}))" />
                        @endcan
                    </td>
                </tr>
            @empty
                <x-ui.empty-row colspan="4" icon="collection" message="Tidak ada fasilitasi ditemukan." />
            @endforelse
        </x-ui.table>

        <div class="mt-5">{{ $fasilitasiList->links() }}</div>
    </x-ui.card>
</div>
