<div>
    {{-- Searching --}}
    <div class="d-flex justify-content-between mb-3">
        <input type="text" class="form-control w-50" placeholder="Cari berdasarkan No Rancangan atau Tentang"
            wire:model.debounce.500ms="search">
        <select class="form-control w-25" wire:model="perPage">
            <option value="5">5 Data</option>
            <option value="10">10 Data</option>
            <option value="20">20 Data</option>
        </select>
    </div>

    {{-- Daftar Riwayat Validasi --}}
    <div class="list-group">
        @forelse ($riwayatValidasi as $item)
            {{-- Card Tab Riwayat Validasi --}}
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
                            <p class="mb-0 info-text small text-primary">
                                <i class="bi bi-person"></i>
                                {{ $item->user->nama_user ?? 'N/A' }}
                                <span class="badge badge-secondary">
                                    Pemohon
                                </span>
                            </p>
                            <p class="mb-0 info-text small">
                                <i class="bi bi-calendar"></i>
                                Tanggal Revisi
                                {{ \Carbon\Carbon::parse($item->tanggal_revisi)->translatedFormat('d F Y, H:i') }}
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
                                    class="badge-{{ $item->revisi->last()->status_revisi === 'Direvisi' ? 'success' : ($item->revisi->last()->status_revisi === 'Menunggu Revisi' ? 'warning' : 'danger') }} badge-pill">
                                    {{ $item->revisi->last()->status_revisi ?? 'N/A' }}
                                </mark>
                            </p>
                            @if ($item->status_rancangan === 'Disetujui')
                                <div class="alert alert-default alert-dismissible fade show mb-2" role="alert">
                                    <span class="alert-icon"><i class="bi bi-clipboard-x"></i></span>
                                    <span class="alert-text">
                                        <strong>Rancangan Telah Selesai</strong> untuk tahap Pengajuan Rancangan. Pantau
                                        untuk lanjut ke tahap Fasilitasi!
                                    </span>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif
                            @if ($item->revisi->last()->status_validasi === 'Ditolak')
                                <div class="alert alert-danger alert-dismissible fade show mb-2" role="alert">
                                    <span class="alert-icon"><i class="bi bi-clipboard-x"></i></span>
                                    <span class="alert-text">
                                        <strong>Anda Menolak Validasi ! </strong>Tunggu Peneliti Merivisi dan memeriksa
                                        ulang revisi!
                                    </span>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                        </div>
                    </div>

                    {{-- Bagian Kanan --}}
                    <div class="text-right">
                        {{-- Status Rancangan --}}
                        <h4>
                            <mark class="badge-secondary badge-pill">
                                <i class="bi bi-person-check"></i>
                                {{ $item->revisi->last()?->peneliti->nama_user }}
                            </mark>
                        </h4>

                        <p class="info-text mb-1 small">
                            Pengajuan Rancangan Tahun
                            {{ \Carbon\Carbon::parse($item->tanggal_pengajuan)->year }}
                        </p>

                        <div class="dropdown">
                            <button type="button" class="btn btn btn-neutral dropdown-toggle" data-toggle="dropdown"
                                aria-expanded="false">
                                <i class="bi bi-gear"></i> Kelola Revisi
                            </button>
                            <div class="dropdown-menu shadow-lg">
                                <a class="dropdown-item d-flex align-items-center" href="#"
                                    wire:click.prevent="loadDetailValidasi({{ $item->id_rancangan }})">
                                    <i class="bi bi-info-circle"></i> Lihat Detail Revisi
                                </a>
                                {{-- Reset Revisi --}}
                                <a class="dropdown-item text-danger d-flex align-items-center"
                                    onclick="confirmResetValidasi({{ $item->id_rancangan }})">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset Revisi
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-center">Tidak ada data yang ditemukan.</p>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="d-flex justify-content-center mt-3">
        {{ $riwayatValidasi->links('pagination::bootstrap-4') }}
    </div>

    <div wire:ignore.self class="modal fade" id="detailValidasiModal" tabindex="-1" role="dialog"
        aria-labelledby="detailValidasiModalLabel">
        <div class="modal-dialog modal-xl no-style-modal" role="document">
            <div class="modal-content">
                @if ($selectedRancangan)
                    <div class="row">
                        {{-- Informasi Utama --}}
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h4 class="mb-0">Informasi Utama</h4>
                                </div>
                                <div class="card-body">
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
                                                    {{ $selectedRancangan->user->nama_user ?? 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Perangkat Daerah</th>
                                                <td class="wrap-text w-75">
                                                    {{ $selectedRancangan->user->perangkatDaerah->nama_perangkat_daerah ?? 'N/A' }}
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
                                            <tr>
                                                <th class="info-text w-25">Nota Dinas</th>
                                                <td class="wrap-text w-75">
                                                    @if ($selectedRancangan->nota_dinas_pd)
                                                        <a href="{{ url('/view-private/rancangan/nota_dinas/' . basename($selectedRancangan->nota_dinas_pd)) }}"
                                                            target="_blank" class="d-flex align-items-center">
                                                            <i class="bi bi-file-earmark-pdf mr-2 text-warning"></i>
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
                                                            target="_blank" class="d-flex align-items-center">
                                                            <i class="bi bi-file-earmark-pdf mr-2 text-primary"></i>
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
                                                            target="_blank" class="d-flex align-items-center">
                                                            <i class="bi bi-file-earmark-pdf mr-2 text-success"></i>
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
                                                            target="_blank" class="d-flex align-items-center">
                                                            <i class="bi bi-file-earmark-pdf mr-2 text-danger"></i>
                                                            Lihat Bahan Pendukung
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
                                                        class="badge-{{ $selectedRancangan->status_berkas === 'Disetujui' ? 'success' : ($selectedRancangan->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                        {{ $selectedRancangan->status_berkas ?? 'N/A' }}
                                                    </mark>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Tanggal Berkas Disetujui</th>
                                                <td class="wrap-text w-75">
                                                    {{ $selectedRancangan->tanggal_berkas_disetujui
                                                        ? \Carbon\Carbon::parse($selectedRancangan->tanggal_berkas_disetujui)->translatedFormat('d F Y, H:i')
                                                        : 'N/A' }}
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
                            </div>
                        </div>

                        {{-- Detail Revisi --}}
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h4 class="mb-0">Detail Revisi</h4>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tbody>
                                            <tr>
                                                <th class="info-text w-25">Status Revisi</th>
                                                <td class="wrap-text w-75">
                                                    <mark
                                                        class="badge-{{ $selectedRancangan->revisi->last()->status_revisi === 'Direvisi' ? 'success' : ($selectedRancangan->revisi->last()->status_revisi === 'Menunggu Revisi' ? 'warning' : 'danger') }} badge-pill">
                                                        {{ $selectedRancangan->revisi->last()->status_revisi }}
                                                    </mark>

                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Status Validasi</th>
                                                <td class="wrap-text w-75">
                                                    <mark
                                                        class="badge-{{ $selectedRancangan->revisi->last()->status_validasi === 'Diterima' ? 'success' : ($selectedRancangan->revisi->last()->status_validasi === 'Menunggu Validasi' ? 'warning' : 'danger') }} badge-pill">
                                                        {{ $selectedRancangan->revisi->last()->status_validasi }}
                                                    </mark>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Tanggal Revisi</th>
                                                <td class="wrap-text w-75">
                                                    {{ \Carbon\Carbon::parse($selectedRancangan->revisi->last()->tanggal_revisi)->translatedFormat('d F Y, H:i') }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Peneliti</th>
                                                <td class="wrap-text w-75">
                                                    {{ $selectedRancangan->revisi->last()->peneliti->nama_user ?? 'Belum Ditentukan' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Tanggal Peneliti Ditunjuk</th>
                                                <td class="wrap-text w-75">
                                                    {{ $selectedRancangan->revisi->last()->tanggal_peneliti_ditunjuk ? \Carbon\Carbon::parse($selectedRancangan->revisi->last()->tanggal_peneliti_ditunjuk)->translatedFormat('d F Y, H:i') : 'Belum Ditentukan' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Revisi Rancangan</th>
                                                <td class="wrap-text w-75">
                                                    @if ($selectedRancangan->revisi->last() && $selectedRancangan->revisi->last()->revisi_rancangan)
                                                        <a href="{{ url('/view-private/revisi/rancangan/' . basename($selectedRancangan->revisi->last()->revisi_rancangan)) }}"
                                                            target="_blank" class="d-flex align-items-center">
                                                            <i class="bi bi-file-earmark-text mr-2 text-primary"></i>
                                                            Lihat Revisi
                                                        </a>
                                                    @else
                                                        <span class="text-muted">Data Tidak Ada</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Revisi Matrik</th>
                                                <td class="wrap-text w-75">
                                                    @if ($selectedRancangan->revisi->last() && $selectedRancangan->revisi->last()->revisi_matrik)
                                                        <a href="{{ url('/view-private/revisi/matrik/' . basename($selectedRancangan->revisi->last()->revisi_matrik)) }}"
                                                            target="_blank" class="d-flex align-items-center">
                                                            <i
                                                                class="bi bi-file-earmark-spreadsheet mr-2 text-success"></i>
                                                            Lihat Matrik Revisi
                                                        </a>
                                                    @else
                                                        <span class="text-muted">Data Tidak Ada</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Catatan Revisi</th>
                                                <td class="wrap-text w-75">
                                                    @if ($selectedRancangan->revisi->last() && $selectedRancangan->revisi->last()->catatan_revisi)
                                                        {{ $selectedRancangan->revisi->last()->catatan_revisi }}
                                                    @else
                                                        <span class="text-muted">Tidak Ada Catatan</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div class="card-body">
                                        {{-- Alert Peneliti Sudah Dipilih --}}
                                        <div class="alert alert-default" role="alert">
                                            <i class="bi bi-info-circle"></i>
                                            Peneliti
                                            <strong>{{ $selectedRancangan->no_rancangan }}</strong>
                                            telah telah di setujui.
                                            Jika anda ingin memilih ulang <strong>Pilih Aksi Reset</strong> untuk
                                            mereset validasi !
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-warning"
                                                data-dismiss="modal" wire:click="resetDetail">Tutup
                                                Detail </button>
                                        </div>
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
        document.addEventListener('DOMContentLoaded', () => {
            // nyoba manggil dispatch dengan id
            // Listener untuk membuka modal
            Livewire.on('openModal', (modalId) => {
                $(`#${modalId}`).modal('show');
            });

            // Listener untuk menutup modal
            Livewire.on('closeModal', (modalId) => {
                $(`#${modalId}`).modal('hide');
            });

            // ini yang langsun gmangiglnya
            window.Livewire.on('openUploadRevisiModal', () => {
                $('#uploadRevisiModal').modal('show');
            });

            window.Livewire.on('openDetailRevisiModal', () => {
                $('#detailRevisiModal').modal('show');
            });

            window.Livewire.on('closeUploadRevisiModal', () => {
                $('#uploadRevisiModal').modal('hide');
            });

            window.addEventListener('swal:modal', function(event) {
                const data = event.detail[0];
                Swal.fire({
                    icon: data.type, // 'success', 'error', 'warning', etc.
                    title: data.title,
                    text: data.message,
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    position: 'top-end',
                    toast: true,
                });
            });

            window.addEventListener('swal:reset', function(event) {
                const data = event.detail[0];

                Swal.fire({
                    icon: data.type || 'info',
                    title: data.title || 'Informasi',
                    text: data.message || 'Tidak ada pesan yang diterima.',
                    showConfirmButton: true,
                });
            });
        });
    </script>
</div>
