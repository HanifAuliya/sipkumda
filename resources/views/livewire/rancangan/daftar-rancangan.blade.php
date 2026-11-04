@section('title', 'Daftar Pengajuan Rancangan')

<div>
    <x-ui.page-header title="Daftar Rancangan" description="Pengajuan rancangan produk hukum" icon="clipboard2-data" />

    <x-ui.card title="Tabel Rancangan Produk Hukum">
        @unless (auth()->user()->hasRole('Perangkat Daerah'))
            <x-slot:actions>
                <x-ui.button variant="outline-danger" icon="file-earmark-pdf" wire:click="exportPDF" loading="exportPDF">
                    Export PDF
                </x-ui.button>
            </x-slot:actions>
        @endunless

        {{-- Filter --}}
        <div class="grid gap-3 md:grid-cols-12">
            <x-form.select wire:model.live="tahun" icon="calendar3" class="md:col-span-3" aria-label="Tahun">
                <option value="">Semua Tahun</option>
                @foreach ($tahunOptions as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </x-form.select>

            <x-form.select wire:model.live="jenisRancangan" icon="tags" class="md:col-span-3" aria-label="Jenis rancangan">
                <option value="">Semua Jenis Rancangan</option>
                <option value="Peraturan Bupati">Peraturan Bupati</option>
                <option value="Surat Keputusan">Surat Keputusan</option>
            </x-form.select>

            <x-form.select wire:model.live="perPage" icon="list-ol" class="md:col-span-2" aria-label="Data per halaman">
                @foreach ([5, 10, 20, 50] as $size)
                    <option value="{{ $size }}">{{ $size }} / halaman</option>
                @endforeach
            </x-form.select>

            <x-form.input wire:model.live.debounce.400ms="search" icon="search" class="md:col-span-4"
                placeholder="Cari tentang atau nomor rancangan..." aria-label="Cari" />
        </div>
        <x-ui.loading target="tahun, jenisRancangan, perPage, search" class="mt-3">Memuat data...</x-ui.loading>

        {{-- Tabel --}}
        <x-ui.table class="mt-5" wire:loading.class="opacity-60" wire:target="tahun, jenisRancangan, perPage, search, gotoPage, nextPage, previousPage">
            <x-slot:head>
                <th>No Rancangan</th>
                <th>Tentang</th>
                <th>Jenis</th>
                <th>Diajukan</th>
                <th>Berkas</th>
                <th>Penelitian</th>
                <th class="text-right">Aksi</th>
            </x-slot:head>

            @forelse ($rancanganProdukHukum as $rancangan)
                <tr wire:key="rancangan-{{ $rancangan->id_rancangan }}">
                    <td class="font-semibold whitespace-nowrap text-navy-900 dark:text-white">{{ $rancangan->no_rancangan }}</td>
                    <td class="min-w-56">{{ $rancangan->tentang }}</td>
                    <td><x-ui.status-badge :value="$rancangan->jenis_rancangan" /></td>
                    <td class="whitespace-nowrap">{{ tanggal($rancangan->tanggal_pengajuan, empty: 'N/A') }}</td>
                    <td><x-ui.status-badge :value="$rancangan->status_berkas" /></td>
                    <td>
                        <div class="flex flex-wrap gap-1">
                            @foreach ($rancangan->revisi as $revisi)
                                <x-ui.status-badge :value="$revisi->status_revisi" />
                            @endforeach
                        </div>
                    </td>
                    <td>
                        <div class="flex justify-end gap-1.5">
                            @can('view', $rancangan)
                                <x-ui.button variant="navy" size="sm" square icon="eye" title="Lihat detail"
                                    wire:click="showDetail({{ $rancangan->id_rancangan }})"
                                    loading="showDetail({{ $rancangan->id_rancangan }})" />
                                @unless (auth()->user()->hasRole('Perangkat Daerah'))
                                    <x-ui.button variant="soft-danger" size="sm" square icon="trash" title="Hapus"
                                        x-on:click="$confirm({ text: 'Rancangan yang dihapus tidak dapat dikembalikan!', confirmText: 'Ya, hapus' })
                                            .then(ok => ok && $wire.delete({{ $rancangan->id_rancangan }}))" />
                                @endunless
                            @else
                                <x-ui.badge color="danger" icon="lock">Perlu izin</x-ui.badge>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <x-ui.empty-row colspan="7" message="Tidak ada rancangan yang cocok dengan filter." />
            @endforelse
        </x-ui.table>

        <div class="mt-5">{{ $rancanganProdukHukum->links() }}</div>
    </x-ui.card>

    {{-- Detail --}}
    <x-ui.modal id="detailModal" title="Detail Rancangan" :description="$selectedRancangan?->no_rancangan" size="6xl" on-close="resetDetail">
        <x-rancangan.detail :rancangan="$selectedRancangan" />

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="close()">Tutup</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
