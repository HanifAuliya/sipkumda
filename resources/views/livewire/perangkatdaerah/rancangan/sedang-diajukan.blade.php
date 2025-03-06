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

    @forelse ($rancangan as $item)
        {{-- Daftar Rnacnagan --}}
        <div id="sedangDiajukanContent">

            {{-- Card Tab Sedang Diajukan --}}
            <div class="card p-3 shadow-sm border mb-3">
                <div class="row">
                    {{-- Bagian Kiri --}}
                    <div class="col-md-8 col-12 mb-3 d-flex flex-column">
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

                            <p class="mb-1 mt-2 font-weight-bold text-left">
                                {{ $item->tentang }}
                            </p>
                            <p class="mb-0 info-text small text-left">
                                <i class="bi bi-houses"></i>
                                {{ $item->user->perangkatDaerah->nama_perangkat_daerah ?? '-' }}
                            </p>
                            <p class="mb-0 info-text small text-left">
                                <i class="bi bi-calendar"></i>
                                Tanggal Pengajuan:
                                {{ \Carbon\Carbon::parse($item->tanggal_pengajuan)->translatedFormat('d F Y, H:i') }}
                            </p>
                            <p class="mb-0 info-text small text-left">
                                <i class="bi bi-person-gear"></i>
                                <span
                                    class="{{ $item->revisi->last()->peneliti->nama_user ?? false ? 'info-text' : 'text-danger' }} text-primary">
                                    {{ $item->revisi->last()->peneliti->nama_user ?? 'Belum Ditentukan' }}
                                </span>
                                <span class="badge badge-secondary">
                                    Peneliti
                                </span>
                            </p>
                            <p class="mb-1 info-text small text-left">
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
                            @if ($item->status_berkas === 'Menunggu Persetujuan')
                                <div class="alert alert-warning alert-dismissible fade show mb-2 mt-4" role="alert">
                                    <span class="alert-icon"><i class="bi bi-send"></i></span>
                                    <span class="alert-text">
                                        Rancangan <strong>{{ $item->status_berkas }}!</strong> Tunggu Admin memeriksa
                                        berkas diajukan!.
                                    </span>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            @if ($item->status_berkas === 'Ditolak')
                                <div class="alert alert-danger alert-dismissible fade show mb-2 mt-4" role="alert">
                                    <span class="alert-icon"><i class="bi bi-clipboard-x"></i></span>
                                    <span class="alert-text">
                                        <strong>{{ $item->status_berkas }}!</strong> Rancangan telah
                                        {{ $item->status_berkas }}. Ke Bagian kanan => ⚙️ kelola
                                        rancangan => Upload
                                        Ulang Berkas.
                                    </span>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            @if ($item->revisi->isNotEmpty() && $item->revisi->last()->status_revisi === 'Menunggu Peneliti')
                                <div class="alert alert-primary alert-dismissible fade show mb-2 mt-4" role="alert">
                                    <span class="alert-icon"><i class="bi bi-person-plus-fill"></i></span>
                                    <span class="alert-text">
                                        <strong>Info!</strong> Menunggu dipilihkan peneliti untuk rancangan ini.
                                    </span>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            @if ($item->revisi->isNotEmpty() && $item->revisi->last()->status_revisi === 'Proses Revisi')
                                <div class="alert alert-default alert-dismissible fade show mb-2 mt-4" role="alert">
                                    <span class="alert-icon"><i class="bi bi-pencil-square"></i></span>
                                    <span class="alert-text">
                                        <strong>Proses!</strong> Rancangan sedang dalam proses revisi.
                                    </span>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

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
                                        <i class="bi bi-folder text-warning"></i>
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Modal Detail --}}
            <div wire:ignore.self class="modal fade" id="detailModalPengajuan-{{ $item->id_rancangan }}"
                tabindex="-1" role="dialog" aria-labelledby="detailModalLabelPengajuan" data-backdrop="static"
                data-keyboard="false">
                <div class="modal-dialog  modal-xl no-style-modal" role="document">
                    <div class="modal-content ">
                        {{-- Body Modal untuk header --}}

                        {{-- Header --}}
                        @if ($item)
                            <div class="row mt-3">
                                {{-- Informasi Utama --}}
                                <div class="col-md-6 mb-2">
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <h5 class="mb-0 ">Informasi Utama</h5>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-sm table-borderless">
                                                <tbody>
                                                    <tr>
                                                        <th class="info-text w-25">Nomor</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $item->no_rancangan ?? 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Jenis</th>
                                                        <td class="wrap-text w-75">
                                                            @if ($item && $item->jenis_rancangan)
                                                                <mark
                                                                    class="badge-{{ $item->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                                                    {{ $item->jenis_rancangan }}
                                                                </mark>
                                                            @else
                                                                <span class="text-danger">Data tidak tersedia</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Tentang</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $item->tentang ?? 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Tanggal Pengajuan</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $item->tanggal_pengajuan
                                                                ? \Carbon\Carbon::parse($item->tanggal_pengajuan)->translatedFormat('d F Y, H:i')
                                                                : 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">User Pengaju</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $item->user->nama_user ?? 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Perangkat Daerah</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $item->user->perangkatDaerah->nama_perangkat_daerah ?? 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Nomor Nota</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $item->nomor_nota ?? 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Tanggal Nota</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $item->tanggal_nota ? \Carbon\Carbon::parse($item->tanggal_nota)->translatedFormat('d F Y') : 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Status Rancangan</th>
                                                        <td class="wrap-text w-75">
                                                            @if ($item && $item->status_rancangan)
                                                                <mark
                                                                    class="badge-{{ $item->status_rancangan === 'Disetujui'
                                                                        ? 'success'
                                                                        : ($item->status_rancangan === 'Ditolak'
                                                                            ? 'danger'
                                                                            : 'warning') }} badge-pill">
                                                                    {{ $item->status_rancangan }}
                                                                </mark>
                                                            @else
                                                                <span class="badge badge-secondary">N/A</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Tanggal Rancangan disetujui</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $item->tanggal_rancangan_disetujui
                                                                ? \Carbon\Carbon::parse($item->tanggal_rancangan_disetujui)->translatedFormat('d F Y, H:i')
                                                                : 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Status Berkas</th>
                                                        @if ($item && $item->status_berkas)
                                                            <td class="wrap-text w-75">
                                                                <mark
                                                                    class="badge-{{ $item->status_berkas === 'Disetujui' ? 'success' : ($item->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                                    {{ $item->status_berkas }}
                                                                </mark>
                                                            </td>
                                                        @else
                                                            <td class="wrap-text w-75">
                                                                <span class="badge badge-secondary">N/A</span>
                                                            </td>
                                                        @endif
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Tanggal Berkas Disetujui</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $item->tanggal_berkas_disetujui
                                                                ? \Carbon\Carbon::parse($item->tanggal_berkas_disetujui)->translatedFormat('d F Y, H:i')
                                                                : 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Nota Dinas</th>
                                                        <td class="wrap-text w-75">
                                                            @if (isset($item->nota_dinas_pd))
                                                                <a href="{{ url('/view-private/rancangan/nota_dinas/' . basename($item->nota_dinas_pd)) }}"
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
                                                        <td class="wrap-text w-75">
                                                            @if (isset($item->rancangan))
                                                                <a href="{{ url('/view-private/rancangan/rancangan/' . basename($item->rancangan)) }}"
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
                                                        <td class="wrap-text w-75">
                                                            @if (isset($item->matrik))
                                                                <a href="{{ url('/view-private/rancangan/matrik/' . basename($item->matrik)) }}"
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
                                                        <td class="wrap-text w-75">
                                                            @if (isset($item->bahan_pendukung))
                                                                <a href="{{ url('/view-private/rancangan/bahan_pendukung/' . basename($item->bahan_pendukung)) }}"
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
                                                        <td class="wrap-text w-75">
                                                            {{ $item->catatan_berkas ?? 'Tidak Ada Catatan' }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                {{-- Informasi Utama --}}
                                <div class="col-md-6 mb-2">
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <h5 class="mb-0 ">Informasi Utama</h5>
                                        </div>
                                        <div class="card-body">
                                            @if ($item && $item->revisi->isNotEmpty())
                                                @foreach ($item->revisi as $revisi)
                                                    <table class="table table-sm table-borderless">
                                                        <tbody>
                                                            <tr>
                                                                <th class="info-text w-25">Status Revisi</th>
                                                                <td class="wrap-text w-75">
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
                                                                <td class="wrap-text w-75">
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
                                                                <td class="wrap-text w-75">
                                                                    {{ $revisi->tanggal_revisi
                                                                        ? \Carbon\Carbon::parse($revisi->tanggal_revisi)->translatedFormat('d F Y, H:i')
                                                                        : 'N/A' }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-text w-25">Tanggal Validasi</th>
                                                                <td class="wrap-text w-75">
                                                                    {{ $revisi->tanggal_validasi
                                                                        ? \Carbon\Carbon::parse($revisi->tanggal_validasi)->translatedFormat('d F Y, H:i')
                                                                        : 'N/A' }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-text w-25">Peneliti</th>
                                                                <td class="wrap-text w-75">
                                                                    {{ $revisi->peneliti->nama_user ?? 'Belum Ditentukan' }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-text w-25">Tanggal Peneliti Ditunjuk
                                                                </th>
                                                                <td class="wrap-text w-75">
                                                                    {{ $revisi->tanggal_peneliti_ditunjuk
                                                                        ? \Carbon\Carbon::parse($revisi->tanggal_peneliti_ditunjuk)->translatedFormat('d F Y H:i')
                                                                        : 'N/A' }}
                                                                </td>
                                                            </tr>
                                                            @if (!auth()->user()->hasRole('Perangkat Daerah'))
                                                                <tr>
                                                                    <th class="info-text w-25">Revisi Rancangan</th>
                                                                    <td class="wrap-text w-75">
                                                                        @if (isset($revisi->revisi_rancangan))
                                                                            <a href="{{ url('/view-private/revisi/rancangan/' . basename($revisi->revisi_rancangan)) }}"
                                                                                target="_blank"
                                                                                class="d-flex align-items-center">
                                                                                <i class="bi bi-file-earmark-pdf mr-2"
                                                                                    style="font-size: 1.5rem; color: #007bff;"></i>
                                                                                <span>Lihat Revisi Rancangan</span>
                                                                            </a>
                                                                        @else
                                                                            <span style="color: #6c757d;">Data Tidak
                                                                                Ada</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th class="info-text w-25">Revisi Matrik</th>
                                                                    <td class="wrap-text w-75">
                                                                        @if (isset($revisi->revisi_matrik))
                                                                            <a href="{{ url('/view-private/revisi/matrik/' . basename($revisi->revisi_matrik)) }}"
                                                                                target="_blank"
                                                                                class="d-flex align-items-center">
                                                                                <i class="bi bi-file-earmark-pdf mr-2"
                                                                                    style="font-size: 1.5rem; color: #28a745;"></i>
                                                                                <span>Lihat Matrik Revisi</span>
                                                                            </a>
                                                                        @else
                                                                            <span style="color: #6c757d;">Data Tidak
                                                                                Ada</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endif
                                                            <tr>
                                                                <th class="info-text w-25">Catatan Revisi</th>
                                                                <td class="wrap-text w-75">
                                                                    {{ $revisi->catatan_revisi ?? 'Tidak Ada Catatan' }}
                                                                </td>
                                                            </tr>
                                                        </tbody>

                                                    </table>
                                                @endforeach
                                            @else
                                                <p class="text-center text-muted">Belum ada revisi.</p>
                                            @endif
                                            <div class="d-flex justify-content-end mt-4">
                                                {{-- Tombol Tutup --}}
                                                <button type="button" class="btn btn-outline-warning mr-3"
                                                    data-dismiss="modal" wire:click="resetData"></i> tutup
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
        </div>


    @empty
        <p class="text-center info-text">Tidak ada data rancangan sedang diajukan.</p>
    @endforelse

    {{-- pagination --}}
    <div class="d-flex justify-content-center w-100 w-md-auto">
        {{ $rancangan->links('pagination::bootstrap-4') }}
    </div>

    {{-- Modal Upload Ulang Berkas Ditolak --}}
    <div wire:ignore.self class="modal fade" id="uploadUlangBerkasModal" tabindex="-1" role="dialog"
        aria-labelledby="uploadUlangBerkasModalLabel" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadUlangBerkasModalLabel">Upload Ulang Berkas Ditolak</h5>
                </div>
                <form wire:submit.prevent="uploadUlangBerkas">
                    {{-- Modal Body --}}
                    <div class="modal-body">
                        <div class="row">
                            <!-- Input Tanggal Nota -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tanggalNota" class="form-label font-weight-bold">Tanggal Nota</label>
                                    <input type="date" id="tanggalNota" class="form-control"
                                        wire:model="tanggalNota" required />
                                    @error('tanggalNota')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Input Nomor Nota -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nomorNota" class="form-label font-weight-bold">Nomor Nota</label>
                                    <input type="text" id="nomorNota" class="form-control" wire:model="nomorNota"
                                        placeholder="Masukkan Nomor Nota" required />
                                    @error('nomorNota')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="nomorNota" class="form-control-label font-weight-bold text-warning">Perhatikan
                                Ini , Untuk matrik download file yang ada di Template (Bahan Penting)
                                <small class="text-muted d-block">File Rancangan dan Matrik berupa word selain itu
                                    PDF</small>
                            </label>
                        </div>

                        {{-- Input File (Nota Dinas, Rancangan, Matrik, Bahan Pendukung) --}}
                        @foreach (['fileNotaDinas', 'fileRancangan', 'fileMatrik', 'fileBahanPendukung'] as $fileField)
                            <div class="mb-4">
                                <label class="font-weight-bold form-control-label">
                                    <i class="bi bi-file-earmark text-primary"></i>
                                    {{ ucfirst(str_replace('_', ' ', $fileField)) }}
                                    <small class="text-muted d-block">Unggah dokumen dalam format PDF (max:
                                        5MB).</small>
                                </label>

                                <input type="file" class="form-control" wire:model="{{ $fileField }}"
                                    accept=".pdf, .doc, .docx" wire:change="resetError('{{ $fileField }}')"
                                    {{ $$fileField ? 'disabled' : '' }}
                                    style="{{ $$fileField ? 'background-color: #e9ecef; cursor: not-allowed; opacity: 0.6;' : '' }}">

                                {{-- Indikator Loading --}}
                                <div wire:loading wire:target="{{ $fileField }}" class="text-info mt-2">
                                    <i class="spinner-border spinner-border-sm"></i> Mengunggah...
                                </div>

                                {{-- Preview file & tombol hapus --}}
                                @if ($$fileField)
                                    <div class="mt-2 p-2 border rounded bg-light d-flex align-items-center">
                                        <i class="bi bi-file-earmark-pdf text-danger mr-2"></i>
                                        <span class="flex-grow-1">{{ $$fileField->getClientOriginalName() }}</span>
                                        <button type="button" class="btn btn-sm btn-outline-danger ml-2"
                                            wire:click="removeFile('{{ $fileField }}')">
                                            <i class="bi bi-trash"></i> Hapus File
                                        </button>
                                    </div>
                                @endif

                                @error($fileField)
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        @endforeach

                        {{-- Checkbox Hapus Bahan Pendukung Lama --}}
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="hapusBahanPendukung"
                                wire:model="hapusBahanPendukung">
                            <label class="form-check-label" for="hapusBahanPendukung">
                                Hapus Bahan Pendukung Lama <small class="info-text"> (Centang ini apabila anda tidak
                                    menggunakan bahan pendukung)</small>
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
                        <button type="submit" class="btn btn-outline-default" wire:loading.attr="disabled"
                            {{ empty($tanggalNota) || empty($nomorNota) || empty($fileNotaDinas) || empty($fileRancangan) ? 'disabled' : '' }}>
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
