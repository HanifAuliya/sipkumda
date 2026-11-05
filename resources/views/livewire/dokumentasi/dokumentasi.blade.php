@section('title', 'Dokumentasi Produk Hukum')

<div>
    <x-ui.page-header title="Dokumentasi Produk Hukum" icon="folder2-open"
        description="Arsip produk hukum daerah yang telah ditetapkan · {{ number_format($totalDokumentasi, 0, ',', '.') }} dokumen">
        <x-slot:actions>
            @can('viewAny', App\Models\DokumentasiProdukHukum::class)
                <x-ui.button variant="outline" icon="file-earmark-spreadsheet" title="Unduh daftar sesuai pencarian dan filter yang sedang dipakai"
                    x-on:click="showLoading('Menyiapkan Excel...'); $wire.exportExcel()"><span class="max-sm:hidden">Unduh</span> Excel</x-ui.button>
                <x-ui.button variant="outline" icon="file-earmark-pdf" title="Unduh daftar sesuai pencarian dan filter yang sedang dipakai"
                    x-on:click="showLoading('Menyiapkan PDF...'); $wire.exportPDF()"><span class="max-sm:hidden">Unduh</span> PDF</x-ui.button>
            @endcan
            @can('create', App\Models\DokumentasiProdukHukum::class)
                <x-ui.dropdown width="w-80 max-w-[calc(100vw-2rem)]">
                    <x-slot:trigger>
                        <x-ui.button icon="folder-plus">Tambah Arsip <i class="bi bi-chevron-down text-xs"></i></x-ui.button>
                    </x-slot:trigger>
                    <x-ui.dropdown-item icon="file-earmark-check" description="Rancangan yang fasilitasinya sudah diterima." x-on:click="Livewire.dispatch('tambahDokumentasi', { mode: 'rancangan' })">
                        Dari rancangan di sistem
                    </x-ui.dropdown-item>
                    <x-ui.dropdown-item icon="archive" description="Produk hukum yang ditetapkan sebelum ada sistem ini." x-on:click="Livewire.dispatch('tambahDokumentasi', { mode: 'manual' })">
                        Dokumen lama
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
                    <button type="button" wire:click="sortBy('nomor')" class="-my-2 inline-flex items-center gap-1.5 py-2 hover:text-primary-600">
                        Produk Hukum <i class="bi {{ $sortHeader('nomor') }}"></i>
                    </button>
                </th>
                <th>Perangkat Daerah</th>
                <th>Berita Daerah</th>
                <th>
                    <button type="button" wire:click="sortBy('tanggal_penetapan')" class="-my-2 inline-flex items-center gap-1.5 py-2 hover:text-primary-600">
                        Ditetapkan <i class="bi {{ $sortHeader('tanggal_penetapan') }}"></i>
                    </button>
                </th>
                <th>
                    <button type="button" wire:click="sortBy('tanggal_pengarsipan')" class="-my-2 inline-flex items-center gap-1.5 py-2 hover:text-primary-600">
                        Diarsipkan <i class="bi {{ $sortHeader('tanggal_pengarsipan') }}"></i>
                    </button>
                </th>
                <th class="text-right">Aksi</th>
            </x-slot:head>

            @forelse ($dokumentasiList as $dokumentasi)
                @php $file = private_file_url('dokumentasi/file_produk_hukum', $dokumentasi->file_produk_hukum); @endphp
                <tr wire:key="dokumentasi-{{ $dokumentasi->id }}">
                    <td class="min-w-76">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="sel-utama">{{ $dokumentasi->nomor_formatted }}</span>
                            <x-ui.status-badge :value="$dokumentasi->jenis_dokumentasi" />
                        </div>
                        <p class="sel-judul mt-1" title="{{ $dokumentasi->tentang_dokumentasi }}">{{ $dokumentasi->tentang_dokumentasi }}</p>
                        <p class="sel-kedua mt-1">
                            @if ($dokumentasi->rancangan)
                                <i class="bi bi-link-45deg"></i> Rancangan {{ $dokumentasi->rancangan->no_rancangan }}
                            @else
                                <i class="bi bi-archive"></i> Dokumen terdahulu
                            @endif
                        </p>
                    </td>
                    <td class="min-w-36"><p class="sel-judul">{{ $dokumentasi->perangkatDaerah->nama_perangkat_daerah ?? '-' }}</p></td>
                    <td class="whitespace-nowrap">{{ $dokumentasi->nomor_tahun_berita ?: '-' }}</td>
                    <td class="whitespace-nowrap" title="{{ tanggal($dokumentasi->tanggal_penetapan) }}">{{ tanggal_singkat($dokumentasi->tanggal_penetapan) }}</td>
                    <td class="whitespace-nowrap" title="{{ tanggal($dokumentasi->tanggal_pengarsipan) }}">{{ tanggal_singkat($dokumentasi->tanggal_pengarsipan) }}</td>
                    <td>
                        {{-- Desktop: tombol berlabel supaya jelas fungsinya. HP: ikon sebaris supaya tabel tetap terbaca. --}}
                        <div class="flex justify-end gap-1.5 sm:ml-auto sm:grid sm:w-44 sm:grid-cols-2">
                            @if ($file)
                                <x-ui.button variant="outline-primary" size="sm" icon="file-earmark-pdf" :href="$file" target="_blank" class="max-sm:size-10 max-sm:px-0 sm:col-span-2" title="Buka file produk hukum di tab baru" aria-label="Lihat file"><span class="max-sm:hidden">Lihat File</span></x-ui.button>
                            @endif
                            @can('update', $dokumentasi)
                                <x-ui.button variant="outline" size="sm" icon="pencil" class="max-sm:size-10 max-sm:px-0" title="Ubah nomor, tanggal, atau file" aria-label="Ubah"
                                    wire:click="edit({{ $dokumentasi->id }})" loading="edit({{ $dokumentasi->id }})"><span class="max-sm:hidden">Ubah</span></x-ui.button>
                            @endcan
                            @can('delete', $dokumentasi)
                                <x-ui.button variant="soft-danger" size="sm" icon="trash" class="max-sm:size-10 max-sm:px-0" title="Hapus dokumentasi ini" aria-label="Hapus" data-nomor="{{ $dokumentasi->nomor_formatted }}"
                                    x-on:click="$confirm({ title: 'Hapus dokumentasi?', text: `${$el.dataset.nomor} beserta filenya akan dihapus permanen.`, confirmText: 'Ya, hapus' })
                                        .then((ok) => ok && $wire.delete({{ $dokumentasi->id }}))"><span class="max-sm:hidden">Hapus</span></x-ui.button>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <x-ui.empty-row colspan="6" icon="folder2-open" message="Tidak ada dokumentasi yang cocok.">
                    <x-ui.reset-filter :aktif="filled($search) || filled($tahun) || filled($jenisRancangan)" :nilai="['search' => '', 'tahun' => '', 'jenisRancangan' => '']" />
                </x-ui.empty-row>
            @endforelse
        </x-ui.table>

        <div class="mt-5">{{ $dokumentasiList->links() }}</div>
    </x-ui.card>

    <livewire:dokumentasi.ajukan-dokumentasi-produk-hukum />

    {{-- Edit dokumentasi --}}
    <x-ui.modal id="modalEditDokumentasi" title="Ubah Dokumentasi" description="Perbarui nomor, berita daerah, atau file produk hukum."
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
