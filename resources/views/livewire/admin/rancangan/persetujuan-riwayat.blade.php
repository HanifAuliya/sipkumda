<div> {{-- Daftar Revisi --}}
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0 text-success">Berkas Disetujui -
                <small class="text-default">Daftar Rancangan dengan status berkas disetujui oleh verifikator ! </small>
            </h3>
        </div>

        <div class="card-body">
            {{-- Searching dan PerPage --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                {{-- Searching --}}
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
            {{-- Daftar Rancangan --}}
            <div class="list-group">
                @forelse ($riwayatRancangan as $item)
                    <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center">
                        {{-- Informasi Utama --}}
                        <div class="d-flex flex-column">
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
                            <p class="mb-1 mt-2 font-weight-bold">{{ $item->tentang }}</p>
                            <p class="mb-0 info-text small">
                                <i class="bi bi-person"></i>
                                {{ $item->user->nama_user ?? 'N/A' }}
                                <span class="badge badge-secondary">
                                    Pemohon
                                </span>
                            </p>
                            <p class="mb-0 info-text small">
                                <i class="bi bi-houses"></i>
                                {{ $item->user->perangkatDaerah->nama_perangkat_daerah ?? '-' }}
                            </p>
                            <p class="info-text small mb-0">
                                <i class="bi bi-calendar"></i> Tanggal Pengajuan:
                                {{ \Carbon\Carbon::parse($item->tanggal_pengajuan)->translatedFormat('d F Y, H:i') }}
                            </p>
                            <p class="info-text small mb-0">
                                <i class="bi bi-calendar"></i> Tanggal Berkas Disetujui:
                                {{ $item->tanggal_berkas_disetujui
                                    ? \Carbon\Carbon::parse($item->tanggal_berkas_disetujui)->translatedFormat('d F Y, H:i')
                                    : 'Belum Disetujui' }}
                            </p>
                        </div>

                        {{-- Bagian Kanan --}}
                        <div class="col-md-4 col-12 text-right d-flex flex-column align-items-end mt-3 mt-md-0">
                            {{-- Status Berkas --}}
                            <h4 class="mb-2">
                                <mark
                                    class="badge-{{ $item->status_berkas === 'Disetujui' ? 'success' : ($item->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                    <i
                                        class="{{ $item->status_berkas === 'Disetujui' ? 'bi bi-check-circle' : ($item->status_berkas === 'Ditolak' ? 'bi bi-x-circle-fill' : 'bi bi-gear-fill') }}"></i>
                                    <span>Berkas</span> {{ $item->status_berkas }}
                                </mark>
                            </h4>

                            <p class="info-text mb-1 small">
                                Pengajuan Rancangan Tahun
                                {{ \Carbon\Carbon::parse($item->tanggal_pengajuan)->year }}
                            </p>
                            <div class="mt-2">

                                {{-- Tombol Tindakan --}}
                                <div class="btn-group">
                                    <button type="button" class="btn btn-neutral dropdown-toggle"
                                        data-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-gear"></i> Kelola Persetujuan
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right shadow">
                                        {{-- Lihat Detail --}}
                                        <a class="dropdown-item d-flex align-items-center" href="#"
                                            wire:click.prevent="openModal({{ $item->id_rancangan }})">
                                            <i class="bi bi-eye mr-2 text-default"></i>
                                            <span>Lihat Detail</span>
                                        </a>
                                        {{-- Reset Status --}}
                                        <a class="dropdown-item d-flex align-items-center text-danger" href="#"
                                            data-toggle="modal" data-target="#resetStatusModal"
                                            wire:click="setSelectedRancangan({{ $item->id_rancangan }})">
                                            <i class="bi bi-arrow-counterclockwise mr-2"></i>
                                            <span>Reset Status</span>
                                        </a>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                @empty
                    <div class="list-group-item text-center info-text">
                        Tidak ada rancangan dalam riwayat.
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            <div class="d-flex justify-content-center mt-3">
                {{ $riwayatRancangan->links('pagination::bootstrap-4') }}
            </div>

            {{-- Modal Detail Persetujuan --}}
            <div wire:ignore.self class="modal fade" id="modalPersetujuan" tabindex="-1" role="dialog"
                data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog modal-xl no-style-modal" role="document">
                    <div class="modal-content">

                        @if ($selectedRancangan)
                            <div class="row mt-3">
                                {{--  Informasi Utama  --}}
                                <div class="col-md-6 mb-2">
                                    <div class="card shadow-sm">
                                        <div class="card-header">
                                            <h4 class="mb-0">Informasi Utama</h4>
                                        </div>
                                        <div class="card-body table-responsive modal-table mt--2">
                                            <table class="table table-sm table-borderless">
                                                <tbody>
                                                    <tr>
                                                        <th class="info-text w-25">Nomor</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $selectedRancangan->no_rancangan ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Jenis</th>
                                                        <td class="wrap-text w-75">
                                                            <mark
                                                                class="badge-{{ $selectedRancangan->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                                                {{ $selectedRancangan->jenis_rancangan ?? 'N/A' }}
                                                            </mark>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Tentang</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $selectedRancangan->tentang ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Tanggal Pengajuan</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $selectedRancangan->tanggal_pengajuan ? \Carbon\Carbon::parse($selectedRancangan->tanggal_pengajuan)->translatedFormat('d F Y, H:i') : 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">User Pengaju</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $selectedRancangan->user->nama_user ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Perangkat Daerah</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $selectedRancangan->user->perangkatDaerah->nama_perangkat_daerah ?? 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Nomor Nota</th>
                                                        <td class="wrap-text-td-70 ">
                                                            {{ $selectedRancangan->nomor_nota ?? 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Tanggal Nota</th>
                                                        <td>
                                                            {{ $selectedRancangan->tanggal_nota ? \Carbon\Carbon::parse($selectedRancangan->tanggal_nota)->translatedFormat('d F Y') : 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Status Rancangan</th>
                                                        <td class="wrap-text w-75">
                                                            <mark
                                                                class="badge-{{ $selectedRancangan->status_rancangan === 'Disetujui' ? 'success' : ($selectedRancangan->status_rancangan === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                                {{ $selectedRancangan->status_rancangan ?? 'N/A' }}
                                                            </mark>
                                                        </td>
                                                    </tr>
                                                </tbody>

                                            </table>
                                        </div>
                                    </div>
                                </div>

                                {{--  File Persetujuan --}}
                                <div class="col-md-6 mb-2">
                                    <div class="card shadow-sm">
                                        <div class="card-header">
                                            <h4 class="mb-0">File Persetujuan</h4>
                                        </div>
                                        <div class="card-body table-responsive modal-table mt--2">
                                            <table class="table table-sm table-borderless">
                                                <tbody>
                                                    <tr>
                                                        <th class="info-text w-25">Nota Dinas</th>
                                                        <td class="wrap-text w-75">
                                                            @if ($selectedRancangan->nota_dinas_pd)
                                                                <a href="{{ url('/view-private/rancangan/nota_dinas/' . basename($selectedRancangan->nota_dinas_pd)) }}"
                                                                    target="_blank">
                                                                    <i
                                                                        class="bi bi-file-earmark-text mr-2 text-warning"></i>
                                                                    Lihat Nota
                                                                </a>
                                                            @else
                                                                <span class="text-muted">Tidak Ada Nota</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">File Rancangan</th>
                                                        <td class="wrap-text w-75">
                                                            @if ($selectedRancangan->rancangan)
                                                                <a href="{{ url('/view-private/rancangan/rancangan/' . basename($selectedRancangan->rancangan)) }}"
                                                                    target="_blank">
                                                                    <i
                                                                        class="bi bi-file-earmark-text mr-2 text-primary"></i>
                                                                    Lihat Rancangan
                                                                </a>
                                                            @else
                                                                <span class="text-muted">Tidak Ada Rancangan</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Matrik</th>
                                                        <td class="wrap-text w-75">
                                                            @if ($selectedRancangan->matrik)
                                                                <a href="{{ url('/view-private/rancangan/matrik/' . basename($selectedRancangan->matrik)) }}"
                                                                    target="_blank">
                                                                    <i
                                                                        class="bi bi-file-earmark-spreadsheet mr-2 text-success"></i>
                                                                    Lihat Matrik
                                                                </a>
                                                            @else
                                                                <span class="text-muted">Tidak Ada Matrik</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Bahan Pendukung</th>
                                                        <td class="wrap-text w-75">
                                                            @if ($selectedRancangan->bahan_pendukung)
                                                                <a href="{{ url('/view-private/rancangan/bahan_pendukung/' . basename($selectedRancangan->bahan_pendukung)) }}"
                                                                    target="_blank">
                                                                    <i
                                                                        class="bi bi-file-earmark-pdf mr-2 text-danger"></i>
                                                                    Lihat Bahan
                                                                </a>
                                                            @else
                                                                <span class="text-muted">Tidak Ada Bahan</span>
                                                            @endif
                                                        </td>
                                                    </tr>

                                                    <tr>
                                                        <th class="info-text w-25">Status Berkas</th>
                                                        <td class="wrap-text w-75">
                                                            <mark
                                                                class="badge badge-{{ $selectedRancangan->status_berkas === 'Disetujui' ? 'success' : ($selectedRancangan->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                                {{ $selectedRancangan->status_berkas ?? 'N/A' }}
                                                            </mark>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Tanggal Berkas Disetujui</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $selectedRancangan->tanggal_berkas_disetujui
                                                                ? \Carbon\Carbon::parse($selectedRancangan->tanggal_berkas_disetujui)->translatedFormat('d F Y, H:i')
                                                                : 'Belum disetujui' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Catatan Berkas</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $selectedRancangan->catatan_berkas ?? 'Tidak Ada Catatan' }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                        </div>
                                        {{-- Footer Modal --}}
                                        <div class="card-body ">
                                            <div class="text-right"> <button type="button"
                                                    class="btn btn-outline-warning" data-dismiss="modal">Tutup
                                                    Detail Persetujuan</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="d-flex justify-content-center align-items-start"
                                style="min-height: 200px; padding-top: 50px;">
                                <div class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    <p class="mt-3 info-text">Sedang memuat data, harap tunggu...</p>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
            </div>

            {{-- Modal Konfirmasi Reset Status --}}
            <div wire:ignore.self class="modal fade" id="resetStatusModal" tabindex="-1" role="dialog"
                aria-labelledby="resetStatusModalLabel" data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        {{-- Header --}}
                        <div class="modal-header">
                            <h5 class="modal-title d-flex align-items-center" id="resetStatusModalLabel">
                                <i class="bi bi-exclamation-triangle-fill mr-2"></i> Konfirmasi Reset Status
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        {{-- Body --}}
                        <div class="modal-body text-center">
                            @if ($selectedRancangan)
                                <p class="mb-3 info-text">
                                    Apakah Anda yakin ingin mereset status rancangan dengan nomor rancangan <strong>
                                        {{ $selectedRancangan->no_rancangan }}
                                    </strong>?
                                    Mohon diperhatikan lagi apakah sudah benar rancangannya.

                                </p>
                                <p class="text-danger">Semua data revisi di rancangan ini akan dihapus</p>
                                </p>
                                <i class="bi bi-arrow-counterclockwise text-danger" style="font-size: 3rem;"></i>
                            @else
                                <span class="text-danger">Rancangan tidak ditemukan.</span>
                            @endif
                        </div>

                        {{-- Footer --}}
                        <div class="modal-footer justify-content-center">
                            <button type="button" class="btn btn-danger d-flex align-items-center"
                                wire:click="confirmResetStatus" wire:loading.attr="disabled" data-dismiss="modal">
                                <span wire:loading.remove wire:target="confirmResetStatus">
                                    <i class="bi bi-check-circle mr-2"></i> Reset Status
                                </span>
                                <span wire:loading wire:target="confirmResetStatus">
                                    <i class="spinner-border spinner-border-sm mr-2"></i> Memproses...
                                </span>
                            </button>
                            <button type="button" class="btn btn-neutral d-flex align-items-center"
                                data-dismiss="modal">
                                <i class="bi bi-x-circle mr-2"></i> Batal
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
