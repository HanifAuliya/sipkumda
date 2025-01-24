<div>
    {{-- Daftar Revisi --}}
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Daftar Rancangan - Menunggu Revisi</h3>
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
            {{-- Daftar Revisi --}}
            @forelse ($revisi as $item)
                <div class="card p-3 shadow-sm border mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        {{-- Bagian Kiri --}}
                        <div class="d-flex align-items-start">
                            <div>
                                {{-- Informasi Utama --}}
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
                                    <i class="bi bi-person"></i>
                                    {{ $item->user->nama_user ?? 'N/A' }}
                                    <span class="badge badge-secondary">Pemohon</span>
                                </p>
                                <p class="mb-0 info-text small">
                                    <i class="bi bi-person-gear"></i>
                                    <span
                                        class="{{ $item->revisi->last()->peneliti->nama_user ?? false ? 'info-text' : 'text-danger' }}">
                                        {{ $item->revisi->last()->peneliti->nama_user ?? 'Belum Ditentukan' }}
                                    </span>
                                    <span class="badge badge-secondary">Peneliti</span>
                                </p>
                                <p class="mb-0 info-text small">
                                    <i class="bi bi-calendar"></i>
                                    Tanggal Peneliti Ditunjuk
                                    {{ \Carbon\Carbon::parse($item->revisi->last()->tanggal_peneliti_ditunjuk)->translatedFormat('d F Y, H:i') }}
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
                                        class="badge-{{ $item->revisi->last()->status_revisi === 'Menunggu Revisi' ? 'warning' : 'danger' }} badge-pill">
                                        {{ $item->revisi->last()->status_revisi ?? 'N/A' }}
                                    </mark>
                                </p>
                            </div>
                        </div>

                        {{-- Bagian Kanan --}}
                        <div class="text-right">
                            <h4>
                                <mark
                                    class="badge-{{ $item->revisi->last()->status_revisi === 'Menunggu Revisi' ? 'warning' : 'danger' }} badge-pill">
                                    <i class="bi bi-clipboard2-check"></i>
                                    {{ $item->revisi->last()->status_revisi ?? 'N/A' }}
                                </mark>
                            </h4>
                            <p class="info-text mb-1 small">
                                Pengajuan Rancangan Tahun {{ \Carbon\Carbon::parse($item->tanggal_pengajuan)->year }}
                            </p>
                            <div class="mt-2">
                                <div class="dropdown">
                                    <button type="button" class="btn btn btn-neutral dropdown-toggle"
                                        data-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-gear"></i> Kelola Revisi
                                    </button>
                                    <div class="dropdown-menu shadow-lg">
                                        <a class="dropdown-item d-flex align-items-center" href="#"
                                            wire:click.prevent="loadDetailRevisi({{ $item->id_rancangan }})">
                                            <i class="bi bi-info-circle"></i> Lihat Detail Revisi
                                        </a>
                                        <a href="#" class="dropdown-item d-flex align-items-center"
                                            wire:click.prevent="selectRevisi({{ $item->revisi->last()->id_revisi ?? 'null' }})">
                                            <i class="bi bi-upload text-success"></i>
                                            Unggah Revisi
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-center info-text">Tidak ada data rancangan menunggu revisi.</p>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="d-flex justify-content-center mt-3">
            {{ $revisi->links('pagination::bootstrap-4') }}
        </div>
    </div>

    {{-- Modal Unggah Revisi --}}
    <div wire:ignore.self class="modal fade" id="uploadRevisiModal" tabindex="-1" role="dialog"
        aria-labelledby="uploadRevisiModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form wire:submit.prevent="uploadRevisi">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title" id="uploadRevisiModalLabel">Upload Revisi</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                            wire:click="resetForm">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        {{-- Input File Revisi Rancangan --}}
                        <div class="mb-4">
                            <label for="revisiRancangan" class="font-weight-bold form-control-label">
                                <i class="bi bi-file-earmark-pdf text-primary"></i> File Revisi Rancangan
                                <small class="text-muted d-block">Unggah dokumen rancangan dalam format PDF (max:
                                    2MB).</small>
                            </label>
                            <input type="file" class="form-control" wire:model="revisiRancangan" accept=".pdf" />
                            <div wire:loading wire:target="revisiRancangan" class="text-info mt-2">
                                <i class="spinner-border spinner-border-sm"></i> Mengunggah...
                            </div>
                            @error('revisiRancangan')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Input File Revisi Matrik --}}
                        <div class="mb-4">
                            <label for="revisiMatrik" class="font-weight-bold form-control-label">
                                <i class="bi bi-file-earmark-pdf text-primary"></i> File Revisi Matrik
                                <small class="text-muted d-block">Unggah dokumen rancangan dalam format PDF (max:
                                    10 MB).</small>
                            </label>
                            <input type="file" class="form-control" wire:model="revisiMatrik" accept=".pdf" />
                            <div wire:loading wire:target="revisiMatrik" class="text-info mt-2">
                                <i class="spinner-border spinner-border-sm"></i> Mengunggah...
                            </div>
                            @error('revisiMatrik')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Catatan Revisi --}}
                        <div class="mb-4">
                            <label for="catatanRevisi" class="font-weight-bold">Catatan Revisi</label>
                            <textarea id="catatanRevisi" class="form-control" wire:model="catatanRevisi" rows="4"
                                placeholder="Tambahkan catatan revisi jika diperlukan"></textarea>
                            @error('catatanRevisi')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-warning" data-dismiss="modal"
                            wire:click="resetForm">
                            Batal
                        </button>
                        <button class="btn btn-outline-default" type="submit" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="uploadRevisi">Upload</span>
                            <span wire:loading wire:target="uploadRevisi">
                                <i class="spinner-border spinner-border-sm"></i> Memproses...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div wire:ignore.self class="modal fade" id="detailRevisiModal" tabindex="-1" role="dialog"
        aria-labelledby="detailRevisiModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    @if ($selectedRevisi)
                        <div class="row">
                            {{-- Informasi Utama --}}
                            <div class="col-md-6 mb-4">
                                <div class="card shadow-sm">
                                    <div class="card-header">
                                        <h4 class="mb-0">Informasi Utama</h4>
                                    </div>
                                    <div class="card-body">
                                        <p class="description info-text mb-3">
                                            Berikut adalah informasi dasar dari rancangan yang diajukan. Pastikan semua
                                            informasi sudah sesuai.
                                        </p>
                                        <table class="table table-sm table-borderless">
                                            <tbody>
                                                <tr>
                                                    <th class="info-text w-25">Nomor</th>
                                                    <td class="wrap-text w-75">
                                                        {{ $selectedRevisi->rancangan->no_rancangan ?? 'N/A' }}</td>
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
                                                        {{ $selectedRevisi->rancangan->tentang ?? 'N/A' }}</td>
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
                                                        {{ $selectedRevisi->rancangan->user->nama_user ?? 'N/A' }}</td>
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
                                                                target="_blank" class="d-flex align-items-center">
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
                                                                target="_blank" class="d-flex align-items-center">
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
                                                                target="_blank" class="d-flex align-items-center">
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

                            {{-- Detail Revisi --}}
                            <div class="col-md-6 mb-4">
                                <div class="card shadow-sm">
                                    <div class="card-header">
                                        <h4 class="mb-0">Detail Revisi</h4>
                                    </div>
                                    <div class="card-body">
                                        <p class="description info-text mb-3">
                                            Pastikan file yang diajukan sudah lengkap dan sesuai. Anda dapat mengunduh
                                            file untuk memverifikasinya.
                                        </p>
                                        <table class="table table-sm table-borderless">
                                            <tbody>
                                                <tr>
                                                    <th class="info-text w-25">Status Revisi</th>
                                                    <td class="wrap-text w-75">
                                                        <mark
                                                            class="badge-{{ $selectedRevisi->status_revisi === 'Direvisi' ? 'success' : ($selectedRevisi->status_revisi === 'Menunggu Revisi' ? 'warning' : 'danger') }} badge-pill">
                                                            {{ $selectedRevisi->status_revisi }}
                                                        </mark>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Status Validasi</th>
                                                    <td class="wrap-text w-75">
                                                        <mark
                                                            class="badge-{{ $selectedRevisi->status_validasi === 'Direvisi' ? 'success' : ($selectedRevisi->status_revisi === 'Menunggu Revisi' ? 'warning' : 'danger') }} badge-pill">
                                                            {{ $selectedRevisi->status_revisi }}
                                                        </mark>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Tanggal Revisi</th>
                                                    <td class="wrap-text w-75">
                                                        {{ $selectedRevisi->tanggal_revisi ? \Carbon\Carbon::parse($selectedRevisi->tanggal_revisi)->translatedFormat('d F Y, H:i') : 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Peneliti</th>
                                                    <td class="wrap-text w-75">
                                                        {{ $selectedRevisi->peneliti->nama_user ?? 'Belum Ditentukan' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Tanggal Peneliti Ditunjuk</th>
                                                    <td class="wrap-text w-75">
                                                        {{ $selectedRevisi->tanggal_peneliti_ditunjuk ? \Carbon\Carbon::parse($selectedRevisi->tanggal_peneliti_ditunjuk)->translatedFormat('d F Y, H:i') : 'Belum Ditentukan' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Revisi Rancangan</th>
                                                    <td class="wrap-text w-75">
                                                        @if ($selectedRevisi->revisi_rancangan)
                                                            <a href="{{ url('/view-private/revisi/rancangan/' . basename($selectedRevisi->revisi_rancangan)) }}"
                                                                target="_blank" class="d-flex align-items-center">
                                                                <i
                                                                    class="bi bi-file-earmark-text mr-2 text-primary"></i>
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
                                                        @if ($selectedRevisi->revisi_matrik)
                                                            <a href="{{ url('/view-private/revisi/matrik/' . basename($selectedRevisi->revisi_matrik)) }}"
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
                                            Revisi Rancangan berstatus
                                            <strong>{{ $selectedRevisi->status_revisi }}.</strong>
                                            Silahkan Tunggu Verifikator melakukan <strong>Validasi</strong> Revisi !
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-dismiss="modal">Tutup</button>
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
    </div>
</div>
