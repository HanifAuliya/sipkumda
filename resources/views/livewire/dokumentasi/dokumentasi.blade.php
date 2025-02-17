<div>
    <div class="card shadow">
        {{-- Header --}}
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-2">Daftar Dokumentasi Produk Hukum </h3>
                <small>Dibawah adalah dokumentasi produk Hukum</small>
            </div>
            @livewire('dokumentasi.ajukan-dokumentasi-produk-hukum')
        </div>
        <div class="card-body">
            <div class="text-right">
                <button type="button" class="btn btn-outline-default" data-container="body" data-toggle="popover"
                    data-color="default" data-placement="top"
                    data-content="Total Data Arsip Produk Hukum Saat ini sebanyak {{ $totalDokumentasi }} Data Produk Hukum">
                    Klik Informasi
                </button>
                @can('viewAny', App\Models\DokumentasiProdukHukum::class)
                    {{-- Export Excel --}}
                    <button wire:click="exportExcel" class="btn btn-outline-success" wire:loading.attr="disabled"
                        wire:target="exportExcel">
                        <i class="bi bi-file-earmark-spreadsheet" wire:loading.remove wire:target="exportExcel"></i>
                        <span wire:loading.remove wire:target="exportExcel">Export Excel</span>
                        <span wire:loading wire:target="exportExcel">
                            <i class="spinner-border spinner-border-sm"></i> Loading...
                        </span>
                    </button>

                    <!-- Tombol Export PDF -->
                    <button wire:click="exportPDF" class="btn btn-outline-danger" wire:loading.attr="disabled"
                        wire:target="exportPDF">
                        <i class="bi bi-file-earmark-pdf" wire:loading.remove wire:target="exportPDF"></i>
                        <span wire:loading.remove wire:target="exportPDF">Export PDF</span>
                        <span wire:loading wire:target="exportPDF">
                            <i class="spinner-border spinner-border-sm"></i> Loading...
                        </span>
                    </button>
                @endcan
            </div>

            {{-- 🔍 Pencarian --}}
            <div class="row align-items-center mt-4">

                {{-- Filter Tahun --}}
                <div class="col-md-3 position-relative">
                    {{-- Loading Spinner (di atas filter) --}}
                    <div wire:loading wire:target="tahun"
                        class="text-sm text-muted position-absolute w-100  text-primary" style="top: -22px;">
                        <div class="spinner-border spinner-border-sm text-primary mr-2" role="status"></div>Memuat
                        data...
                    </div>

                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="ni ni-calendar-grid-58"></i></span>
                        </div>
                        <select class="form-control" wire:model.live="tahun">
                            <option value="">Semua Tahun</option>
                            @foreach ($tahunOptions as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Search Bar --}}
                <div class="col-md-3 position-relative">
                    {{-- Loading Spinner (di atas input) --}}
                    <div wire:loading wire:target="search"
                        class="text-sm text-muted position-absolute w-100  text-primary" style="top: -22px;">
                        <div class="spinner-border spinner-border-sm text-primary mr-2" role="status"></div>Mencari...
                    </div>

                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="ni ni-zoom-split-in"></i></span>
                        </div>
                        <input type="text" class="form-control" wire:model.live="search"
                            placeholder="Cari tentang, Nomor Rancangan...">
                    </div>
                </div>

                {{-- Per Page Dropdown --}}
                <div class="col-md-3 position-relative">
                    {{-- Loading Spinner (di atas dropdown) --}}
                    <div wire:loading wire:target="perPage"
                        class="text-sm text-muted position-absolute w-100 text-primary" style="top: -22px;">
                        <div class="spinner-border spinner-border-sm text-primary mr-2" role="status"></div>
                        Memperbarui daftar...
                    </div>

                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="ni ni-bullet-list-67"></i></span>
                        </div>
                        <select class="form-control" wire:model.live="perPage">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>

                {{-- Filter by Jenis Rancangan --}}
                <div class="col-md-3 position-relative">
                    {{-- Loading Spinner (di atas dropdown) --}}
                    <div wire:loading wire:target="jenisRancangan"
                        class="text-sm text-muted position-absolute w-100 text-primary" style="top: -22px;">
                        <div class="spinner-border spinner-border-sm text-primary mr-2" role="status"></div>
                        Memfilter data...
                    </div>

                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="ni ni-single-02"></i></span>
                        </div>
                        <select class="form-control" wire:model.live="jenisRancangan">
                            <option value="">Semua Jenis Rancangan</option>
                            <option value="Peraturan Bupati">Peraturan Bupati</option>
                            <option value="Surat Keputusan">Surat Keputusan</option>
                        </select>
                    </div>
                </div>

            </div>

            <div class="table-responsive">
                {{-- Tabel Dokumentasi --}}
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th wire:click="sortBy('nomor')" style="cursor: pointer;">
                                Nomor Produk Hukum
                                @if ($sortColumn === 'nomor')
                                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </th>
                            <th>No Fasilitasi</th>
                            <th>Jenis Produk Hukum</th>
                            <th>Tentang</th>
                            <th>Perangkat Daerah</th>
                            <th wire:click="sortBy('tanggal')" class="sortable text-center">
                                Tanggal Dokumentasi
                                @if ($sortColumn === 'tanggal')
                                    <i class="bi {{ $sortDirection === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down' }}"></i>
                                @endif
                            </th>

                            <th>File Produk Hukum</th>
                            @can('viewAny', App\Models\DokumentasiProdukHukum::class)
                                <th>Aksi</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dokumentasiList as $dokumentasi)
                            <tr>
                                <td>{{ $dokumentasiList->firstItem() + $loop->index }}</td>
                                <td class="text-wrap w-25">{{ $dokumentasi->nomor_formatted }}</td>
                                <td class="text-wrap w-25">{{ $dokumentasi->rancangan->no_rancangan }}</td>
                                <td class="text-wrap">
                                    <mark
                                        class="badge-{{ $dokumentasi->rancangan->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                        {{ $dokumentasi->rancangan->jenis_rancangan }}
                                    </mark>
                                </td>
                                <td
                                    style="min-width: 300px; max-width: 500px; white-space: normal; word-wrap: break-word;">
                                    {{ $dokumentasi->rancangan->tentang }}</td>
                                <td
                                    style="min-width: 300px; max-width: 500px; white-space: normal; word-wrap: break-word;">
                                    {{ $dokumentasi->perangkatDaerah->nama_perangkat_daerah }}
                                </td>
                                <td class="wrap-text w-25">
                                    {{ \Carbon\Carbon::parse($dokumentasi->tanggal)->translatedFormat('d F Y') }}
                                </td>

                                <td
                                    style="min-width: 200px; max-width: 200px; white-space: normal; word-wrap: break-word;">
                                    @if ($dokumentasi->file_produk_hukum)
                                        <a href="{{ url('/view-private/dokumentasi/file_produk_hukum/' . basename($dokumentasi->file_produk_hukum)) }}"
                                            target="_blank">
                                            <i class="bi bi-file-earmark-pdf"></i> Lihat Produk Hukum
                                        </a>
                                    @else
                                        <span class="text-muted">Tidak Ada</span>
                                    @endif
                                </td>
                                @can('viewAny', App\Models\DokumentasiProdukHukum::class)
                                    <td>
                                        <button class="btn btn-warning btn-sm" data-toggle="modal"
                                            data-target="#modalEditDokumentasi"
                                            wire:click.prevent="edit({{ $dokumentasi->id }})">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm"
                                            onclick="confirmDelete({{ $dokumentasi->id }})">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                @endcan
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">Tidak ada dokumentasi</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-3">
                    {{ $dokumentasiList->links() }}
                </div>
            </div>
        </div>

    </div>

    <div wire:ignore.self class="modal fade" id="modalEditDokumentasi" tabindex="-1" role="dialog"
        data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                @if ($dokumentasi)
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Dokumentasi Produk Hukum</h5>
                    </div>
                    <div class="modal-body">
                        {{-- Nomor Produk Hukum --}}
                        <div class="form-group">
                            <label>Nomor Produk Hukum</label>

                            <div class="input-group">
                                <input type="text" class="form-control text-center" value="Nomor" disabled>
                                <input type="text" class="form-control text-center" wire:model.defer="nomor"
                                    placeholder="###" maxlength="3"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,3)">
                                <input type="text" class="form-control text-center"
                                    value="Tahun {{ now()->year }}" disabled>
                                @error('nomor')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        {{-- Nomor Berita Daerah --}}
                        <div class="form-group">
                            <label>Nomor Berita Daerah</label>
                            <input type="text" class="form-control" wire:model.defer="nomorBeritaDaerah">
                            @error('nomorBeritaDaerah')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Tanggal Berita Daerah --}}
                        <div class="form-group">
                            <label>Tanggal Berita Daerah</label>
                            <input type="date" class="form-control" wire:model.defer="tanggalBeritaDaerah">
                            @error('tanggalBeritaDaerah')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Upload File Baru --}}
                        <div class="form-group">
                            <label class="font-weight-bold">Upload Berita Daerah (PDF) - Kosongkan jika tidak ingin
                                mengubah</label>

                            {{-- Input File (Disable setelah upload) --}}
                            <input type="file" class="form-control" wire:model="fileProdukHukum"
                                wire:change="resetError" wire:loading.attr="disabled" accept="application/pdf"
                                {{ $fileProdukHukum ? 'disabled' : '' }}
                                style="{{ $fileProdukHukum ? 'background-color: #e9ecef; cursor: not-allowed; opacity: 0.6;' : '' }}">

                            {{-- Indikator Loading --}}
                            <div wire:loading wire:target="fileProdukHukum" class="text-warning mt-2">
                                <i class="spinner-border spinner-border-sm"></i> Mengupload file...
                            </div>

                            {{-- Error Handling --}}
                            @error('fileProdukHukum')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror

                            {{-- Preview file & tombol hapus --}}
                            @if ($fileProdukHukum)
                                <div class="mt-2 p-2 border rounded bg-light d-flex align-items-center">
                                    <i class="bi bi-file-earmark-pdf text-danger mr-2"></i>
                                    <span class="flex-grow-1">{{ $fileProdukHukum->getClientOriginalName() }}</span>
                                    <button type="button" class="btn btn-sm btn-outline-danger ml-2"
                                        wire:click="removeFile">
                                        <i class="bi bi-trash"></i> Hapus File
                                    </button>
                                </div>
                            @endif
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-outline-warning" data-dismiss="modal"><i
                                class="bi bi-backspace-fill mr-2" wire:click="resetForm"></i>Batal Edit</button>
                        <button class="btn btn-outline-primary" wire:click="update" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="update">
                                <i class="bi bi-save mr-2"></i> Simpan Perubahan
                            </span>
                            <span wire:loading wire:target="update">
                                <i class="spinner-border spinner-border-sm"></i> Memproses...
                            </span>
                        </button>
                    </div>
                @else
                    <div class="card mb-3">
                        <div class="d-flex justify-content-center align-items-start"
                            style="min-height: 200px; padding-top: 50px;">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-3 info-text">Sedang memuat data, harap tunggu...</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data yang dihapus tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch('deleteConfirmed', {
                        id: id
                    }); // Kirim event ke Livewire
                }
            });
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            Livewire.on('openModalEditDokumentasi', () => {
                $('#modalEditDokumentasi').modal('show');
            });

            Livewire.on('closeModalEditDokumentasi', () => {
                $('#modalEditDokumentasi').modal('hide');
            });

            Livewire.on('closeModalTambahDokumentasi', function() {
                $('#modalTambahDokumentasi').modal('hide');
            });

        });

        document.addEventListener('livewire:initialized', function() {
            Livewire.on('swal:error', event => {
                Swal.fire({
                    icon: event.type, // success, error
                    title: event.title,
                    text: event.message,
                    confirmButtonColor: event.type === 'success' ? '#3085d6' : '#d33',
                    confirmButtonText: 'OK Gagal'
                });
            });
        });
    </script>

</div>
