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

            <div class="row align-items-center mt-4">

                {{-- Filter Tahun --}}
                <div class="col-md-3">
                    <select class="form-control" wire:model.live="tahun">
                        <option value="">Semua Tahun</option>
                        @foreach ($tahunOptions as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>


                {{-- Search Bar --}}
                <div class="col-md-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="ni ni-zoom-split-in"></i></span>
                        </div>
                        <input type="text" class="form-control" wire:model.live="search"
                            placeholder="Cari tentang, Nomor Rancangan...">
                    </div>
                </div>

                {{-- Per Page Dropdown --}}
                <div class="col-md-3">
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
                <div class="col-md-3">
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
                            <th>No Fasilitasi </th>
                            <th>Jenis Produk Hukum</th>
                            <th>Tentang</th>
                            <th>Nomor Produk Hukum</th>
                            <th>Perangkat Daerah</th>
                            <th>Tanggal Pengajuan</th>
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
                                <td class="wrap-text">{{ $dokumentasi->rancangan->no_rancangan }}</td>
                                <td class="wrap-text">
                                    <mark
                                        class="badge-{{ $dokumentasi->rancangan->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                        {{ $dokumentasi->rancangan->jenis_rancangan }}
                                    </mark>
                                </td>
                                <td class="wrap-text w-50">{{ $dokumentasi->rancangan->tentang }}</td>
                                <td class="wrap-text w-25">{{ $dokumentasi->nomor_formatted }}</td>
                                <td class="wrap-text w-50">{{ $dokumentasi->perangkatDaerah->nama_perangkat_daerah }}
                                </td>
                                <td class="wrap-text w-25">
                                    {{ \Carbon\Carbon::parse($dokumentasi->rancangan->tanggal_pengajuan)->translatedFormat('d F Y') }}
                                </td>

                                <td>
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
                            <input type="text" class="form-control" wire:model.defer="nomor">
                            @error('nomor')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Tanggal Publikasi --}}
                        <div class="form-group">
                            <label>Tanggal Publikasi</label>
                            <input type="date" class="form-control" wire:model.defer="tanggal">
                            @error('tanggal')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
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
                            <label>Upload Berita Daerah (PDF) - Kosongkan jika tidak ingin mengubah</label>
                            <input type="file" class="form-control" wire:model="fileProdukHukum">
                            <div wire:loading wire:target="fileProdukHukum">
                                <small class="text-warning">Mengupload file...</small>
                            </div>
                            @error('fileProdukHukum')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
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
