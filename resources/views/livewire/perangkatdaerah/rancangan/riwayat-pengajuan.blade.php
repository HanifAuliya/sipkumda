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
            <div class="card p-3 shadow-sm border mb-3 bg-light">
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
                            <p class="info-text small mb-0">
                                <i class="bi bi-calendar"></i> Tanggal Berkas Disetujui:
                                {{ $item->tanggal_berkas_disetujui
                                    ? \Carbon\Carbon::parse($item->tanggal_berkas_disetujui)->translatedFormat('d F Y')
                                    : 'N/A' }}
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
                            <p class="mb-0 info-text small">
                                <i class="bi bi-file-earmark-text"></i>
                                Status Revisi:
                                <mark
                                    class="badge-{{ $item->revisi->first()->status_revisi === 'Direvisi'
                                        ? 'success'
                                        : ($item->revisi->first()->status_revisi === 'Menunggu Peneliti'
                                            ? 'info text-default'
                                            : ($item->revisi->first()->status_revisi === 'Menunggu Revisi'
                                                ? 'warning'
                                                : ($item->revisi->first()->status_revisi === 'Menunggu Validasi'
                                                    ? 'dark text-white'
                                                    : ($item->revisi->first()->status_revisi === 'Belum Tahap Revisi'
                                                        ? 'danger'
                                                        : 'secondary')))) }} badge-pill">
                                    {{ $item->revisi->first()->status_revisi }}
                                </mark>
                            </p>
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
                        <button class="btn btn-secondary mt-3" data-toggle="modal"
                            data-target="#detailModalRiwayat-{{ $item->id_rancangan }}">
                            Lihat Detail
                        </button>
                    </div>
                </div>
            </div>

            {{-- Modal Detail Riwayat --}}
            <div wire:ignore.self class="modal fade" id="detailModalRiwayat-{{ $item->id_rancangan }}" tabindex="-1"
                role="dialog" aria-labelledby="detailModalLabelRiwayat" aria-hidden="true">
                <div class="modal-dialog  no-style-modal modal-xl" role="document">
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
                                                            <a href="{{ asset('storage/' . $item->nota_dinas_pd) }}"
                                                                target="_blank" class="d-flex align-items-center">
                                                                <i class="bi bi-file-earmark-text mr-2"
                                                                    style="font-size: 1.5rem; color: #ffc107;"></i>
                                                                <span>Download Nota</span>
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
                                                            <a href="{{ asset('storage/' . $item->rancangan) }}"
                                                                target="_blank" class="d-flex align-items-center">
                                                                <i class="bi bi-file-earmark-text mr-2"
                                                                    style="font-size: 1.5rem; color: #007bff;"></i>
                                                                <span>Download Rancangan</span>
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
                                                            <a href="{{ asset('storage/' . $item->matrik) }}"
                                                                target="_blank" class="d-flex align-items-center">
                                                                <i class="bi bi-file-earmark-spreadsheet mr-2"
                                                                    style="font-size: 1.5rem; color: #28a745;"></i>
                                                                <span>Download Matrik</span>
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
                                                            <a href="{{ asset('storage/' . $item->bahan_pendukung) }}"
                                                                target="_blank" class="d-flex align-items-center">
                                                                <i class="bi bi-file-earmark-pdf mr-2"
                                                                    style="font-size: 1.5rem; color: #dc3545;"></i>
                                                                <span>Download Bahan</span>
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
                                                                            : ($revisi->status_revisi === 'Menunggu Revisi'
                                                                                ? 'warning'
                                                                                : ($revisi->status_revisi === 'Menunggu Validasi'
                                                                                    ? 'dark text-white'
                                                                                    : ($revisi->status_revisi === 'Belum Tahap Revisi'
                                                                                        ? 'danger'
                                                                                        : 'secondary')))) }} badge-pill">
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
                                                                                ? 'warning'
                                                                                : 'secondary')) }} badge-pill">
                                                                    {{ $revisi->status_validasi }}
                                                                </mark>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>Tanggal Revisi</th>
                                                            <td class="wrap-text-td-70">
                                                                {{ $revisi->tanggal_revisi ? \Carbon\Carbon::parse($revisi->tanggal_revisi)->translatedFormat('d F Y') : 'N/A' }}
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
                                                                    <a href="{{ asset('storage/' . $revisi->revisi_rancangan) }}"
                                                                        target="_blank"
                                                                        class="d-flex align-items-center">
                                                                        <i class="bi bi-file-earmark-text mr-2"
                                                                            style="font-size: 1.5rem; color: #007bff;"></i>
                                                                        <span>Download Revisi</span>
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
                                                                    <a href="{{ asset('storage/' . $revisi->revisi_matrik) }}"
                                                                        target="_blank"
                                                                        class="d-flex align-items-center">
                                                                        <i class="bi bi-file-earmark-spreadsheet mr-2"
                                                                            style="font-size: 1.5rem; color: #28a745;"></i>
                                                                        <span>Download Matrik Revisi</span>
                                                                    </a>
                                                                @else
                                                                    <span style="color: #6c757d;">Data Tidak Ada</span>
                                                                @endif
                                                            </td>
                                                        </tr>


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
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                        </div>
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


</div>
