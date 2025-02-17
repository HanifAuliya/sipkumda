<div>
    {{-- Search Bar --}}
    {{-- Searching dan PerPage --}}
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

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Nomor Rancangan</th>
                    <th>Tentang</th>
                    <th>Perangkat Daerah</th>
                    <th>Status Validasi Fasilitasi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($fasilitasiMenunggu as $fasilitasi)
                    <tr>
                        <td class="wrap-text">{{ $fasilitasi->rancangan->no_rancangan }}</td>
                        <td class="wrap-text w-50">{{ $fasilitasi->rancangan->tentang }}</td>
                        <td class="wrap-text w-25">
                            {{ $fasilitasi->rancangan->user->perangkatDaerah->nama_perangkat_daerah }}</td>
                        <td>
                            <mark
                                class="badge-{{ $fasilitasi->status_validasi_fasilitasi === 'Diterima'
                                    ? 'success'
                                    : ($fasilitasi->status_validasi_fasilitasi === 'Ditolak'
                                        ? 'danger'
                                        : ($fasilitasi->status_validasi_fasilitasi === 'Menunggu Validasi'
                                            ? 'warning'
                                            : 'dark')) }} badge-pill">
                                {{ $fasilitasi->status_validasi_fasilitasi ?? 'N/A' }}
                            </mark>
                        </td>
                        <td class="w-25">
                            {{-- Tombol Verifikasi --}}
                            <button class="btn btn-neutral" href="#"
                                wire:click.prevent="openModalValidasiFasilitasi({{ $fasilitasi->id }})"
                                data-target="#modalValidasiFasilitasi" data-toggle="modal">
                                <i class="bi bi-check2-square"></i> Verifikasi Berkas
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada data</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-3">
        {{ $fasilitasiMenunggu->links('pagination::bootstrap-4') }}
    </div>

    {{-- modal detail fasilitasi --}}
    <div wire:ignore.self class="modal fade" id="modalValidasiFasilitasi" tabindex="-1" role="dialog"
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
                                                        {{ $selectedFasilitasi->tanggal_fasilitasi ? \Carbon\Carbon::parse($selectedFasilitasi->tanggal_fasilitasi)->translatedFormat('d F Y') : 'N/A' }}
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
                                                                        ? 'dark'
                                                                        : 'warning')) }} badge-pill">
                                                            {{ $selectedFasilitasi->status_validasi_fasilitasi ?? 'N/A' }}
                                                        </mark>

                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Tanggal Persetujuan Fasilitasi</th>
                                                    <td class="wrap-text w-75">
                                                        {{ $selectedFasilitasi->tanggal_persetujuan_berkas ? \Carbon\Carbon::parse($selectedFasilitasi->tanggal_persetujuan_berkas)->translatedFormat('d F Y') : 'Belum di Verifikasi' }}
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <th class="info-text w-25">File Rancangan Fasilitasi</th>
                                                    <td class="wrap-text w-75">
                                                        @if ($selectedFasilitasi->file_rancangan)
                                                            <a href="{{ url('/view-private/fasilitasi/rancangan/' . basename($selectedFasilitasi->file_rancangan)) }}"
                                                                target="_blank" class="d-flex align-items-center">
                                                                <i class="bi bi-file-earmark-pdf mr-2 text-primary"></i>
                                                                Lihat File
                                                            </a>
                                                        @else
                                                            <span class="text-muted">Tidak Ada File</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text w-25">Catatan Fasilitasi</th>
                                                    <td class="wrap-text w-75">
                                                        {{ $selectedFasilitasi->catatan_persetujuan_fasilitasi ?? 'Tidak Ada Catatan' }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>

                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Validasi Persetujuan --}}
                        <div class="col-md-6 mb-2">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h4 class="mb-0">Validasi Fasilitasi</h4>
                                </div>
                                <div class="card-body">
                                    {{-- Pilih Status Validasi --}}
                                    <div class="form-group">
                                        <label>Pilih Status Validasi</label>
                                        <select class="form-control" wire:model="statusValidasi">
                                            <option hidden>Pilih Status</option>
                                            <option value="Diterima">Diterima</option>
                                            <option value="Ditolak">Ditolak</option>
                                        </select>
                                        @error('statusValidasi')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    {{-- Catatan Validasi --}}
                                    <div class="form-group">
                                        <label>Catatan Validasi</label>
                                        <textarea class="form-control" wire:model.defer="catatanValidasi" rows="3" placeholder="Tambahkan catatan..."></textarea>
                                        @error('catatanValidasi')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    {{-- Tombol Aksi --}}
                                    <div class="d-flex justify-content-end mt-3">
                                        {{-- Tombol Batalkan --}}
                                        <button class="btn btn-outline-warning" data-dismiss="modal"
                                            wire:click="resetData">
                                            <i class="bi bi-x-circle"></i> Batalkan
                                        </button>

                                        {{-- Tombol Validasi dengan Loading --}}
                                        <button class="btn btn-outline-success ml-2" wire:click="validasiFasilitasi"
                                            wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="validasiFasilitasi">
                                                <i class="bi bi-check-circle"></i> Validasi
                                            </span>
                                            <span wire:loading wire:target="validasiFasilitasi">
                                                <i class="spinner-border spinner-border-sm"></i> Memproses...
                                            </span>
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
