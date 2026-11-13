@section('title', 'Dokumentasi Produk Hukum')

<div>
    <x-ui.page-header title="Dokumentasi Produk Hukum" icon="folder2-open"
        description="Arsip produk hukum daerah yang telah ditetapkan · {{ number_format($totalDokumentasi, 0, ',', '.') }} dokumen">
        <x-slot:actions>
            @can('viewAny', App\Models\DokumentasiProdukHukum::class)
                {{-- Satu tombol Unduh supaya kepala halaman tidak sesak; isinya sesuai pencarian dan filter yang dipakai. --}}
                <x-ui.dropdown align="right" width="w-60">
                    <x-slot:trigger>
                        <x-ui.button variant="outline" icon="download" title="Unduh daftar sesuai pencarian dan filter yang sedang dipakai">
                            Unduh <i class="bi bi-chevron-down text-xs" aria-hidden="true"></i>
                        </x-ui.button>
                    </x-slot:trigger>
                    <x-ui.dropdown-item icon="file-earmark-spreadsheet" x-on:click="showLoading('Menyiapkan Excel...'); $wire.exportExcel()">Excel</x-ui.dropdown-item>
                    <x-ui.dropdown-item icon="file-earmark-pdf" x-on:click="showLoading('Menyiapkan PDF...'); $wire.exportPDF()">PDF</x-ui.dropdown-item>
                </x-ui.dropdown>
            @endcan
            @can('create', App\Models\DokumentasiProdukHukum::class)
                {{-- Dua sumber arsip: naskah dari sistem (terkait rancangan/fasilitasinya) dan produk hukum lama sebelum ada sistem. --}}
                @if ($bolehAntre)
                    <x-ui.button icon="archive" title="Naskah SIFKUMDA yang paraf dan tanda tangannya lengkap"
                        x-on:click="Livewire.dispatch('tambahDokumentasi', { sumber: 'sistem' })">Arsipkan dari Sistem</x-ui.button>
                @endif
                <x-ui.button variant="outline" icon="folder-plus" title="Produk hukum yang ditetapkan sebelum ada sistem"
                    x-on:click="Livewire.dispatch('tambahDokumentasi', { sumber: 'lama' })">Tambah Arsip Lama</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        @if ($bolehAntre)
            {{-- Arsip dan antreannya di satu halaman: yang sudah terarsip, dan naskah yang tinggal diarsipkan. --}}
            <x-ui.tabs class="mb-5">
                <x-ui.tab :active="$tab === 'terarsip'" icon="folder-check" :count="$totalDokumentasi" wire:click="$set('tab', 'terarsip')">Terarsip</x-ui.tab>
                <x-ui.tab :active="$tab === 'menunggu'" icon="hourglass-split" :count="$jumlahAntre" wire:click="$set('tab', 'menunggu')">Menunggu Arsip</x-ui.tab>
            </x-ui.tabs>
        @endif

        <x-ui.list-toolbar placeholder="Cari tentang atau nomor..." :options="[5, 10, 20, 50]">
            @if ($tab === 'terarsip')
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
            @else
                <x-form.select wire:model.live="jenisRancangan" icon="tags" class="sm:w-56" aria-label="Jenis produk hukum">
                    <option value="">Semua jenis</option>
                    <option value="Peraturan Bupati">Peraturan Bupati</option>
                    <option value="Keputusan Bupati">Keputusan Bupati</option>
                </x-form.select>
            @endif
        </x-ui.list-toolbar>

        @if ($tab === 'menunggu')
            <x-ui.table>
                <x-slot:head>
                    <th>Naskah</th>
                    <th class="max-sm:hidden">Tentang</th>
                    <th class="max-sm:hidden">Perangkat Daerah</th>
                    <th class="max-sm:hidden">Tanda Tangan Terakhir</th>
                    <th class="text-right">Aksi</th>
                </x-slot:head>

                @forelse ($antrean as $naskah)
                    <tr wire:key="antre-{{ $naskah->id }}">
                        <td class="sm:whitespace-nowrap">
                            <p class="sel-utama">{{ $naskah->nomor_tampil }}</p>
                            <x-ui.status-badge :value="$naskah->jenis" class="mt-1" />
                            <p class="sel-judul mt-1.5 sm:hidden">{{ $naskah->tentang }}</p>
                        </td>
                        <td class="min-w-64 max-sm:hidden"><p class="sel-judul">{{ $naskah->tentang }}</p></td>
                        <td class="min-w-44 max-sm:hidden"><p class="sel-judul">{{ $naskah->pemrakarsa->perangkatDaerah->nama_perangkat_daerah ?? $naskah->pemrakarsa->nama_user }}</p></td>
                        <td class="whitespace-nowrap max-sm:hidden">{{ tanggal(collect($naskah->tahapan_ttd ?? [])->sort()->last()) }}</td>
                        <td>
                            <div class="flex justify-end gap-1.5">
                                <x-ui.button size="sm" variant="outline" icon="eye" :href="route('naskah.pantau', $naskah)" title="Lihat naskah dan berkasnya"
                                    class="max-sm:size-9 max-sm:px-0"><span class="max-sm:hidden">Lihat</span></x-ui.button>
                                <x-ui.button size="sm" variant="success" icon="archive"
                                    x-on:click="Livewire.dispatch('tambahDokumentasi', { sumber: 'sistem', naskah: {{ $naskah->id }} })">Arsipkan</x-ui.button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="5" icon="hourglass-split" :message="filled($search) || filled($jenisRancangan) ? 'Tidak ada naskah yang cocok.' : 'Tidak ada naskah yang menunggu diarsipkan.'">
                        <x-ui.reset-filter :aktif="filled($search) || filled($jenisRancangan)" :nilai="['search' => '', 'jenisRancangan' => '']" />
                    </x-ui.empty-row>
                @endforelse
            </x-ui.table>
        @else

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
                <th class="max-sm:hidden">Perangkat Daerah</th>
                <th class="max-sm:hidden">Berita Daerah</th>
                <th class="max-sm:hidden">
                    <button type="button" wire:click="sortBy('tanggal_penetapan')" class="-my-2 inline-flex items-center gap-1.5 py-2 hover:text-primary-600">
                        Ditetapkan <i class="bi {{ $sortHeader('tanggal_penetapan') }}"></i>
                    </button>
                </th>
                <th class="max-sm:hidden">
                    <button type="button" wire:click="sortBy('tanggal_pengarsipan')" class="-my-2 inline-flex items-center gap-1.5 py-2 hover:text-primary-600">
                        Diarsipkan <i class="bi {{ $sortHeader('tanggal_pengarsipan') }}"></i>
                    </button>
                </th>
                <th class="text-right">Aksi</th>
            </x-slot:head>

            @forelse ($dokumentasiList as $dokumentasi)
                @php $file = private_file_url('dokumentasi/file_produk_hukum', $dokumentasi->file_produk_hukum); @endphp
                <tr wire:key="dokumentasi-{{ $dokumentasi->id }}">
                    <td class="sm:min-w-76">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="sel-utama">{{ $dokumentasi->nomor_formatted }}</span>
                            <x-ui.status-badge :value="$dokumentasi->jenis_dokumentasi" />
                        </div>
                        <p class="sel-judul mt-1 max-sm:line-clamp-3" title="{{ $dokumentasi->tentang_dokumentasi }}">{{ $dokumentasi->tentang_dokumentasi }}</p>
                        <p class="sel-kedua mt-1 max-sm:hidden">
                            @if ($dokumentasi->naskah)
                                {{-- Diarsipkan dari naskah v2: yang berhak melihat naskahnya bisa langsung membukanya. --}}
                                <i class="bi bi-link-45deg"></i>
                                @can('view', $dokumentasi->naskah)
                                    <a href="{{ route('naskah.kerja', $dokumentasi->naskah) }}" class="font-medium text-primary-600 underline-offset-2 hover:underline dark:text-primary-300">Naskah {{ $dokumentasi->naskah->nomor_tampil }}</a>
                                @else
                                    Naskah {{ $dokumentasi->naskah->nomor_tampil }}
                                @endcan
                            @else
                                <i class="bi bi-archive"></i> Dokumen terdahulu
                            @endif
                        </p>
                        <p class="sel-kedua mt-1 sm:hidden">Ditetapkan {{ tanggal_singkat($dokumentasi->tanggal_penetapan) }}</p>
                    </td>
                    <td class="min-w-44 max-sm:hidden"><p class="sel-judul line-clamp-none">{{ $dokumentasi->perangkatDaerah->nama_perangkat_daerah ?? '-' }}</p></td>
                    <td class="whitespace-nowrap max-sm:hidden">{{ $dokumentasi->berita_daerah ?? '-' }}</td>
                    <td class="whitespace-nowrap max-sm:hidden" title="{{ tanggal($dokumentasi->tanggal_penetapan) }}">{{ tanggal_singkat($dokumentasi->tanggal_penetapan) }}</td>
                    <td class="whitespace-nowrap max-sm:hidden" title="{{ tanggal($dokumentasi->tanggal_pengarsipan) }}">{{ tanggal_singkat($dokumentasi->tanggal_pengarsipan) }}</td>
                    <td>
                        {{-- Desktop: tombol berlabel supaya jelas fungsinya. HP: ikon sebaris supaya tabel tetap terbaca. --}}
                        <div class="flex justify-end gap-1.5 max-[380px]:flex-col max-[380px]:items-end sm:ml-auto sm:grid sm:w-44 sm:grid-cols-2">
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
        @endif
    </x-ui.card>

    <livewire:dokumentasi.ajukan-dokumentasi-produk-hukum />

    {{-- Edit dokumentasi --}}
    <x-ui.modal id="modalEditDokumentasi" title="Ubah Dokumentasi" description="Perbarui nomor, berita daerah, atau file produk hukum."
        size="2xl" static on-close="resetForm">
        <form id="editDokumentasiForm" wire:submit="update" class="space-y-5">
            @include('livewire.dokumentasi.partials.nomor-fields', ['tahunTetap' => null, 'beritaWajib' => ! $editKeputusan])

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
