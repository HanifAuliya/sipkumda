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

    {{-- Daftar Rancangan --}}
    <div class="list-group">
        @forelse ($rancanganMenunggu as $item)
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
                                {{ \Carbon\Carbon::parse($item->revisi->last()?->tanggal_revisi)->translatedFormat('d F Y, H:i') }}
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
                                    class="badge-{{ $item->revisi->last()?->status_revisi === 'Direvisi' ? 'success' : ($item->revisi->last()?->status_revisi === 'Menunggu Revisi' ? 'warning' : 'danger') }} badge-pill">
                                    {{ $item->revisi->last()?->status_revisi ?? 'N/A' }}
                                </mark>
                            </p>
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
                            Pengajuan Rancangan Tahun {{ \Carbon\Carbon::parse($item->tanggal_pengajuan)->year }}
                        </p>

                        <div class="mt-2">
                            <a href="#" class="btn btn-neutral" data-toggle="modal"
                                wire:click="openModalValidasiRancangan({{ $item->id_rancangan }})"
                                data-target="#modalValidasiRancangan">
                                Validasi Rancangan <i class="bi bi-question-circle"></i>
                            </a>
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
        {{ $rancanganMenunggu->links() }}
    </div>

    <div wire:ignore.self class="modal fade" id="modalValidasiRancangan" tabindex="-1" role="dialog"
        aria-labelledby="modalValidasiRancanganLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-xl no-style-modal" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    @if ($selectedRancangan)
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
                                    </div>
                                    <div class="card-body">
                                        {{-- Form Validasi --}}
                                        <form wire:submit.prevent="validasiRevisi">

                                            {{-- Pilihan Validasi --}}
                                            <div class="form-group">
                                                {{-- Form Pilih Peneliti --}}
                                                <p class="info-text description">
                                                    Silahkan pilih peneliti dari daftar di bawah ini untuk
                                                    menugaskan
                                                    rancangan yang sedang diproses.
                                                </p>
                                                <label for="statusValidasi">Pilih Validasi</label>
                                                <select id="statusValidasi" class="form-control"
                                                    wire:model.defer="statusValidasi">
                                                    <option hidden>Pilih Status Validasi...</option>
                                                    <option value="Diterima">Diterima</option>
                                                    <option value="Ditolak">Ditolak</option>
                                                </select>
                                                @error('statusValidasi')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            {{-- Catatan Validasi --}}
                                            <div class="form-group">
                                                <label for="catatanValidasi">Catatan Validasi</label>
                                                <textarea id="catatanValidasi" class="form-control" wire:model.defer="catatanValidasi"
                                                    placeholder="Tambahkan catatan terkait validasi ini..." rows="3"></textarea>
                                                @error('catatanValidasi')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="form-group text-right">
                                                <button type="button" class="btn btn-outline-warning"
                                                    data-dismiss="modal" wire:click="resetFormValidasi">
                                                    Batal
                                                </button>
                                                <button type="submit"
                                                    class="btn btn-outline-default"wire:loading.attr="disabled"
                                                    wire:target="validasiRevisi">
                                                    <!-- Spinner Loading -->
                                                    <span wire:loading wire:target="validasiRevisi">
                                                        <i class="spinner-border spinner-border-sm mr-1"
                                                            role="status" aria-hidden="true"></i>Proses..
                                                    </span>
                                                    <!-- Ikon dan Teks -->
                                                    <span wire:loading.remove wire:target="validasiRevisi">
                                                        <i class="bi bi-check-circle mr-1"></i> Validasi
                                                    </span>
                                                </button>

                                            </div>
                                        </form>
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
</div>
