@section('title', 'Dokumentasi Produk Hukum')

<div>
    <x-ui.page-header title="Dokumentasi Produk Hukum" icon="folder2-open"
        description="Arsip produk hukum daerah yang telah ditetapkan · {{ number_format($totalDokumentasi, 0, ',', '.') }} dokumen">
        <x-slot:actions>
            @can('viewAny', App\Models\DokumentasiProdukHukum::class)
                <x-ui.button variant="outline" icon="file-earmark-spreadsheet" class="text-emerald-600 dark:text-emerald-400"
                    x-on:click="showLoading('Menyiapkan Excel...'); $wire.exportExcel()">Excel</x-ui.button>
                <x-ui.button variant="outline-danger" icon="file-earmark-pdf"
                    x-on:click="showLoading('Menyiapkan PDF...'); $wire.exportPDF()">PDF</x-ui.button>
            @endcan
            @can('create', App\Models\DokumentasiProdukHukum::class)
                <x-ui.dropdown width="w-64">
                    <x-slot:trigger>
                        <x-ui.button icon="folder-plus">Tambah Arsip <i class="bi bi-chevron-down text-xs"></i></x-ui.button>
                    </x-slot:trigger>
                    <x-ui.dropdown-item icon="file-earmark-check" x-on:click="Livewire.dispatch('tambahDokumentasi', { mode: 'rancangan' })">
                        Dari rancangan terfasilitasi
                    </x-ui.dropdown-item>
                    <x-ui.dropdown-item icon="archive" x-on:click="Livewire.dispatch('tambahDokumentasi', { mode: 'manual' })">
                        Arsip sebelum ada sistem
                    </x-ui.dropdown-item>
                </x-ui.dropdown>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.list-toolbar placeholder="Cari tentang, nomor, atau nomor rancangan..." :options="[5, 10, 20, 50]">
            <x-form.select wire:model.live="tahun" icon="calendar3" class="sm:w-44" aria-label="Tahun">
                <option value="">Semua tahun</option>
                @foreach ($tahunOptions as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </x-form.select>
            <x-form.select wire:model.live="jenisRancangan" icon="tags" class="sm:w-56" aria-label="Jenis produk hukum">
                <option value="">Semua jenis</option>
                @foreach ($jenisOptions as $jenis)
                    <option value="{{ $jenis }}">{{ $jenis }}</option>
                @endforeach
            </x-form.select>
        </x-ui.list-toolbar>

        @php
            $sortHeader = fn (string $kolom) => $sortColumn === $kolom
                ? ($sortDirection === 'asc' ? 'bi-sort-up' : 'bi-sort-down')
                : 'bi-arrow-down-up opacity-40';
        @endphp

        <x-ui.table>
            <x-slot:head>
                <th>
                    <button type="button" wire:click="sortBy('nomor')" class="inline-flex items-center gap-1.5 uppercase">
                        Produk Hukum <i class="bi {{ $sortHeader('nomor') }}"></i>
                    </button>
                </th>
                <th>Perangkat Daerah</th>
                <th>Berita Daerah</th>
                <th>
                    <button type="button" wire:click="sortBy('tanggal_penetapan')" class="inline-flex items-center gap-1.5 uppercase">
                        Ditetapkan <i class="bi {{ $sortHeader('tanggal_penetapan') }}"></i>
                    </button>
                </th>
                <th>
                    <button type="button" wire:click="sortBy('tanggal_pengarsipan')" class="inline-flex items-center gap-1.5 uppercase">
                        Diarsipkan <i class="bi {{ $sortHeader('tanggal_pengarsipan') }}"></i>
                    </button>
                </th>
                <th class="text-right">Aksi</th>
            </x-slot:head>

            @forelse ($dokumentasiList as $dokumentasi)
                @php $file = private_file_url('dokumentasi/file_produk_hukum', $dokumentasi->file_produk_hukum); @endphp
                <tr wire:key="dokumentasi-{{ $dokumentasi->id }}">
                    <td class="min-w-80">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-navy-900 dark:text-white">{{ $dokumentasi->nomor_formatted }}</span>
                            <x-ui.status-badge :value="$dokumentasi->jenis_dokumentasi" />
                        </div>
                        <p class="mt-0.5 text-slate-600 dark:text-slate-300">{{ $dokumentasi->tentang_dokumentasi }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">
                            @if ($dokumentasi->rancangan)
                                <i class="bi bi-link-45deg"></i> Rancangan {{ $dokumentasi->rancangan->no_rancangan }}
                            @else
                                <i class="bi bi-archive"></i> Dokumen terdahulu
                            @endif
                        </p>
                    </td>
                    <td class="min-w-40">{{ $dokumentasi->perangkatDaerah->nama_perangkat_daerah ?? '-' }}</td>
                    <td class="whitespace-nowrap">{{ $dokumentasi->nomor_tahun_berita ?: '-' }}</td>
                    <td class="whitespace-nowrap">{{ tanggal($dokumentasi->tanggal_penetapan) }}</td>
                    <td class="whitespace-nowrap">{{ tanggal($dokumentasi->tanggal_pengarsipan) }}</td>
                    <td class="text-right whitespace-nowrap">
                        @if ($file)
                            <x-ui.button variant="outline-primary" size="sm" icon="file-earmark-pdf" :href="$file" target="_blank">Lihat</x-ui.button>
                        @endif
                        @can('update', $dokumentasi)
                            <x-ui.button variant="outline" size="sm" square icon="pencil" title="Edit"
                                wire:click="edit({{ $dokumentasi->id }})" loading="edit({{ $dokumentasi->id }})" />
                        @endcan
                        @can('delete', $dokumentasi)
                            <x-ui.button variant="soft-danger" size="sm" square icon="trash" title="Hapus" data-nomor="{{ $dokumentasi->nomor_formatted }}"
                                x-on:click="$confirm({ title: 'Hapus dokumentasi?', text: `${$el.dataset.nomor} beserta filenya akan dihapus permanen.`, confirmText: 'Ya, hapus' })
                                    .then((ok) => ok && $wire.delete({{ $dokumentasi->id }}))" />
                        @endcan
                    </td>
                </tr>
            @empty
                <x-ui.empty-row colspan="6" icon="folder2-open" message="Tidak ada dokumentasi." />
            @endforelse
        </x-ui.table>

        <div class="mt-5">{{ $dokumentasiList->links() }}</div>
    </x-ui.card>

    <livewire:dokumentasi.ajukan-dokumentasi-produk-hukum />

    {{-- Edit dokumentasi --}}
    <x-ui.modal id="modalEditDokumentasi" title="Edit Dokumentasi" description="Perbarui nomor, berita daerah, atau file produk hukum."
        size="2xl" static on-close="resetForm">
        <form id="editDokumentasiForm" wire:submit="update" class="space-y-5">
            @include('livewire.dokumentasi.partials.nomor-fields', ['tahunTetap' => $editTahun])

            <x-form.field label="Tanggal Penetapan" for="edit_tanggal_penetapan" error="tanggal_penetapan" required>
                <x-form.input type="date" id="edit_tanggal_penetapan" wire:model="tanggal_penetapan" />
            </x-form.field>

            <x-form.upload model="file_produk_hukum" :file="$file_produk_hukum" label="Ganti File Produk Hukum" accept="application/pdf"
                hint="Kosongkan jika tidak diganti. PDF, maks. 20MB." />
            <x-ui.loading target="file_produk_hukum">Mengunggah file...</x-ui.loading>
        </form>

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="close()">Batal</x-ui.button>
            <x-ui.button type="submit" form="editDokumentasiForm" icon="save" loading="update">Simpan Perubahan</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
