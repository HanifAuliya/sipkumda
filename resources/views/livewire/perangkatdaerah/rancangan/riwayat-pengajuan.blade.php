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

    {{-- Data Riwayat Pengajuan --}}
    <div id="riwayatPengajuanContent">
        @forelse ($riwayat as $item)
            {{-- Card Riwayat --}}
            <div class="card p-3 shadow-sm border mb-3 ">
                <div class="d-flex justify-content-between align-items-center">
                    {{-- Bagian Kiri --}}
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
                                <i class="bi bi-person"></i>
                                {{ $item->user->nama_user ?? 'N/A' }}
                                <span class="badge badge-secondary">
                                    Pemohon
                                </span>
                            </p>
                            <p class="mb-0 info-text small">
                                <i class="bi bi-person-gear"></i>
                                <span
                                    class="{{ $item->revisi->first()->peneliti->nama_user ?? false ? 'info-text' : 'text-danger' }}">
                                    {{ $item->revisi->first()->peneliti->nama_user ?? 'Belum Ditentukan' }}
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
                            <p class="mb-0 info-text small mb-2">
                                <i class="bi bi-file-earmark-text"></i>
                                Status Revisi:
                                <mark
                                    class="badge-{{ $item->revisi->first()->status_revisi === 'Direvisi'
                                        ? 'success'
                                        : ($item->revisi->first()->status_revisi === 'Menunggu Peneliti'
                                            ? 'info text-default'
                                            : ($item->revisi->first()->status_revisi === 'Proses Revisi'
                                                ? 'warning'
                                                : ($item->revisi->first()->status_revisi === 'Belum Tahap Revisi'
                                                    ? 'danger'
                                                    : 'secondary'))) }} badge-pill">
                                    {{ $item->revisi->first()->status_revisi }}
                                </mark>
                            </p>

                            <div class="alert alert-default alert-dismissible fade show mb-2" role="alert">
                                <span class="alert-icon"><i class="bi bi-clipboard2-check"></i></span>
                                <span class="alert-text">
                                    <strong>Rancangan Diterima</strong>. Ajukan ke menu Fasilitasi <i
                                        class="bi bi-send-plus"></i> dengan catatan dan file dari revisi yang dikirm.
                                </span>

                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Bagian Kanan --}}
                    <div class="text-right">
                        {{-- Status --}}
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
                            Pengajuan Rancangan Tahun {{ \Carbon\Carbon::parse($item->tanggal_pengajuan)->year }}
                        </p>

                        <div class="dropdown">
                            <button class="btn btn-neutral dropdown-toggle" type="button" id="dropdownMenuButton"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="bi bi-gear"></i> Detail Rancangan
                            </button>
                            <div class="dropdown-menu shadow-lg" aria-labelledby="dropdownMenuButton">
                                {{-- Item 1 --}}
                                <a class="dropdown-item d-flex align-items-center" data-toggle="modal"
                                    data-target="#detailModalRiwayat-{{ $item->id_rancangan }}">
                                    <i class="bi bi-info-circle"></i>
                                    Lihat Detail
                                </a>
                                <a href="#" class="dropdown-item d-flex align-items-center"
                                    wire:click.prevent="loadDokumenRevisi({{ $item->id_rancangan }})">
                                    <i class="bi bi-file-earmark-text text-primary"></i>
                                    <span>Preview Dokumen Revisi</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal Detail Riwayat --}}
            <div wire:ignore.self class="modal fade" id="detailModalRiwayat-{{ $item->id_rancangan }}" tabindex="-1"
                role="dialog" aria-labelledby="detailModalLabelRiwayat" data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog  no-style-modal modal-xl" role="document">
                    <div class="modal-content">
                        {{-- Body Modal --}}
                        {{-- Header --}}
                        @if ($item)
                            <div class="row mt-3">
                                {{-- Informasi Utama --}}
                                <div class="col-md-6 mb-2">
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <h5 class="mb-0 ">Informasi Utama</h5>
                                        </div>
                                        <div class="card-body table-responsive modal-table mt--3">
                                            <table class="table table-sm table-borderless">
                                                <tbody>
                                                    <tr>
                                                        <th class="info-text w-25">Nomor</th>
                                                        <td class="text-wrap w-75">
                                                            {{ $item->no_rancangan ?? 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Jenis</th>
                                                        <td class="text-wrap w-75">
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
                                                        <td class="text-wrap w-75">
                                                            {{ $item->tentang ?? 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Tanggal Pengajuan</th>
                                                        <td class="text-wrap w-75">
                                                            {{ $item->tanggal_pengajuan
                                                                ? \Carbon\Carbon::parse($item->tanggal_pengajuan)->translatedFormat('d F Y, H:i')
                                                                : 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">User Pengaju</th>
                                                        <td class="text-wrap w-75">
                                                            {{ $item->user->nama_user ?? 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Perangkat Daerah</th>
                                                        <td class="text-wrap w-75">
                                                            {{ $item->user->perangkatDaerah->nama_perangkat_daerah ?? 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Nomor Nota</th>
                                                        <td class="text-wrap w-75">
                                                            {{ $item->nomor_nota ?? 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Tanggal Nota</th>
                                                        <td class="text-wrap w-75">
                                                            {{ $item->tanggal_nota ? \Carbon\Carbon::parse($item->tanggal_nota)->translatedFormat('d F Y') : 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Status Rancangan</th>
                                                        <td class="text-wrap w-75">
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
                                                        <td class="text-wrap w-75">
                                                            {{ $item->tanggal_rancangan_disetujui
                                                                ? \Carbon\Carbon::parse($item->tanggal_rancangan_disetujui)->translatedFormat('d F Y, H:i')
                                                                : 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Status Berkas</th>
                                                        @if ($item && $item->status_berkas)
                                                            <td class="text-wrap w-75">
                                                                <mark
                                                                    class="badge-{{ $item->status_berkas === 'Disetujui' ? 'success' : ($item->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                                    {{ $item->status_berkas }}
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
                                                            {{ $item->tanggal_berkas_disetujui
                                                                ? \Carbon\Carbon::parse($item->tanggal_berkas_disetujui)->translatedFormat('d F Y, H:i')
                                                                : 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Nota Dinas</th>
                                                        <td class="text-wrap w-75">
                                                            @if (isset($item->nota_dinas_pd))
                                                                <a href="{{ url('/view-private/rancangan/nota_dinas/' . basename($item->nota_dinas_pd)) }}"
                                                                    target="_blank" class="d-flex align-items-center">
                                                                    <i
                                                                        class="bi bi-file-earmark-pdf mr-2 text-warning"></i>
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
                                                            @if (isset($item->rancangan))
                                                                <a href="{{ url('/view-private/rancangan/rancangan/' . basename($item->rancangan)) }}"
                                                                    target="_blank" class="d-flex align-items-center">
                                                                    <i
                                                                        class="bi bi-file-earmark-word mr-2 text-primary"></i>
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
                                                            @if (isset($item->matrik))
                                                                <a href="{{ url('/view-private/rancangan/matrik/' . basename($item->matrik)) }}"
                                                                    target="_blank" class="d-flex align-items-center">
                                                                    <i
                                                                        class="bi bi-file-earmark-word mr-2 text-success"></i>
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
                                                            @if (isset($item->bahan_pendukung))
                                                                <a href="{{ url('/view-private/rancangan/bahan_pendukung/' . basename($item->bahan_pendukung)) }}"
                                                                    target="_blank" class="d-flex align-items-center">
                                                                    <i
                                                                        class="bi bi-file-earmark-pdf mr-2 text-danger"></i>
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
                                            <h5 class="mb-0 ">Informasi Hasil Penelitian</h5>
                                        </div>
                                        <div class="card-body table-responsive modal-table mt--3">
                                            @if ($item && $item->revisi->isNotEmpty())
                                                @foreach ($item->revisi as $revisi)
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
                                                                <th class="info-text w-25">Tanggal Peneliti Ditunjuk
                                                                </th>
                                                                <td class="text-wrap w-75">
                                                                    {{ $revisi->tanggal_peneliti_ditunjuk
                                                                        ? \Carbon\Carbon::parse($revisi->tanggal_peneliti_ditunjuk)->translatedFormat('d F Y H:i')
                                                                        : 'N/A' }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th class="info-text w-25">Revisi Matrik</th>
                                                                <td class="text-wrap w-75">
                                                                    @if (isset($revisi->revisi_matrik))
                                                                        <a href="{{ url('/view-private/revisi/matrik/' . basename($revisi->revisi_matrik)) }}"
                                                                            target="_blank"
                                                                            class="d-flex align-items-center">
                                                                            <i
                                                                                class="bi bi-file-earmark-word mr-2 text-success"></i>
                                                                            <span>Lihat Matrik Revisi</span>
                                                                        </a>
                                                                    @else
                                                                        <span style="color: #6c757d;">Data Tidak
                                                                            Ada</span>
                                                                    @endif
                                                                </td>
                                                            </tr>

                                                            <tr>
                                                                <th class="info-text w-25">Catatan Revisi</th>
                                                                <td class="text-wrap w-75">
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
                                                    data-dismiss="modal"></i> tutup
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
        @empty
            <p class="text-center text-muted">Tidak ada data riwayat pengajuan.</p>
        @endforelse

        {{-- Pagination --}}
        <div class="d-flex justify-content-center">
            {{ $riwayat->links('pagination::bootstrap-4') }}
        </div>
    </div>

    {{-- Modal untuk menampilkan daftar berkas --}}
    <div class="modal fade" id="berkasModal" tabindex="-1" role="dialog" aria-labelledby="berkasModalLabel"
        wire:ignore.self>
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="berkasModalLabel">Daftar Berkas Revisi</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        wire:click="resetModal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if ($selectedRancangan && $selectedRancangan->revisi)
                        <div class="card shadow">
                            <div class="list-group list-group-flush">

                                {{-- File Matrik --}}
                                @if ($selectedRancangan->matrik)
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="bi bi-file-earmark-word text-success"></i>
                                            <span>File Matrik</span>
                                        </div>
                                        <a href="{{ url('/view-private/revisi/matrik/' . basename($selectedRancangan->revisi->last()->revisi_matrik)) }}"
                                            target="_blank" class="btn btn-outline-success btn-sm">
                                            <i class="bi bi-eye"></i> Lihat
                                        </a>
                                    </div>
                                @endif
                                {{-- Catatan Revisi --}}
                                <div class="card shadow-sm">
                                    <div
                                        class="card-header text-dark d-flex justify-content-between align-items-center">
                                        <span><i class="bi bi-stickies"></i> Catatan Revisi</span>
                                        <div>
                                            <button class="btn btn-outline-success btn-sm"
                                                onclick="toggleFullscreen()">
                                                <i class="bi bi-arrows-fullscreen"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div id="zoomable-wrapper" style="position: relative;">
                                        <div class="card-body" id="zoomable-content"
                                            style="max-height: 200px; overflow-y: auto; font-size: 16px; background: #fff;">
                                            @if ($selectedRancangan->revisi->last()->catatan_revisi)
                                                <div class="alert alert-secondary" role="alert"
                                                    style="white-space: pre-line;">
                                                    <i class="bi bi-chat-dots"></i>
                                                    {{ $selectedRancangan->revisi->last()->catatan_revisi }}
                                                </div>
                                            @else
                                                <div class="alert alert-secondary" role="alert">
                                                    <i class="bi bi-info-circle"></i> Tidak ada catatan revisi
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    @else
                        <p class="text-danger">Data dokumen tidak tersedia.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"
                        wire:click="resetDetail">Tutup</button>
                </div>
            </div>
        </div>
    </div>


</div>
