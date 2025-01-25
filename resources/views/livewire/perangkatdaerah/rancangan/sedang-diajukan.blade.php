<div>
    {{-- Pencarian dan Pilihan PerPage --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        {{-- Pencarian --}}
        <div class="input-group w-50">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
            </div>
            <input type="text" class="form-control" placeholder="Cari berdasarkan No Rancangan atau Tentang"
                wire:model.live="search">
        </div>
        {{-- Per Page Dropdown --}}
        <div class="col-md-3">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="ni ni-bullet-list-67"></i></span>
                </div>
                <select class="form-control" wire:model.live="perPage">
                    <option value="3">3 Data per Halaman</option>
                    <option value="5">5 Data per Halaman</option>
                    <option value="10">10 Data per Halaman</option>
                    <option value="50">50 Data per Halaman</option>
                </select>
            </div>
        </div>
    </div>


    {{-- Daftar Rnacnagan --}}
    <div id="sedangDiajukanContent">
        @forelse ($rancangan as $item)
            {{-- Card Tab Sedang Diajukan --}}
            <div class="card p-3 shadow-sm border mb-3">
                <div class="row">
                    {{-- Bagian Kiri --}}
                    <div class="col-md-8">
                        <div class="d-flex align-items-start">
                            <div>
                                <div class="d-flex align-items-center">
                                    <h4 class="mb-1 font-weight-bold">
                                        {{ $item->no_rancangan }}
                                    </h4>
                                    <h5 class="ml-2">
                                        <mark
                                            class="badge-{{ $item->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                            {{ $item->jenis_rancangan }}
                                        </mark>
                                    </h5>
                                </div>

                                <p class="mb-1 mt-2 font-weight-bold">
                                    {{ $item->tentang }}
                                </p>
                                <p class="mb-0 info-text small">
                                    <i class="bi bi-houses"></i>
                                    {{ $item->user->perangkatDaerah->nama_perangkat_daerah ?? '-' }}
                                </p>
                                <p class="mb-0 info-text small">
                                    <i class="bi bi-calendar"></i>
                                    Tanggal Pengajuan:
                                    {{ \Carbon\Carbon::parse($item->tanggal_pengajuan)->translatedFormat('d F Y, H:i') }}
                                </p>
                                <p class="mb-0 info-text small">
                                    <i class="bi bi-person-gear"></i>
                                    <span
                                        class="{{ $item->revisi->last()->peneliti->nama_user ?? false ? 'info-text' : 'text-danger' }} text-primary">
                                        {{ $item->revisi->last()->peneliti->nama_user ?? 'Belum Ditentukan' }}
                                    </span>
                                    <span class="badge badge-secondary">
                                        Peneliti
                                    </span>
                                </p>
                                <p class="mb-1 info-text small">
                                    <i class="bi bi-file-check"></i>
                                    Persetujuan Berkas:
                                    <mark
                                        class="badge-{{ $item->status_berkas === 'Disetujui' ? 'success' : ($item->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                        {{ $item->status_berkas }}
                                    </mark>
                                </p>
                                <p class="mb-0 info-text small">
                                    <i class="bi bi-file-earmark-text"></i>
                                    Status Revisi:
                                    <mark
                                        class="badge-{{ $item->revisi->last()->status_revisi === 'Direvisi'
                                            ? 'success'
                                            : ($item->revisi->last()->status_revisi === 'Menunggu Peneliti'
                                                ? 'info text-default'
                                                : ($item->revisi->last()->status_revisi === 'Proses Revisi'
                                                    ? 'warning'
                                                    : ($item->revisi->last()->status_revisi === 'Belum Tahap Revisi'
                                                        ? 'danger'
                                                        : 'secondary'))) }} badge-pill">
                                        {{ $item->revisi->last()->status_revisi }}
                                    </mark>
                                </p>
                                <p>
                                    {{-- Progress Bar --}}
                                <div class="progress-wrapper mt--4">
                                    <div class="progress-info d-flex justify-content-between">
                                        <div class="progress-label">
                                            <span>Progress Pengajuan Rancangan</span>
                                        </div>
                                        <div class="progress-percentage">
                                            <span>{{ $item->progress ?? 0 }}%</span>
                                        </div>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar {{ $item->progress == 100 ? 'bg-success' : ($item->progress >= 50 ? 'bg-warning' : 'bg-danger') }} progress-bar-striped"
                                            role="progressbar" aria-valuenow="{{ $item->progress ?? 0 }}"
                                            aria-valuemin="0" aria-valuemax="100"
                                            style="width: {{ $item->progress ?? 0 }}%;">
                                        </div>
                                    </div>
                                </div>

                                </p>
                                @if ($item->status_berkas === 'Ditolak')
                                    <p class="mt-2">
                                    <div class="alert alert-warning mb-0" role="alert">
                                        <strong>{{ $item->status_berkas }} !</strong> Rancangan
                                        Telah
                                        {{ $item->status_berkas }} Silahkan lakukan ke kelola rancangan >> Upload Ulang
                                        Berkas
                                    </div>
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Bagian Kanan --}}
                    <div class="col-md-4 col-12 d-flex flex-column justify-content-center text-right">
                        {{-- Status Rancangan --}}
                        <h4>
                            <mark
                                class="badge-{{ $item->status_rancangan === 'Disetujui' ? 'success' : ($item->status_rancangan === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                @if ($item->status_rancangan === 'Disetujui')
                                    <i class="bi bi-check-circle"></i>
                                @elseif ($item->status_rancangan === 'Ditolak')
                                    <i class="bi bi-x-circle-fill"></i>
                                @else
                                    <i class="bi bi-gear-fill"></i>
                                @endif
                                Rancangan {{ $item->status_rancangan }}
                            </mark>
                        </h4>

                        <p class="info-text mb-1 small">
                            Pengajuan Rancangan Tahun
                            {{ \Carbon\Carbon::parse($item->tanggal_pengajuan)->year }}
                        </p>

                        <div class="mt-2">
                            <div class="dropdown">
                                <button class="btn btn-neutral dropdown-toggle" type="button" id="dropdownMenuButton"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="bi bi-gear"></i> Kelola Rancangan
                                </button>
                                <div class="dropdown-menu shadow-lg" aria-labelledby="dropdownMenuButton">
                                    {{-- Item 1 --}}
                                    <a href="#" class="dropdown-item d-flex align-items-center"
                                        data-toggle="modal"
                                        data-target="#detailModalPengajuan-{{ $item->id_rancangan }}">
                                        <i class="bi bi-folder mr-2 text-warning"></i>
                                        <span>Detail Pengajuan</span>
                                    </a>
                                    {{-- Upload Ulang Berkas Ditolak --}}
                                    @if ($item->status_berkas === 'Ditolak')
                                        <a href="#" class="dropdown-item d-flex align-items-center"
                                            wire:click.prevent="openUploadUlangModal({{ $item->id_rancangan }})">
                                            <i class="bi bi-upload text-success"></i>
                                            <span>Upload Ulang Berkas Ditolak</span>
                                        </a>
                                    @endif
                                    <a href="#" class="dropdown-item d-flex align-items-center"
                                        data-toggle="modal"
                                        data-target="#detailModalPengajuan-{{ $item->id_rancangan }}">
                                        <i class="bi bi-filetype-pdf text-capitalize"></i>
                                        <span>Berkas Revisi dan Nota Dinas</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Modal Detail --}}
            <div wire:ignore.self class="modal fade" id="detailModalPengajuan-{{ $item->id_rancangan }}" tabindex="-1"
                role="dialog" aria-labelledby="detailModalLabelPengajuan" aria-hidden="true" data-backdrop="static"
                data-keyboard="false">
                <div class="modal-dialog  modal-xl no-style-modal" role="document">
                    <div class="modal-content">
                        {{-- Body Modal --}}
                        <div class="modal-body">
                            {{-- Header --}}
                            @if ($item)
                                <div class="card mb-3">
                                    <div class="modal-header">
                                        {{-- Teks Detail Rancangan --}}
                                        <h5 class="modal-title mb-0" id="detailModalLabel">
                                            Detail Rancangan: {{ $item->no_rancangan ?? 'N/A' }}
                                        </h5>

                                        {{-- Tombol --}}
                                        <button type="button" class="close" data-dismiss="modal"
                                            aria-label="Close">
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
                                                        {{ $item->no_rancangan ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Jenis</th>
                                                    <td class="wrap-text-td-70 ">
                                                        @if ($item && $item->jenis_rancangan)
                                                            <mark
                                                                class="badge-{{ $item->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                                                {{ $item->jenis_rancangan }}
                                                            </mark>
                                                        @else
                                                            <span class="text-danger">Data tidak tersedia</span>
                                                        @endif
                                                </tr>
                                                <tr>
                                                    <th>Tentang</th>
                                                    <td class="wrap-text-td-70 ">
                                                        {{ $item->tentang ?? 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Tanggal Pengajuan</th>
                                                    <td>
                                                        {{ $item->tanggal_pengajuan
                                                            ? \Carbon\Carbon::parse($item->tanggal_pengajuan)->translatedFormat('d F Y, H:i')
                                                            : 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>User Pengaju</th>
                                                    <td class="wrap-text-td-70 ">
                                                        {{ $item->user->nama_user ?? 'N/A' }}
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <th>Perangkat Daerah</th>
                                                    <td class="wrap-text-td-70 ">
                                                        {{ $item->user->perangkatDaerah->nama_perangkat_daerah ?? 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Status Rancangan</th>
                                                    @if ($item && $item->status_rancangan)
                                                        <td class="wrap-text-td-70 ">
                                                            <mark
                                                                class="badge-{{ $item->status_rancangan === 'Disetujui'
                                                                    ? 'success'
                                                                    : ($item->status_rancangan === 'Ditolak'
                                                                        ? 'danger'
                                                                        : 'warning') }} badge-pill">
                                                                {{ $item->status_rancangan }}
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
                                                        {{ $item->tanggal_rancangan_disetujui
                                                            ? \Carbon\Carbon::parse($item->tanggal_rancangan_disetujui)->translatedFormat('d F Y, H:i')
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
                                                    @if ($item && $item->status_berkas)
                                                        <td class="wrap-text-td-70 ">
                                                            <mark
                                                                class="badge-{{ $item->status_berkas === 'Disetujui' ? 'success' : ($item->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                                {{ $item->status_berkas }}
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
                                                        {{ $item->tanggal_berkas_disetujui
                                                            ? \Carbon\Carbon::parse($item->tanggal_berkas_disetujui)->translatedFormat('d F Y, H:i')
                                                            : 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Nota Dinas</th>
                                                    <td class="wrap-text-td-70">
                                                        @if (isset($item->nota_dinas_pd))
                                                            <a href="{{ url('/view-private/rancangan/nota_dinas/' . basename($item->nota_dinas_pd)) }}"
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
                                                        @if (isset($item->rancangan))
                                                            <a href="{{ url('/view-private/rancangan/rancangan/' . basename($item->rancangan)) }}"
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
                                                        @if (isset($item->matrik))
                                                            <a href="{{ url('/view-private/rancangan/matrik/' . basename($item->matrik)) }}"
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
                                                        @if (isset($item->bahan_pendukung))
                                                            <a href="{{ url('/view-private/rancangan/bahan_pendukung/' . basename($item->bahan_pendukung)) }}"
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
                                                        {{ $item->catatan_berkas ?? 'Tidak Ada Catatan' }}
                                                    </td>
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
                                        @if ($item && $item->revisi->isNotEmpty())
                                            @foreach ($item->revisi as $revisi)
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
                                                            <th>Catatan Revisi</th>
                                                            <td class="wrap-text-td-70 ">
                                                                {{ $revisi->catatan_revisi ?? 'Tidak Ada Catatan' }}
                                                            </td>
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
                            {{-- Tutup Modal --}}
                            <div class="card mt-4">
                                <button type="button" class="btn btn-neutral" data-dismiss="modal">Tutup Detail
                                    Rancangan</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        @empty
            <p class="text-center info-text">Tidak ada data rancangan sedang diajukan.</p>
        @endforelse

        {{-- pagination --}}
        <div class="d-flex justify-content-center w-100 w-md-auto">
            {{ $rancangan->links('pagination::bootstrap-4') }}
        </div>
    </div>

    {{-- Modal Upload Ulang Berkas Ditolak --}}
    <div wire:ignore.self class="modal fade" id="uploadUlangBerkasModal" tabindex="-1" role="dialog"
        aria-labelledby="uploadUlangBerkasModalLabel" aria-hidden="true" data-backdrop="static"
        data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadUlangBerkasModalLabel">Upload Ulang Berkas Ditolak</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        wire:click="resetForm">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form wire:submit.prevent="uploadUlangBerkas">
                    {{-- Modal Body --}}
                    <div class="modal-body">
                        {{-- Input File Nota Dinas --}}
                        <div class="mb-4">
                            <label for="fileNotaDinas" class="font-weight-bold form-control-label">
                                <i class="bi bi-file-earmark-pdf text-primary"></i> File Nota Dinas
                                <small class="text-muted d-block">Unggah dokumen nota dinas dalam format PDF (max:
                                    2MB).</small>
                            </label>
                            <input type="file" id="fileNotaDinas" class="form-control" wire:model="fileNotaDinas"
                                accept="application/pdf" />
                            <div wire:loading wire:target="fileNotaDinas" class="text-info mt-2">
                                <i class="spinner-border spinner-border-sm"></i> Mengunggah Nota Dinas...
                            </div>
                            @error('fileNotaDinas')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        {{-- Input File Rancangan --}}
                        <div class="mb-4">
                            <label for="fileRancangan" class="font-weight-bold form-control-label">
                                <i class="bi bi-file-earmark-pdf text-primary"></i> File Rancangan
                                <small class="text-muted d-block">Unggah dokumen rancangan dalam format PDF (max:
                                    2MB).</small>
                            </label>
                            <input type="file" id="fileRancangan" class="form-control" wire:model="fileRancangan"
                                accept="application/pdf" />
                            <div wire:loading wire:target="fileRancangan" class="text-info mt-2">
                                <i class="spinner-border spinner-border-sm"></i> Mengunggah File Rancangan...
                            </div>
                            @error('fileRancangan')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Input File Matrik --}}
                        <div class="mb-4">
                            <label for="fileMatrik" class="font-weight-bold form-control-label">
                                <i class="bi bi-file-earmark-pdf text-primary"></i> File Matrik
                                <small class="text-muted d-block">Unggah matrik dokumen dalam format PDF (max:
                                    2MB).</small>
                            </label>
                            <input type="file" id="fileMatrik" class="form-control" wire:model="fileMatrik"
                                accept="application/pdf" />
                            <div wire:loading wire:target="fileMatrik" class="text-info mt-2">
                                <i class="spinner-border spinner-border-sm"></i> Mengunggah File Matrik...
                            </div>
                            @error('fileMatrik')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Input File Bahan Pendukung --}}
                        <div class="mb-4">
                            <label for="fileBahanPendukung" class="font-weight-bold form-control-label">
                                <i class="bi bi-file-earmark-pdf text-primary"></i> File Bahan Pendukung (Opsional)
                                <small class="text-muted d-block">Unggah dokumen bahan pendukung dalam format PDF (max:
                                    2MB).</small>
                            </label>
                            <input type="file" id="fileBahanPendukung" class="form-control"
                                wire:model="fileBahanPendukung" accept="application/pdf" />
                            <div wire:loading wire:target="fileBahanPendukung" class="text-info mt-2">
                                <i class="spinner-border spinner-border-sm"></i> Mengunggah Bahan Pendukung...
                            </div>
                            @error('fileBahanPendukung')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="hapusBahanPendukung"
                                wire:model="hapusBahanPendukung">
                            <label class="form-check-label" for="hapusBahanPendukung">
                                Hapus Bahan Pendukung Lama <small class="text-danger"> (CENTANG INI APABILA ANDA
                                    SEBLUMNYA SUDAH MEMPUNYAI BAHAN PENDUKUNG DAN AKAN MENGUPLOAD FIE TANPA BAHAN
                                    PENDUKUNG !)</small>
                            </label>
                        </div>
                    </div>
                    {{-- Modal Footer --}}
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-warning" data-dismiss="modal"
                            wire:click="resetForm">
                            <i class="bi bi-x"></i>
                            Batal
                        </button>
                        <button type="button" class="btn btn-otuline-default" wire:click="uploadUlangBerkas"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="uploadUlangBerkas">
                                <i class="bi bi-upload"></i> Upload Ulang
                            </span>
                            <span wire:loading wire:target="uploadUlangBerkas">
                                <i class="spinner-border spinner-border-sm"></i> Memproses...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
