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
                <div class="card-header">
                    <h3 class="mb-0">Tabel Rancangan Produk Hukum</h3>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        {{-- Search Bar --}}
                        <div class="col-md-6">
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
                                <select class="form-control" wire:model.live="jenisFilter">
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
                                        <td class="wrap-text-td-50">{{ $rancangan->tentang }}</td>
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
                                                    <i class="bi bi-eye-fill"></i>
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
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="mb-2 mb-md-0">
                            Menampilkan {{ $rancanganProdukHukum->firstItem() }} hingga
                            {{ $rancanganProdukHukum->lastItem() }} dari
                            {{ $rancanganProdukHukum->total() }}
                            data
                        </div>
                        <div class="d-flex justify-content-center w-100 w-md-auto">
                            {{ $rancanganProdukHukum->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div wire:ignore.self class="modal fade" id="detailModal" tabindex="-1" role="dialog"
        aria-labelledby="detailModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-xl no-style-modal" role="document">
            <div class="modal-content ">

                {{-- Body Modal untuk header --}}
                <div class="modal-body">
                    {{-- Header --}}
                    @if ($selectedRancangan)
                        <div class="card mb-3">
                            <div class="modal-header">
                                {{-- Teks Detail Rancangan --}}
                                <h5 class="modal-title mb-0" id="detailModalLabel">
                                    Detail Rancangan: {{ $selectedRancangan->no_rancangan ?? 'N/A' }}
                                </h5>

                                {{-- Tombol --}}
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>

                        </div>
                        {{-- Informasi Utama --}}
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0 font-weight-bold">Informasi Utama</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tbody>
                                        <tr>
                                            <th>Nomor</th>
                                            <td class="wrap-text-td-70">
                                                {{ $selectedRancangan->no_rancangan ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Jenis</th>
                                            <td class="wrap-text-td-70 ">
                                                @if ($selectedRancangan && $selectedRancangan->jenis_rancangan)
                                                    <mark
                                                        class="badge-{{ $selectedRancangan->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                                        {{ $selectedRancangan->jenis_rancangan }}
                                                    </mark>
                                                @else
                                                    <span class="text-danger">Data tidak tersedia</span>
                                                @endif
                                        </tr>
                                        <tr>
                                            <th>Tentang</th>
                                            <td class="wrap-text-td-70 ">{{ $selectedRancangan->tentang ?? 'N/A' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal Pengajuan</th>
                                            <td>
                                                {{ $selectedRancangan->tanggal_pengajuan
                                                    ? \Carbon\Carbon::parse($selectedRancangan->tanggal_pengajuan)->translatedFormat('d F Y, H:i')
                                                    : 'N/A' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>User Pengaju</th>
                                            <td class="wrap-text-td-70 ">
                                                {{ $selectedRancangan->user->nama_user ?? 'N/A' }}
                                            </td>
                                        </tr>

                                        <tr>
                                            <th>Perangkat Daerah</th>
                                            <td class="wrap-text-td-70 ">
                                                {{ $selectedRancangan->user->perangkatDaerah->nama_perangkat_daerah ?? 'N/A' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Status Rancangan</th>
                                            @if ($selectedRancangan && $selectedRancangan->status_rancangan)
                                                <td class="wrap-text-td-70 ">
                                                    <mark
                                                        class="badge-{{ $selectedRancangan->status_rancangan === 'Disetujui'
                                                            ? 'success'
                                                            : ($selectedRancangan->status_rancangan === 'Ditolak'
                                                                ? 'danger'
                                                                : 'warning') }} badge-pill">
                                                        {{ $selectedRancangan->status_rancangan }}
                                                    </mark>
                                                </td>
                                            @else
                                                <td class="wrap-text-td-70 ">
                                                    <span class="badge badge-secondary">N/A</span>
                                                </td>
                                            @endif
                                        </tr>
                                        <tr>
                                            <th>Tanggal Rancangan disetujui</th>
                                            <td>
                                                {{ $selectedRancangan->tanggal_rancangan_disetujui
                                                    ? \Carbon\Carbon::parse($selectedRancangan->tanggal_rancangan_disetujui)->translatedFormat('d F Y, H:i')
                                                    : 'N/A' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- File Rancangan --}}
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0 font-weight-bold">File Rancangan</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tbody>
                                        <tr>
                                            <th>Status Berkas</th>
                                            @if ($selectedRancangan && $selectedRancangan->status_berkas)
                                                <td class="wrap-text-td-70 ">
                                                    <mark
                                                        class="badge-{{ $selectedRancangan->status_berkas === 'Disetujui' ? 'success' : ($selectedRancangan->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                        {{ $selectedRancangan->status_berkas }}
                                                    </mark>
                                                </td>
                                            @else
                                                <td class="wrap-text-td-70 ">
                                                    <span class="badge badge-secondary">N/A</span>
                                                </td>
                                            @endif
                                        </tr>
                                        <tr>
                                            <th>
                                                Tanggal Berkas Disetujui
                                            </th>
                                            <td>
                                                {{ $selectedRancangan->tanggal_berkas_disetujui
                                                    ? \Carbon\Carbon::parse($selectedRancangan->tanggal_berkas_disetujui)->translatedFormat('d F Y, H:i')
                                                    : 'N/A' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Nota Dinas</th>
                                            <td class="wrap-text-td-70">
                                                @if (isset($selectedRancangan->nota_dinas_pd))
                                                    <a href="{{ url('/view-private/rancangan/nota_dinas/' . basename($selectedRancangan->nota_dinas_pd)) }}"
                                                        target="_blank" class="d-flex align-items-center">
                                                        <i class="bi bi-file-earmark-pdf mr-2"
                                                            style="font-size: 1.5rem; color: #ffc107;"></i>
                                                        <span>lihat Nota</span>
                                                    </a>
                                                @else
                                                    <span style="color: #6c757d;">Data Tidak Ada</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>File Rancangan</th>
                                            <td class="wrap-text-td-70">
                                                @if (isset($selectedRancangan->rancangan))
                                                    <a href="{{ url('/view-private/rancangan/rancangan/' . basename($selectedRancangan->rancangan)) }}"
                                                        target="_blank" class="d-flex align-items-center">
                                                        <i class="bi bi-file-earmark-pdf mr-2"
                                                            style="font-size: 1.5rem; color: #007bff;"></i>
                                                        <span>lihat Rancangan</span>
                                                    </a>
                                                @else
                                                    <span style="color: #6c757d;">Data Tidak Ada</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Matrik</th>
                                            <td class="wrap-text-td-70">
                                                @if (isset($selectedRancangan->matrik))
                                                    <a href="{{ url('/view-private/rancangan/matrik/' . basename($selectedRancangan->matrik)) }}"
                                                        target="_blank" class="d-flex align-items-center">
                                                        <i class="bi bi-file-earmark-pdf mr-2"
                                                            style="font-size: 1.5rem; color: #28a745;"></i>
                                                        <span>lihat Matrik</span>
                                                    </a>
                                                @else
                                                    <span style="color: #6c757d;">Data Tidak Ada</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Bahan Pendukung</th>
                                            <td class="wrap-text-td-70">
                                                @if (isset($selectedRancangan->bahan_pendukung))
                                                    <a href="{{ url('/view-private/rancangan/bahan_pendukung/' . basename($selectedRancangan->bahan_pendukung)) }}"
                                                        target="_blank" class="d-flex align-items-center">
                                                        <i class="bi bi-file-earmark-pdf mr-2"
                                                            style="font-size: 1.5rem; color: #dc3545;"></i>
                                                        <span>lihat Bahan</span>
                                                    </a>
                                                @else
                                                    <span style="color: #6c757d;">Data Tidak Ada</span>
                                                @endif
                                            </td>
                                        </tr>


                                        <tr>
                                            <th>Catatan Berkas</th>
                                            <td class="wrap-text-td-70 ">
                                                {{ $selectedRancangan->catatan_berkas ?? 'Tidak Ada Catatan' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Revisi --}}
                        <div class="card mb-1">
                            <div class="card-header">
                                <h5 class="mb-0 font-weight-bold">Revisi</h5>
                            </div>
                            <div class="card-body">
                                @if ($selectedRancangan && $selectedRancangan->revisi->isNotEmpty())
                                    @foreach ($selectedRancangan->revisi as $revisi)
                                        <table class="table table-borderless mb-4">
                                            <tbody>
                                                <tr>
                                                    <th>Status Revisi</th>
                                                    <td class="wrap-text-td-70 ">
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
                                                    <th>Status Validasi Revisi</th>
                                                    <td class="wrap-text-td-70">
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
                                                    <th>Tanggal Revisi</th>
                                                    <td class="wrap-text-td-70">
                                                        {{ $revisi->tanggal_revisi ? \Carbon\Carbon::parse($revisi->tanggal_revisi)->translatedFormat('d F Y, H:i') : 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Tanggal Validasi</th>
                                                    <td class="wrap-text-td-70">
                                                        {{ $revisi->tanggal_validasi ? \Carbon\Carbon::parse($revisi->tanggal_validasi)->translatedFormat('d F Y, H:i') : 'N/A' }}
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <th>Peneliti</th>
                                                    <td class="wrap-text-td-70 ">
                                                        {{ $revisi->peneliti->nama_user ?? 'Belum Ditentukan' }}
                                                    </td>
                                                </tr>
                                                <th>
                                                    Tanggal Peneliti Ditunjuk
                                                </th>
                                                <td>
                                                    {{ $revisi->tanggal_peneliti_ditunjuk
                                                        ? \Carbon\Carbon::parse($revisi->tanggal_peneliti_ditunjuk)->translatedFormat('d F Y H:i')
                                                        : 'N/A' }}
                                                </td>
                                                <tr>
                                                    <th>Revisi Rancangan</th>
                                                    <td class="wrap-text-td-70">
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
                                                    <th>Revisi Matrik</th>
                                                    <td class="wrap-text-td-70">
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


                                                <tr>
                                                    <th>Catatan Revisi</th>
                                                    <td class="wrap-text-td-70 ">
                                                        {{ $revisi->catatan_revisi ?? 'Tidak Ada Catatan' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    @endforeach
                                @else
                                    <p class="text-center text-muted">Belum ada revisi.</p>
                                @endif
                            </div>
                        </div>
                    @else
                        {{-- Spinner Loading --}}
                        <div class="card mb-3">
                            <div class="d-flex justify-content-center align-items-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-3 ml-3 text-muted">Sedang memuat data, harap tunggu...</p>
                            </div>
                        </div>
                    @endif
                    <div class="card mt-4">
                        <button type="button" class="btn btn-neutral" data-dismiss="modal">Tutup Detail
                            Rancangan</button>
                    </div>
                </div>

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

</div>
