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
                                {{ \Carbon\Carbon::parse($item->tanggal_pengajuan)->translatedFormat('d F Y') }}
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
                                    class="badge-{{ $item->revisi->first()->status_revisi === 'Direvisi' ? 'success' : ($item->revisi->first()->status_revisi === 'Menunggu Revisi' ? 'warning' : 'danger') }} badge-pill">
                                    {{ $item->revisi->first()->status_revisi ?? 'N/A' }}
                                </mark>
                            </p>
                        </div>
                    </div>

                    {{-- Bagian Kanan --}}
                    <div class="text-right">
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
                            Pengajuan Rancangan Tahun {{ now()->year }}
                        </p>
                        <div class="mt-2">
                            <div class="dropdown">
                                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Kelola Rancangan
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    {{-- Button untuk membuka modal --}}
                                    <a href="#" class="dropdown-item" data-toggle="modal"
                                        data-target="#detailModalPengajuan-{{ $item->id_rancangan }}">
                                        <i class="bi bi-folder"></i>
                                        Detail Pengajuan
                                    </a>
                                    <a href="#" class="dropdown-item" data-toggle="modal"
                                        data-target="#uploadRevisiModal-{{ $item->id_rancangan }}">
                                        <i class="bi bi-upload"></i>
                                        Upload Revisi
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal Detail --}}
            <div wire:ignore.self class="modal fade" id="detailModalPengajuan-{{ $item->id_rancangan }}" tabindex="-1"
                role="dialog" aria-labelledby="detailModalLabelPengajuan" aria-hidden="true">
                <div class="modal-dialog modal-xl no-style-modal" role="document">
                    <div class="modal-content">
                        {{-- Body Modal --}}
                        <div class="modal-body">
                            {{-- Header --}}
                            @if ($item)
                                <div class="card mb-3">
                                    <div class="modal-header">
                                        {{-- Teks Detail Rancangan --}}
                                        <h5 class="modal-title mb-0" id="detailModalLabelPengajuan">
                                            Detail Pengajuan: {{ $item->no_rancangan ?? 'N/A' }}
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
                                                    <td class="info-text">{{ $item->no_rancangan ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Jenis</th>
                                                    <td>
                                                        <mark
                                                            class="info-text badge-{{ $item->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                                            {{ $item->jenis_rancangan ?? 'N/A' }}
                                                        </mark>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Tentang</th>
                                                    <td class="info-text">{{ $item->tentang ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Tanggal Pengajuan</th>
                                                    <td class="info-text">
                                                        {{ $item->tanggal_pengajuan
                                                            ? \Carbon\Carbon::parse($item->tanggal_pengajuan)->translatedFormat('d F Y')
                                                            : 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>User Pengaju</th>
                                                    <td class="info-text">{{ $item->user->nama_user ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Perangkat Daerah</th>
                                                    <td class="info-text">
                                                        {{ $item->user->perangkatDaerah->nama_perangkat_daerah ?? 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Status Rancangan</th>
                                                    <td class="info-text">
                                                        <mark
                                                            class="badge-{{ $item->status_rancangan === 'Disetujui'
                                                                ? 'success'
                                                                : ($item->status_rancangan === 'Ditolak'
                                                                    ? 'danger'
                                                                    : 'warning') }} badge-pill">
                                                            {{ $item->status_rancangan ?? 'N/A' }}
                                                        </mark>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                {{-- File Pengajuan --}}
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5 class="mb-0 font-weight-bold">File Pengajuan</h5>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-borderless">
                                            <tbody>
                                                <tr>
                                                    <th>Status Berkas</th>
                                                    <td class="info-text">
                                                        <mark
                                                            class="badge-{{ $item->status_berkas === 'Disetujui' ? 'success' : ($item->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                            {{ $item->status_berkas ?? 'N/A' }}
                                                        </mark>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Tanggal Berkas Disetujui</th>
                                                    <td class="info-text">
                                                        {{ $item->tanggal_berkas_disetujui
                                                            ? \Carbon\Carbon::parse($item->tanggal_berkas_disetujui)->translatedFormat('d F Y')
                                                            : 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Nota Dinas <small>dari pd</small></th>
                                                    <td class="info-text">
                                                        <a href="{{ isset($item->nota_dinas_pd) ? asset('storage/' . $item->nota_dinas_pd) : '#' }}"
                                                            target="_blank" class="d-flex align-items-center">
                                                            <i class="bi bi-file-earmark-text mr-2"
                                                                style="font-size: 1.5rem; color: #ffc107;"></i>
                                                            <span>{{ isset($item->nota_dinas_pd) ? 'Download Nota' : 'Tidak Ada Nota' }}</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>File Rancangan</th>
                                                    <td class="info-text">
                                                        <a href="{{ isset($item->rancangan) ? asset('storage/' . $item->rancangan) : '#' }}"
                                                            target="_blank" class="d-flex align-items-center">
                                                            <i class="bi bi-file-earmark-text mr-2"
                                                                style="font-size: 1.5rem; color: #007bff;"></i>
                                                            <span>{{ isset($item->rancangan) ? 'Download Rancangan' : 'Tidak Ada Rancangan' }}</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Matrik</th>
                                                    <td class="info-text">
                                                        <a href="{{ isset($item->matrik) ? asset('storage/' . $item->matrik) : '#' }}"
                                                            target="_blank" class="d-flex align-items-center">
                                                            <i class="bi bi-file-earmark-spreadsheet mr-2"
                                                                style="font-size: 1.5rem; color: #28a745;"></i>
                                                            <span>{{ isset($item->matrik) ? 'Download Matrik' : 'Tidak Ada Matrik' }}</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Bahan Pendukung</th>
                                                    <td class="info-text">
                                                        <a href="{{ isset($item->bahan_pendukung) ? asset('storage/' . $item->bahan_pendukung) : '#' }}"
                                                            target="_blank" class="d-flex align-items-center">
                                                            <i class="bi bi-file-earmark-pdf mr-2"
                                                                style="font-size: 1.5rem; color: #dc3545;"></i>
                                                            <span>{{ isset($item->bahan_pendukung) ? 'Download Bahan' : 'Tidak Ada Bahan' }}</span>
                                                        </a>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <th>Catatan Berkas</th>
                                                    <td class="info-text">
                                                        {{ $item->catatan_berkas ?? 'Tidak Ada Catatan' }}</td>
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
                                        @if ($item->revisi->isNotEmpty())
                                            @foreach ($item->revisi as $revisi)
                                                <table class="table table-borderless mb-4">
                                                    <tbody>
                                                        <tr>
                                                            <th>Status Revisi</th>
                                                            <td class="info-text">
                                                                <mark
                                                                    class="badge-{{ $revisi->status_revisi === 'Direvisi' ? 'success' : ($revisi->status_revisi === 'Menunggu Revisi' ? 'warning' : 'danger') }} badge-pill">
                                                                    {{ $revisi->status_revisi }}
                                                                </mark>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>Tanggal Revisi</th>
                                                            <td class="info-text">
                                                                {{ $revisi->tanggal_revisi ? \Carbon\Carbon::parse($revisi->tanggal_revisi)->translatedFormat('d F Y') : 'N/A' }}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>Peneliti</th>
                                                            <td class="info-text">
                                                                {{ $revisi->peneliti->nama_user ?? 'Belum Ditentukan' }}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>Revisi Rancangan</th>
                                                            <td class="info-text">
                                                                <a href="{{ asset('storage/' . $revisi->revisi_rancangan) }}"
                                                                    target="_blank" class="d-flex align-items-center">
                                                                    <i class="bi bi-file-earmark-text mr-2"
                                                                        style="font-size: 1.5rem; color: #007bff;"></i>
                                                                    <span>Download Revisi</span>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>Revisi Matrik</th>
                                                            <td class="info-text">
                                                                <a href="{{ asset('storage/' . $revisi->revisi_matrik) }}"
                                                                    target="_blank" class="d-flex align-items-center">
                                                                    <i class="bi bi-file-earmark-spreadsheet mr-2"
                                                                        style="font-size: 1.5rem; color: #28a745;"></i>
                                                                    <span>Download Matrik Revisi</span>
                                                                </a>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <th>Catatan Revisi</th>
                                                            <td class="info-text">
                                                                {{ $revisi->catatan_revisi ?? 'Tidak Ada Catatan' }}
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            @endforeach
                                        @else
                                            <p class="text-center info-text">Belum ada revisi.</p>
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
                                        <p class="mt-3 ml-3 info-text">Sedang memuat data, harap tunggu...</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Footer Modal --}}
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
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
</div>
