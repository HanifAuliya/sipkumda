@section('title', 'Daftar Pengajuan Rancangan')
@section('manual')
    <div class="card  mb--2">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h3 class="mb-0">Panduan Daftar Isi</h3>
                <p class="description">
                    © Hak Cipta Bagian Hukum Sekretariat Daerah Kabupaten Hulu
                    Sungai Tengah.
                </p>
            </div>
            @if ($isAdmin)
                {{-- Tombol untuk Admin --}}
                <button class="btn btn-outline-default">
                    <i class="bi bi-info-circle"></i> Panduan
                </button>
            @elseif ($isVerifier)
                {{-- Tombol untuk Verifikator --}}
                <button class="btn btn-outline-default">
                    <i class="bi bi-info-circle"></i> Panduan
                </button>
            @else
                {{-- Tombol untuk Verifikator --}}
                <button class="btn btn-outline-warning">
                    <i class="bi bi-info-circle"></i> Panduan
                </button>
            @endif
        </div>
    </div>
@endsection


<div>
    <div class="row mb-1">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">Tabel Rancangan Produk Hukum</h3>
                    </div>
                    <button wire:click="exportPDF" class="btn btn-outline-danger" wire:loading.attr="disabled"
                        wire:target="exportPDF">
                        <i class="bi bi-file-earmark-pdf" wire:loading.remove wire:target="exportPDF"></i>
                        <span wire:loading.remove wire:target="exportPDF">Export PDF</span>
                        <span wire:loading wire:target="exportPDF">
                            <i class="spinner-border spinner-border-sm"></i> Loading...
                        </span>
                    </button>

                </div>
                <div class="card-body">
                    {{-- 🔍 Pencarian --}}
                    <div class="row align-items-center mt-4">

                        {{-- Filter Tahun --}}
                        <div class="col-md-2 position-relative">
                            {{-- Loading Spinner (di atas filter) --}}
                            <div wire:loading wire:target="tahun"
                                class="text-sm text-muted position-absolute w-100  text-primary" style="top: -22px;">
                                <div class="spinner-border spinner-border-sm text-primary mr-2" role="status"></div>
                                Memuat
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
                        <div class="col-md-5 position-relative">
                            {{-- Loading Spinner (di atas input) --}}
                            <div wire:loading wire:target="search"
                                class="text-sm text-muted position-absolute w-100  text-primary" style="top: -22px;">
                                <div class="spinner-border spinner-border-sm text-primary mr-2" role="status"></div>
                                Mencari...
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
                        <div class="col-md-2 position-relative">
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
                    {{-- Responsive table container --}}
                    <div class="table-responsive">
                        <table class="table text-sm">
                            <thead>
                                <tr>
                                    <th> No Rancangan
                                    </th>
                                    <th>Tentang</th>
                                    <th>Jenis Rancangan </th>
                                    <th>Tanggal Pengajuan
                                    </th>
                                    <th>Status Persetujuan Berkas</th>
                                    <th>Status Revisi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rancanganProdukHukum as $rancangan)
                                    <tr>
                                        <td>{{ $rancangan->no_rancangan }}</td>
                                        <td class="text-wrap-td-50">{{ $rancangan->tentang }}</td>
                                        <td class="still-text">
                                            <mark
                                                class="badge-{{ $rancangan->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                                {{ $rancangan->jenis_rancangan }}
                                            </mark>
                                        </td>
                                        <td>
                                            {{ $rancangan->tanggal_pengajuan ? \Carbon\Carbon::parse($rancangan->tanggal_pengajuan)->translatedFormat('d F Y') : 'N/A' }}
                                        </td>
                                        <td class="still-text">
                                            <mark
                                                class="badge-{{ $rancangan->status_berkas === 'Disetujui' ? 'success' : ($rancangan->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                {{ $rancangan->status_berkas }}
                                            </mark>
                                        </td>
                                        <td class="still-text">
                                            @foreach ($rancangan->revisi as $revisi)
                                                <mark
                                                    class="badge-{{ $revisi->status_revisi === 'Direvisi'
                                                        ? 'success'
                                                        : ($revisi->status_revisi === 'Menunggu Peneliti'
                                                            ? 'info text-default'
                                                            : ($revisi->status_revisi === 'Proses Revisi'
                                                                ? 'warning'
                                                                : ($revisi->status_revisi === 'Belum Tahap Revisi'
                                                                    ? 'danger'
                                                                    : 'secondary'))) }} badge-pill">
                                                    {{ $revisi->status_revisi }}
                                                </mark>
                                            @endforeach
                                        </td>

                                        <td>
                                            @can('view', $rancangan)
                                                <button class="btn btn-default btn-sm"
                                                    wire:click="showDetail({{ $rancangan->id_rancangan }})"
                                                    class="btn btn-info btn-sm" data-target="#detailModal"
                                                    data-toggle="modal">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm"
                                                    onclick="confirmDelete({{ $rancangan->id_rancangan }})">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            @else
                                                <small class="text-danger">Perlu Izin!</small>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Tidak ada data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="mt-3">
                            {{ $rancanganProdukHukum->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div wire:ignore.self class="modal fade" id="detailModal" tabindex="-1" role="dialog"
        aria-labelledby="detailModalLabel" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-xl no-style-modal" role="document">
            <div class="modal-content ">
                {{-- Body Modal untuk header --}}

                {{-- Header --}}
                @if ($selectedRancangan)
                    <div class="row mt-3">
                        {{-- Informasi Utama --}}
                        <div class="col-md-6 mb-2">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="mb-0 ">Informasi Utama</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive modal-table">
                                        <table class="table table-sm table-borderless">
                                            <tbody>
                                                <tr>
                                                    <th class="info-text w-25">Nomor</th>
                                                    <td class="text-wrap w-75">
                                                        {{ $selectedRancangan->no_rancangan ?? 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Jenis</th>
                                                    <td class="text-wrap w-75">
                                                        @if ($selectedRancangan && $selectedRancangan->jenis_rancangan)
                                                            <mark
                                                                class="badge-{{ $selectedRancangan->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                                                {{ $selectedRancangan->jenis_rancangan }}
                                                            </mark>
                                                        @else
                                                            <span class="text-danger">Data tidak tersedia</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Tentang</th>
                                                    <td class="text-wrap w-75">
                                                        {{ $selectedRancangan->tentang ?? 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Tanggal Pengajuan</th>
                                                    <td class="text-wrap w-75">
                                                        {{ $selectedRancangan->tanggal_pengajuan
                                                            ? \Carbon\Carbon::parse($selectedRancangan->tanggal_pengajuan)->translatedFormat('d F Y, H:i')
                                                            : 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">User Pengaju</th>
                                                    <td class="text-wrap w-75">
                                                        {{ $selectedRancangan->user->nama_user ?? 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Perangkat Daerah</th>
                                                    <td class="text-wrap w-75">
                                                        {{ $selectedRancangan->user->perangkatDaerah->nama_perangkat_daerah ?? 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Nomor Nota</th>
                                                    <td class="text-wrap w-75">
                                                        {{ $selectedRancangan->nomor_nota ?? 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Tanggal Nota</th>
                                                    <td class="text-wrap w-75">
                                                        {{ $selectedRancangan->tanggal_nota
                                                            ? \Carbon\Carbon::parse($selectedRancangan->tanggal_nota)->translatedFormat('d F Y')
                                                            : 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Status Rancangan</th>
                                                    <td class="text-wrap w-75">
                                                        @if ($selectedRancangan && $selectedRancangan->status_rancangan)
                                                            <mark
                                                                class="badge-{{ $selectedRancangan->status_rancangan === 'Disetujui'
                                                                    ? 'success'
                                                                    : ($selectedRancangan->status_rancangan === 'Ditolak'
                                                                        ? 'danger'
                                                                        : 'warning') }} badge-pill">
                                                                {{ $selectedRancangan->status_rancangan }}
                                                            </mark>
                                                        @else
                                                            <span class="badge badge-secondary">N/A</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Tanggal Rancangan disetujui</th>
                                                    <td class="text-wrap w-75">
                                                        {{ $selectedRancangan->tanggal_rancangan_disetujui
                                                            ? \Carbon\Carbon::parse($selectedRancangan->tanggal_rancangan_disetujui)->translatedFormat('d F Y, H:i')
                                                            : 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Status Berkas</th>
                                                    @if ($selectedRancangan && $selectedRancangan->status_berkas)
                                                        <td class="text-wrap w-75">
                                                            <mark
                                                                class="badge-{{ $selectedRancangan->status_berkas === 'Disetujui' ? 'success' : ($selectedRancangan->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                                {{ $selectedRancangan->status_berkas }}
                                                            </mark>
                                                        </td>
                                                    @else
                                                        <td class="text-wrap w-75">
                                                            <span class="badge badge-secondary">N/A</span>
                                                        </td>
                                                    @endif
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Tanggal Berkas Disetujui</th>
                                                    <td class="text-wrap w-75">
                                                        {{ $selectedRancangan->tanggal_berkas_disetujui
                                                            ? \Carbon\Carbon::parse($selectedRancangan->tanggal_berkas_disetujui)->translatedFormat('d F Y, H:i')
                                                            : 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Nota Dinas</th>
                                                    <td class="text-wrap w-75">
                                                        @if (isset($selectedRancangan->nota_dinas_pd))
                                                            <a href="{{ url('/view-private/rancangan/nota_dinas/' . basename($selectedRancangan->nota_dinas_pd)) }}"
                                                                target="_blank" class="d-flex align-items-center">
                                                                <i class="bi bi-file-earmark-pdf mr-2"
                                                                    style="font-size: 1.5rem; color: #ffc107;"></i>
                                                                <span>Lihat Nota</span>
                                                            </a>
                                                        @else
                                                            <span style="color: #6c757d;">Data Tidak Ada</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">File Rancangan</th>
                                                    <td class="text-wrap w-75">
                                                        @if (isset($selectedRancangan->rancangan))
                                                            <a href="{{ url('/view-private/rancangan/rancangan/' . basename($selectedRancangan->rancangan)) }}"
                                                                target="_blank" class="d-flex align-items-center">
                                                                <i class="bi bi-file-earmark-pdf mr-2"
                                                                    style="font-size: 1.5rem; color: #007bff;"></i>
                                                                <span>Lihat Rancangan</span>
                                                            </a>
                                                        @else
                                                            <span style="color: #6c757d;">Data Tidak Ada</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Matrik</th>
                                                    <td class="text-wrap w-75">
                                                        @if (isset($selectedRancangan->matrik))
                                                            <a href="{{ url('/view-private/rancangan/matrik/' . basename($selectedRancangan->matrik)) }}"
                                                                target="_blank" class="d-flex align-items-center">
                                                                <i class="bi bi-file-earmark-pdf mr-2"
                                                                    style="font-size: 1.5rem; color: #28a745;"></i>
                                                                <span>Lihat Matrik</span>
                                                            </a>
                                                        @else
                                                            <span style="color: #6c757d;">Data Tidak Ada</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Bahan Pendukung</th>
                                                    <td class="text-wrap w-75">
                                                        @if (isset($selectedRancangan->bahan_pendukung))
                                                            <a href="{{ url('/view-private/rancangan/bahan_pendukung/' . basename($selectedRancangan->bahan_pendukung)) }}"
                                                                target="_blank" class="d-flex align-items-center">
                                                                <i class="bi bi-file-earmark-pdf mr-2"
                                                                    style="font-size: 1.5rem; color: #dc3545;"></i>
                                                                <span>Lihat Bahan</span>
                                                            </a>
                                                        @else
                                                            <span style="color: #6c757d;">Data Tidak Ada</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Catatan Berkas</th>
                                                    <td class="text-wrap w-75">
                                                        {{ $selectedRancangan->catatan_berkas ?? 'Tidak Ada Catatan' }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Informasi Revisi --}}
                        <div class="col-md-6 mb-2">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="mb-0 ">Revisi/Hasil Penelitian</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive modal-table">
                                        <table class="table table-sm table-borderless">
                                            <tbody>
                                                <tr>
                                                    <th class="info-text w-25">Status Revisi</th>
                                                    <td class="text-wrap w-75">
                                                        <mark
                                                            class="badge-{{ $revisi->status_revisi === 'Direvisi'
                                                                ? 'success'
                                                                : ($revisi->status_revisi === 'Menunggu Peneliti'
                                                                    ? 'info text-default'
                                                                    : ($revisi->status_revisi === 'Proses Revisi'
                                                                        ? 'warning'
                                                                        : ($revisi->status_revisi === 'Belum Tahap Revisi'
                                                                            ? 'danger'
                                                                            : 'secondary'))) }} badge-pill">
                                                            {{ $revisi->status_revisi }}
                                                        </mark>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Status Validasi Revisi</th>
                                                    <td class="text-wrap w-75">
                                                        <mark
                                                            class="badge-{{ $revisi->status_validasi === 'Diterima'
                                                                ? 'success'
                                                                : ($revisi->status_validasi === 'Ditolak'
                                                                    ? 'danger'
                                                                    : ($revisi->status_validasi === 'Belum Tahap Validasi'
                                                                        ? 'danger'
                                                                        : 'warning')) }} badge-pill">
                                                            {{ $revisi->status_validasi }}
                                                        </mark>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Tanggal Revisi</th>
                                                    <td class="text-wrap w-75">
                                                        {{ $revisi->tanggal_revisi
                                                            ? \Carbon\Carbon::parse($revisi->tanggal_revisi)->translatedFormat('d F Y, H:i')
                                                            : 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Tanggal Validasi</th>
                                                    <td class="text-wrap w-75">
                                                        {{ $revisi->tanggal_validasi
                                                            ? \Carbon\Carbon::parse($revisi->tanggal_validasi)->translatedFormat('d F Y, H:i')
                                                            : 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Peneliti</th>
                                                    <td class="text-wrap w-75">
                                                        {{ $revisi->peneliti->nama_user ?? 'Belum Ditentukan' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Tanggal Peneliti Ditunjuk</th>
                                                    <td class="text-wrap w-75">
                                                        {{ $revisi->tanggal_peneliti_ditunjuk
                                                            ? \Carbon\Carbon::parse($revisi->tanggal_peneliti_ditunjuk)->translatedFormat('d F Y H:i')
                                                            : 'N/A' }}
                                                    </td>
                                                </tr>
                                                @if (!auth()->user()->hasRole('Perangkat Daerah'))
                                                    <tr>
                                                        <th class="info-text w-25">Revisi Rancangan</th>
                                                        <td class="text-wrap w-75">
                                                            @if (isset($revisi->revisi_rancangan))
                                                                <a href="{{ url('/view-private/revisi/rancangan/' . basename($revisi->revisi_rancangan)) }}"
                                                                    target="_blank" class="d-flex align-items-center">
                                                                    <i class="bi bi-file-earmark-pdf mr-2"
                                                                        style="font-size: 1.5rem; color: #007bff;"></i>
                                                                    <span>Lihat Revisi Rancangan</span>
                                                                </a>
                                                            @else
                                                                <span style="color: #6c757d;">Data Tidak Ada</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Revisi Matrik</th>
                                                        <td class="text-wrap w-75">
                                                            @if (isset($revisi->revisi_matrik))
                                                                <a href="{{ url('/view-private/revisi/matrik/' . basename($revisi->revisi_matrik)) }}"
                                                                    target="_blank" class="d-flex align-items-center">
                                                                    <i class="bi bi-file-earmark-pdf mr-2"
                                                                        style="font-size: 1.5rem; color: #28a745;"></i>
                                                                    <span>Lihat Matrik Revisi</span>
                                                                </a>
                                                            @else
                                                                <span style="color: #6c757d;">Data Tidak Ada</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endif
                                                <tr>
                                                    <th class="info-text w-25">Catatan Revisi</th>
                                                    <td class="text-wrap w-75">
                                                        {{ $revisi->catatan_revisi ?? 'Tidak Ada Catatan' }}
                                                    </td>
                                                </tr>
                                            </tbody>

                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-end mt-4">
                                        {{-- Tombol Tutup --}}
                                        <button type="button" class="btn btn-outline-warning mr-3"
                                            data-dismiss="modal" wire:click="resetDetail"></i> tutup
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="card">
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
        window.addEventListener('open-modal', event => {
            $('#' + event.detail.modalId).modal('show');
        });
        window.addEventListener('swal:error', function(event) {

            const data = event.detail[0];

            // Tampilkan SweetAlert
            Swal.fire({
                icon: data.type,
                title: data.title,
                text: data.message,
                showConfirmButton: true,
            });

        });
    </script>
    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch('deleteRancangan', {
                        id: id
                    }); // Kirim event ke Livewire hanya jika dikonfirmasi
                }
            });
        }
    </script>

</div>
