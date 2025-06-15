<div>
    {{-- Search Bar --}}
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

    {{-- Table --}}
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nomor Rancangan</th>
                <th>Tentang</th>
                <th>Perangkat Daerah</th>
                <th>Status Berkas Fasilitasi</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($fasilitasiMenunggu as $fasilitasi)
                <tr>
                    <td class="wrap-text">{{ $fasilitasi->rancangan->no_rancangan }}</td>
                    <td class="wrap-text w-50">{{ $fasilitasi->rancangan->tentang }}</td>
                    <td class="wrap-text w-25">
                        {{ $fasilitasi->rancangan->user->perangkatDaerah->nama_perangkat_daerah }}
                    </td>
                    <td class="wrap-text w-25">
                        <mark
                            class="badge-{{ $fasilitasi->status_berkas_fasilitasi === 'Disetujui' ? 'success' : ($fasilitasi->status_berkas_fasilitasi === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                            {{ $fasilitasi->status_berkas_fasilitasi ?? 'N/A' }}
                        </mark>
                    </td>
                    <td>
                        <div class="dropdown position-static">
                            <button type="button" class="btn btn btn-neutral dropdown-toggle" data-toggle="dropdown"
                                aria-expanded="false">
                                <i class="bi bi-gear"></i> </button>
                            <div class="dropdown-menu shadow-lg dropdown-menu-right">
                                {{-- Tombol Lihat Detail --}}
                                <a class="dropdown-item d-flex align-items-center" href="#"
                                    wire:click.prevent="openDetailFasilitasi({{ $fasilitasi->id }})"
                                    data-target="#modalDetailFasilitasi" data-toggle="modal">
                                    <i class="bi bi-check2-square"></i> Verifikasi Berkas Fasilitasi
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Pagination --}}
    <div class="mt-3">
        {{ $fasilitasiMenunggu->links('pagination::bootstrap-4') }}
    </div>

    {{-- modal Persetujuan fasilitasi --}}
    <div wire:ignore.self class="modal fade" id="modalDetailFasilitasi" tabindex="-1" role="dialog"
        data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-xl no-style-modal">
            <div class="modal-content">

                @if ($selectedFasilitasi)
                    <div class="row mt-3">
                        <div class="col-md-6 mb-2">
                            <div class="card shadow-sm">
                                {{-- Tabel Detail --}}
                                <div class="card-header">
                                    <h4 class="mb-0">Informasi Utama</h4>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tbody>
                                            <tr>
                                                <th class="info-text w-25">Nomor</th>
                                                <td class="wrap-text w-75">
                                                    {{ $selectedFasilitasi->rancangan->no_rancangan ?? 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Jenis</th>
                                                <td class="wrap-text w-75">
                                                    <mark
                                                        class="badge-{{ $selectedFasilitasi->rancangan->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                                        {{ $selectedFasilitasi->rancangan->jenis_rancangan ?? 'N/A' }}
                                                    </mark>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Tentang</th>
                                                <td class="wrap-text w-75">
                                                    {{ $selectedFasilitasi->rancangan->tentang ?? 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Tanggal Pengajuan Fasilitasi</th>
                                                <td class="wrap-text w-75">
                                                    {{ $selectedFasilitasi->tanggal_fasilitasi ? \Carbon\Carbon::parse($selectedFasilitasi->tanggal_fasilitasi)->translatedFormat('d F Y, H:i') : 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">User Pengaju</th>
                                                <td class="wrap-text w-75">
                                                    {{ $selectedFasilitasi->rancangan->user->nama_user ?? 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Perangkat Daerah</th>
                                                <td class="wrap-text w-75">
                                                    {{ $selectedFasilitasi->rancangan->user->perangkatDaerah->nama_perangkat_daerah ?? 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Status Berkas Fasiliatsi</th>
                                                <td class="wrap-text w-75">
                                                    <mark
                                                        class="badge-{{ $selectedFasilitasi->status_berkas_fasilitasi === 'Disetujui' ? 'success' : ($selectedFasilitasi->status_berkas_fasilitasi === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                        {{ $selectedFasilitasi->status_berkas_fasilitasi ?? 'N/A' }}
                                                    </mark>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Status Validasi Fasilitasi</th>
                                                <td class="wrap-text w-75">
                                                    <mark
                                                        class="badge-{{ $selectedFasilitasi->status_validasi_fasilitasi === 'Diterima'
                                                            ? 'success'
                                                            : ($selectedFasilitasi->status_validasi_fasilitasi === 'Ditolak'
                                                                ? 'danger'
                                                                : ($selectedFasilitasi->status_validasi_fasilitasi === 'Belum Tahap Validasi'
                                                                    ? 'danger'
                                                                    : 'warning')) }} badge-pill">
                                                        {{ $selectedFasilitasi->status_validasi_fasilitasi ?? 'N/A' }}
                                                    </mark>

                                                </td>
                                            </tr>

                                            <tr>
                                                <th class="info-text w-25">File Rancangan Fasilitasi</th>
                                                <td class="wrap-text w-75">
                                                    @if ($selectedFasilitasi->file_rancangan)
                                                        <a href="{{ url('/view-private/fasilitasi/rancangan/' . basename($selectedFasilitasi->file_rancangan)) }}"
                                                            target="_blank" class="d-flex align-items-center">
                                                            <i class="bi bi-file-earmark-pdf mr-2 text-primary"></i>
                                                            Lihat
                                                            File
                                                        </a>
                                                    @else
                                                        <span class="text-muted">Tidak Ada File</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Catatan Tahap Rancangan</th>
                                                <td class="wrap-text w-75">
                                                    {{ $selectedFasilitasi->rancangan->revisi->last()->catatan_revisi ?? 'Tidak ada Catatan' }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{--  Persetujuan --}}
                        <div class="col-md-6 mb-2">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h4 class="mb-0">Persetujuan Fasilitasi</h4>
                                </div>

                                {{-- Persetujuan --}}
                                <div class="card-body">
                                    {{--  Verifikasi Persetujuan  --}}
                                    {{-- Persetujuan --}}
                                    <div class="form-group">
                                        <label>Pilih Status Persetujuan</label>
                                        <select class="form-control" wire:model="statusBerkas">
                                            <option hidden>Pilih Status</option>
                                            <option value="Disetujui">Disetujui</option>
                                            <option value="Ditolak">Ditolak</option>
                                        </select>
                                        @error('statusBerkas')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    {{-- Catatan Persetujuan --}}
                                    <div class="form-group">
                                        <label>
                                            Catatan Persetujuan
                                            <small class="text-danger"> wajib</small>
                                        </label>
                                        <textarea class="form-control" wire:model.defer="catatan" rows="3" placeholder="Tambahkan catatan..."></textarea>
                                        @error('catatan')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="alert alert-warning mb-0" role="alert"
                                        style="flex: 1; text-align: center;">
                                        <strong> Berkas Rancangan
                                            {{ $selectedFasilitasi->status_berkas }}!</strong>
                                        Periksa dan Lakukan Verifikasi
                                    </div>


                                    {{-- Tombol --}}
                                    <div class="d-flex justify-content-end mt-4">

                                        {{-- Tombol Verifikasi --}}
                                        <button type="button" class="btn btn-outline-warning" data-dismiss="modal"
                                            wire:click="resetForm"><i class="bi bi-backspace"></i> Batalkan</button>
                                        <button class="btn btn-outline-success" wire:click="updateStatus"
                                            wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="updateStatus"><i
                                                    class="bi bi-check-circle"></i> Verifikasi</span>
                                            <span wire:loading wire:target="updateStatus"><i
                                                    class="bi bi-hourglass-split"></i> Memproses...</span>
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
