<div>
    {{-- Search Bar --}}
    <div class="form-group">
        <input type="text" class="form-control" placeholder="Cari nomor rancangan atau tentang..." wire:model="search">
    </div>

    {{-- Table --}}
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nomor Rancangan</th>
                <th>Tentang</th>
                <th>Status Berkas Fasilitasi</th>
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
                        <mark
                            class="badge-{{ $fasilitasi->status_berkas_fasilitasi === 'Disetujui' ? 'success' : ($fasilitasi->status_berkas_fasilitasi === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                            {{ $fasilitasi->status_berkas_fasilitasi ?? 'N/A' }}
                        </mark>
                    </td>
                    <td>
                        <mark
                            class="badge-{{ $fasilitasi->status_validasi_fasilitasi === 'Diterima'
                                ? 'success'
                                : ($fasilitasi->status_validasi_fasilitasi === 'Ditolak'
                                    ? 'danger'
                                    : ($fasilitasi->status_validasi_fasilitasi === 'Belum Tahap Validasi'
                                        ? 'danger'
                                        : 'warning')) }} badge-pill">
                            {{ $fasilitasi->status_validasi_fasilitasi ?? 'N/A' }}
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
                                {{-- Upload Ulang Berkas --}}
                                <a class="dropdown-item text-danger d-flex align-items-center"
                                    onclick="confirmDeleteValidasi({{ $fasilitasi->id }})">
                                    <i class="bi bi-arrow-counterclockwise"></i> Hapus Pengajuan Fasilitasi
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
    {{-- modal detail fasilitasi --}}
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
                                                        class="badge-{{ $fasilitasi->status_validasi_fasilitasi === 'Diterima'
                                                            ? 'success'
                                                            : ($fasilitasi->status_validasi_fasilitasi === 'Ditolak'
                                                                ? 'danger'
                                                                : ($fasilitasi->status_validasi_fasilitasi === 'Belum Tahap Validasi'
                                                                    ? 'danger'
                                                                    : 'warning')) }} badge-pill">
                                                        {{ $fasilitasi->status_validasi_fasilitasi ?? 'N/A' }}
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
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{--  Persetujuan --}}
                        <div class="col-md-6 mb-2">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h4 class="mb-0">Persetujuan</h4>
                                </div>

                                {{-- Persetujuan --}}
                                <div class="card-body">
                                    {{--  Verifikasi Persetujuan  --}}
                                    {{-- Informasi --}}
                                    <p class="description info-text mb-3 info-text">
                                        Pilih status persetujuan untuk rancangan ini. Tambahkan catatan
                                        untuk memberikan masukan atau alasan penolakan.
                                    </p>
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
                                        <label>Catatan Persetujuan</label>
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
                                            wire:click="resetForm">Batalkan</button>
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
