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

    <div>
        @forelse ($rancangan as $item)
            {{-- Card Tab Sedang Diajukan --}}
            <div class="card p-3 shadow-sm border mb-3">
                <div class="row">
                    {{-- Bagian Kiri --}}
                    <div class="col-md-8 col-12 mb-3 d-flex flex-column">
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
                                    <i class="bi bi-person"></i>
                                    {{ $item->user->nama_user ?? 'N/A' }}
                                    <span class="badge badge-secondary">
                                        Pemohon
                                    </span>
                                </p>
                                <p class="mb-0 info-text small">
                                    <i class="bi bi-calendar"></i>
                                    Tanggal Berkas Disetujui:
                                    {{ \Carbon\Carbon::parse($item->tanggal_berkas_disetujui)->translatedFormat('d F Y, H:i') }}
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
                                        class="badge-{{ $item->revisi->first()?->status_revisi === 'Direvisi' ? 'success' : ($item->revisi->first()?->status_revisi === 'Menunggu Revisi' ? 'warning' : 'danger') }} badge-pill">
                                        {{ $item->revisi->first()?->status_revisi ?? 'N/A' }}
                                    </mark>
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Bagian Kanan --}}
                    <div class="col-md-4 col-12 d-flex flex-column justify-content-center text-right">
                        {{-- Status Rancangan --}}
                        <h4>
                            <mark
                                class="badge-{{ $item->revisi->first()?->peneliti ? 'success' : 'danger' }} badge-pill">
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

                        <div class="mt-2">
                            <a href="#" class="btn btn-neutral" data-toggle="modal"
                                wire:click="openModal({{ $item->id_rancangan }})" data-target="#modalPilihPeneliti">
                                Pilih Peneliti <i class="bi bi-question-circle"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-center info-text">Tidak ada data rancangan sedang diajukan.</p>
        @endforelse

        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex justify-content-center w-100 w-md-auto">
                {{ $rancangan->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>


    {{-- Modal --}}
    <div wire:ignore.self class="modal fade" id="modalPilihPeneliti" tabindex="-1" data-backdrop="static"
        data-keyboard="false">
        <div class="modal-dialog modal-xl no-style-modal">
            <div class="modal-content">
                @if ($selectedRancangan)
                    <div class="row">
                        {{--  Informasi Utama  --}}
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h4 class="mb-0">Informasi Utama</h4>
                                </div>
                                <div class="card-body modal-table mt--3">

                                    <table class="table table-sm table-borderless">
                                        <tbody>
                                            <tr>
                                                <th class="info-text w-25">Nomor</th>
                                                <td class="text-wrap w-75">
                                                    {{ $selectedRancangan->no_rancangan ?? 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Jenis</th>
                                                <td class="text-wrap w-75">
                                                    <mark
                                                        class="badge-{{ $selectedRancangan->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                                        {{ $selectedRancangan->jenis_rancangan ?? 'N/A' }}
                                                    </mark>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Tentang</th>
                                                <td class="text-wrap w-75">
                                                    {{ $selectedRancangan->tentang ?? 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Tanggal Pengajuan</th>
                                                <td class="text-wrap w-75">
                                                    {{ $selectedRancangan->tanggal_pengajuan ? \Carbon\Carbon::parse($selectedRancangan->tanggal_pengajuan)->translatedFormat('d F Y, H:i') : 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">User Pengaju</th>
                                                <td class="text-wrap w-75">
                                                    {{ $selectedRancangan->user->nama_user ?? 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Perangkat Daerah</th>
                                                <td class="text-wrap w-75">
                                                    {{ $selectedRancangan->user->perangkatDaerah->nama_perangkat_daerah ?? 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Status Rancangan</th>
                                                <td class="text-wrap w-75">
                                                    <mark
                                                        class="badge-{{ $selectedRancangan->status_rancangan === 'Disetujui' ? 'success' : ($selectedRancangan->status_rancangan === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                        {{ $selectedRancangan->status_rancangan ?? 'N/A' }}
                                                    </mark>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Nota Dinas</th>
                                                <td class="text-wrap w-75">
                                                    @if ($selectedRancangan->nota_dinas_pd)
                                                        <a href="{{ url('/view-private/rancangan/nota_dinas/' . basename($selectedRancangan->nota_dinas_pd)) }}"
                                                            target="_blank">
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
                                                <td class="text-wrap w-75">
                                                    @if ($selectedRancangan->rancangan)
                                                        <a href="{{ url('/view-private/rancangan/rancangan/' . basename($selectedRancangan->rancangan)) }}"
                                                            target="_blank">
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
                                                <td class="text-wrap w-75">
                                                    @if ($selectedRancangan->matrik)
                                                        <a href="{{ url('/view-private/rancangan/matrik/' . basename($selectedRancangan->matrik)) }}"
                                                            target="_blank">
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
                                                <td class="text-wrap w-75">
                                                    @if ($selectedRancangan->bahan_pendukung)
                                                        <a href="{{ url('/view-private/rancangan/bahan_pendukung/' . basename($selectedRancangan->bahan_pendukung)) }}"
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
                                                <td class="text-wrap w-75">
                                                    <mark
                                                        class="badge-{{ $selectedRancangan->status_berkas === 'Disetujui' ? 'success' : ($selectedRancangan->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                        {{ $selectedRancangan->status_berkas ?? 'N/A' }}
                                                    </mark>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Tanggal Berkas Disetujui</th>
                                                <td class="text-wrap w-75">
                                                    {{ $selectedRancangan->tanggal_berkas_disetujui
                                                        ? \Carbon\Carbon::parse($selectedRancangan->tanggal_berkas_disetujui)->translatedFormat('d F Y, H:i')
                                                        : 'N/A' }}
                                                </td>
                                            </tr>

                                            <tr>
                                                <th class="info-text w-25">Catatan Berkas</th>
                                                <td class="text-wrap w-75">
                                                    {{ $selectedRancangan->catatan_berkas ?? 'Tidak Ada Catatan' }}
                                                </td>
                                            </tr>
                                        </tbody>

                                    </table>
                                </div>
                            </div>
                        </div>

                        {{--  Detail Penelitian --}}
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h4 class="mb-0">Detail Penelitian</h4>
                                </div>
                                <div class="card-body modal-table mt--3">
                                    <table class="table table-sm table-borderless">
                                        <tbody>
                                            @foreach ($selectedRancangan->revisi as $revisi)
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
                                                        {{ $revisi->tanggal_revisi ? \Carbon\Carbon::parse($revisi->tanggal_revisi)->translatedFormat('d F Y, H:i') : 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Peneliti</th>
                                                    <td class="info-text ">
                                                        {{ $revisi->peneliti->nama_user ?? 'Belum Ditentukan' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Tanggal Peneliti Ditunjuk</th>
                                                    <td class="info-text">
                                                        {{ $revisi->tanggal_peneliti_ditunjuk ? \Carbon\Carbon::parse($revisi->tanggal_peneliti_ditunjuk)->translatedFormat('d F Y, H:i') : 'Belum Ditentukan' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Revisi Matrik</th>
                                                    <td class="info-text">
                                                        @if ($revisi->revisi_matrik)
                                                            <a href="{{ asset('storage/' . $revisi->revisi_matrik) }}"
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
                                                    <td class="text-wrap">
                                                        {{ $revisi->catatan_revisi ?? 'Tidak Ada Catatan' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    {{-- Select Dropdown dengan Select2 --}}
                                    <div class="card-body">
                                        {{-- Penjelasan Pilihan Peneliti --}}
                                        @if ($selectedRancangan && $selectedRancangan->revisi->first()?->id_user)
                                            {{-- Alert Peneliti Sudah Dipilih --}}
                                            <div class="alert alert-default" role="alert">
                                                <i class="bi bi-info-circle"></i>
                                                Peneliti
                                                <strong>{{ $selectedRancangan->revisi->first()?->peneliti->nama_user }}</strong>
                                                telah ditetapkan sebagai peneliti.
                                                Silahkan cek menu <strong>Peneliti Ditugaskan</strong> untuk informasi
                                                lebih lanjut.
                                            </div>
                                        @else
                                            {{-- Form Pilih Peneliti --}}
                                            <p class="info-text description">
                                                Silahkan pilih peneliti dari daftar di bawah ini untuk menugaskan
                                                rancangan yang sedang diproses.
                                            </p>
                                            <div class="form-group">
                                                <label for="peneliti">
                                                    <h4>Pilih Peneliti</h4>
                                                </label>
                                                {{-- Dropdown dengan Select2 --}}
                                                <select id="peneliti" class="form-control select2"
                                                    wire:model="selectedPeneliti">
                                                    <option hidden>Pilih Peneliti...</option>
                                                    @foreach ($listPeneliti as $peneliti)
                                                        <option value="{{ $peneliti->id }}">
                                                            {{ $peneliti->nama_user }}</option>
                                                    @endforeach
                                                </select>
                                                @error('selectedPeneliti')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        @endif
                                        <div class="text-right">
                                            {{-- Tombol Batal --}}
                                            <button class="btn btn-outline-warning" data-dismiss="modal"
                                                wire:click="resetDetail">
                                                <i class="bi bi-x-circle"></i> Batal
                                            </button>
                                            {{-- Tombol Simpan dengan wire:loading --}}
                                            <button class="btn btn-outline-default" wire:click="assignPeneliti"
                                                wire:loading.attr="disabled">
                                                <i class="bi bi-person-add" wire:loading.remove></i>
                                                <span wire:loading.remove>Pilih Peneliti</span>
                                                <i class="spinner-border spinner-border-sm text-light"
                                                    wire:loading></i>
                                                <span wire:loading>Memproses...</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @else<div class="card mb-3">
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
