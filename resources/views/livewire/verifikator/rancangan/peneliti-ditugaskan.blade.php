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
        {{-- Card Rancangan --}}
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
                            {{ \Carbon\Carbon::parse($item->tanggal_pengajuan)->translatedFormat('d F Y, H:i') }}
                        </p>
                        <p class="mb-0 info-text small">
                            <i class="bi bi-person-gear"></i>
                            <span
                                class="{{ $item->revisi->first()?->peneliti?->nama_user ? 'info-text' : 'text-danger' }} text-primary">
                                {{ $item->revisi->first()?->peneliti?->nama_user ?? 'Belum Ditentukan' }}
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
                                class="badge-{{ $item->revisi->first()?->status_revisi === 'Direvisi' ? 'success' : ($item->revisi->first()?->status_revisi === 'Proses Revisi' ? 'warning' : 'danger') }} badge-pill">
                                {{ $item->revisi->first()?->status_revisi ?? 'N/A' }}
                            </mark>
                        </p>
                    </div>
                </div>

                {{-- Bagian Kanan --}}
                <div class="text-right">
                    {{-- Status Peneliti --}}
                    <h4>
                        <mark class="badge-{{ $item->revisi->first()?->peneliti ? 'success' : 'danger' }} badge-pill">
                            @if ($item->revisi->first()?->peneliti)
                                <i class="bi bi-person-check"></i>
                                {{ $item->revisi->first()?->peneliti->nama_user }}
                            @else
                                <i class="bi bi-person-dash"></i>
                                Menunggu Pemilihan Peneliti
                            @endif
                        </mark>
                    </h4>

                    <p class="info-text mb-1 small">
                        Pengajuan Rancangan Tahun {{ \Carbon\Carbon::parse($item->tanggal_pengajuan)->year }}
                    </p>

                    <div class="dropdown">
                        <button type="button" class="btn btn btn-neutral dropdown-toggle" data-toggle="dropdown"
                            aria-expanded="false">
                            <i class="bi bi-gear"></i> Kelola Peneliti
                        </button>
                        <div class="dropdown-menu shadow-lg">
                            <a class="dropdown-item d-flex align-items-center text-default" href="#"
                                wire:click.prevent="openModal({{ $item->id_rancangan }})" data-toggle="modal"
                                data-target="#modalPilihPeneliti">
                                <i class="bi bi-eye "></i> Lihat Detail
                            </a>
                            {{-- Pilih Ulang Peneliti --}}
                            <a class="dropdown-item d-flex align-items-center text-default"
                                onclick="pilihUlangPeneliti({{ $item->id_rancangan }})">
                                <i class="bi bi-person-fill-exclamation text-success"></i>Pilih Ulang Peneliti
                            </a>
                            {{-- Tombol Reset Peneliti --}}
                            <a class="dropdown-item d-flex align-items-center text-danger"
                                onclick="confirmResetPeneliti({{ $item->id_rancangan }})">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset Peneliti
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <p class="text-center info-text">Tidak ada data rancangan sedang diajukan.</p>
    @endforelse

    {{-- Modal Detail Revisi --}}
    <div wire:ignore.self class="modal fade" id="modalPilihPeneliti" tabindex="-1" data-backdrop="static"
        data-keyboard="false">
        <div class="modal-dialog modal-xl no-style-modal">
            <div class="modal-content">

                <div class="modal-body">
                    @if ($selectedRevisi)
                        <div class="row">
                            {{--  Informasi Utama  --}}
                            <div class="col-md-6 mb-4">
                                <div class="card shadow-sm">
                                    <div class="card-header">
                                        <h4 class="mb-0">Informasi Utama</h4>
                                    </div>
                                    <div class="card-body">
                                        <p class="description info-text mb-3">Berikut adalah informasi dasar dari
                                            rancangan
                                            yang
                                            diajukan. Pastikan semua informasi sudah sesuai.</p>
                                        <table class="table table-sm table-borderless">
                                            <tbody>
                                                <tr>
                                                    <th class="info-text w-25">Nomor</th>
                                                    <td class="wrap-text w-75">
                                                        {{ $selectedRevisi->rancangan->no_rancangan ?? 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Jenis</th>
                                                    <td class="wrap-text w-75">
                                                        <mark
                                                            class="badge-{{ $selectedRevisi->rancangan->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                                            {{ $selectedRevisi->rancangan->jenis_rancangan ?? 'N/A' }}
                                                        </mark>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Tentang</th>
                                                    <td class="wrap-text w-75">
                                                        {{ $selectedRevisi->rancangan->tentang ?? 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Tanggal Pengajuan</th>
                                                    <td class="wrap-text w-75">
                                                        {{ $selectedRevisi->rancangan->tanggal_pengajuan ? \Carbon\Carbon::parse($selectedRevisi->rancangan->tanggal_pengajuan)->translatedFormat('d F Y, H:i') : 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">User Pengaju</th>
                                                    <td class="wrap-text w-75">
                                                        {{ $selectedRevisi->rancangan->user->nama_user ?? 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Perangkat Daerah</th>
                                                    <td class="wrap-text w-75">
                                                        {{ $selectedRevisi->rancangan->user->perangkatDaerah->nama_perangkat_daerah ?? 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Status Rancangan</th>
                                                    <td class="wrap-text w-75">
                                                        <mark
                                                            class="badge-{{ $selectedRevisi->rancangan->status_rancangan === 'Disetujui' ? 'success' : ($selectedRevisi->rancangan->status_rancangan === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                            {{ $selectedRevisi->rancangan->status_rancangan ?? 'N/A' }}
                                                        </mark>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Nota Dinas</th>
                                                    <td class="wrap-text w-75">
                                                        @if ($selectedRevisi->rancangan->nota_dinas_pd)
                                                            <a href="{{ url('/view-private/rancangan/nota_dinas/' . basename($selectedRevisi->rancangan->nota_dinas_pd)) }}"
                                                                target="_blank">
                                                                <i
                                                                    class="bi bi-file-earmark-pdf mr-2 text-warning"></i>
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
                                                        @if ($selectedRevisi->rancangan->rancangan)
                                                            <a href="{{ url('/view-private/rancangan/rancangan/' . basename($selectedRevisi->rancangan->rancangan)) }}"
                                                                target="_blank">
                                                                <i
                                                                    class="bi bi-file-earmark-pdf mr-2 text-primary"></i>
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
                                                        @if ($selectedRevisi->rancangan->matrik)
                                                            <a href="{{ url('/view-private/rancangan/matrik/' . basename($selectedRevisi->rancangan->matrik)) }}"
                                                                target="_blank">
                                                                <i
                                                                    class="bi bi-file-earmark-pdf mr-2 text-success"></i>
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
                                                        @if ($selectedRevisi->rancangan->bahan_pendukung)
                                                            <a href="{{ url('/view-private/rancangan/bahan_pendukung/' . basename($selectedRevisi->rancangan->bahan_pendukung)) }}"
                                                                target="_blank">
                                                                <i class="bi bi-file-earmark-pdf mr-2 text-danger"></i>
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
                                                            class="badge-{{ $selectedRevisi->rancangan->status_berkas === 'Disetujui' ? 'success' : ($selectedRevisi->rancangan->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                            {{ $selectedRevisi->rancangan->status_berkas ?? 'N/A' }}
                                                        </mark>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Tanggal Berkas Disetujui</th>
                                                    <td class="wrap-text w-75">
                                                        {{ $selectedRevisi->rancangan->tanggal_berkas_disetujui
                                                            ? \Carbon\Carbon::parse($selectedRevisi->rancangan->tanggal_berkas_disetujui)->translatedFormat('d F Y, H:i')
                                                            : 'N/A' }}
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <th class="info-text w-25">Catatan Berkas</th>
                                                    <td class="wrap-text w-75">
                                                        {{ $selectedRevisi->rancangan->catatan_berkas ?? 'Tidak Ada Catatan' }}
                                                    </td>
                                                </tr>
                                            </tbody>

                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{--  Detail Revisi --}}
                            <div class="col-md-6 mb-4">
                                <div class="card shadow-sm">
                                    <div class="card-header">
                                        <h4 class="mb-0">Detail Revisi</h4>
                                    </div>
                                    <div class="card-body">
                                        <p class="description info-text mb-3">Pastikan file yang diajukan sudah
                                            lengkap
                                            dan
                                            sesuai.
                                            Anda dapat mengunduh file untuk memverifikasinya.</p>
                                        <table class="table table-sm table-borderless">
                                            <tbody>
                                                <tr>
                                                    <th>Status Revisi</th>
                                                    <td class="info-text">
                                                        <mark
                                                            class="badge-{{ $selectedRevisi->status_revisi === 'Direvisi' ? 'success' : ($selectedRevisi->status_revisi === 'Menunggu Revisi' ? 'warning' : 'danger') }} badge-pill">
                                                            {{ $selectedRevisi->status_revisi }}
                                                        </mark>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Tanggal Revisi</th>
                                                    <td class="info-text">
                                                        {{ $selectedRevisi->tanggal_revisi ? \Carbon\Carbon::parse($selectedRevisi->tanggal_revisi)->translatedFormat('d F Y, H:i') : 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Peneliti</th>
                                                    <td class="info-text ">
                                                        {{ $selectedRevisi->peneliti->nama_user ?? 'Belum Ditentukan' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Tanggal Peneliti Ditunjuk</th>
                                                    <td class="info-text">
                                                        {{ $selectedRevisi->tanggal_peneliti_ditunjuk ? \Carbon\Carbon::parse($selectedRevisi->tanggal_peneliti_ditunjuk)->translatedFormat('d F Y, H:i') : 'Belum Ditentukan' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Revisi Rancangan</th>
                                                    <td class="info-text">
                                                        @if ($selectedRevisi->revisi_rancangan)
                                                            <a href="{{ asset('storage/' . $selectedRevisi->revisi_rancangan) }}"
                                                                target="_blank" class="d-flex align-items-center">
                                                                <i
                                                                    class="bi bi-file-earmark-text mr-2 text-primary"></i>
                                                                <span>Download Revisi</span>
                                                            </a>
                                                        @else
                                                            <span class="text-muted d-flex align-items-center">
                                                                <i class="bi bi-file-earmark-x mr-2 text-danger"></i>
                                                                <span>Revisi Tidak Tersedia</span>
                                                            </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Revisi Matrik</th>
                                                    <td class="info-text">
                                                        @if ($selectedRevisi->revisi_matrik)
                                                            <a href="{{ asset('storage/' . $selectedRevisi->revisi_matrik) }}"
                                                                target="_blank" class="d-flex align-items-center">
                                                                <i
                                                                    class="bi bi-file-earmark-spreadsheet mr-2 text-success"></i>
                                                                <span>Download Matrik Revisi</span>
                                                            </a>
                                                        @else
                                                            <span class="text-muted d-flex align-items-center">
                                                                <i class="bi bi-file-earmark-x mr-2 text-danger"></i>
                                                                <span>Matrik Tidak Tersedia</span>
                                                            </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Catatan Revisi</th>
                                                    <td class="wrap-text">
                                                        {{ $selectedRevisi->catatan_revisi ?? 'Tidak Ada Catatan' }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="card-body">
                                        {{-- Alert Peneliti Sudah Dipilih --}}
                                        <div class="alert alert-default" role="alert">
                                            <i class="bi bi-info-circle"></i>
                                            Peneliti
                                            <strong>{{ $selectedRevisi->peneliti->nama_user }}</strong>
                                            telah ditetapkan sebagai peneliti.
                                            Jika anda ingin memilih ulang <strong>Pilih Aksi Reset</strong> untuk
                                            mereset ulang peneliti !
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-warning"
                                            data-dismiss="modal">Tutup
                                            Detail </button>
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

    {{-- Script Modal --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.Livewire.on('openModalPilihPeneliti', () => {
                $('#modalPilihPeneliti').modal('show');
            });

            window.Livewire.on('closeModalPilihPeneliti', () => {
                $('#modalPilihPeneliti').modal('hide');
            });
        });
    </script>
</div>
