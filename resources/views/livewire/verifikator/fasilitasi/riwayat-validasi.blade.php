<div>
    {{-- Search Bar --}}
    <div class="form-group">
        <input type="text" class="form-control" placeholder="Cari nomor rancangan atau tentang..." wire:model="search">
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="thead-light">
                <tr>
                    <th>Nomor Rancangan</th>
                    <th>Tentang</th>
                    <th>Status Berkas Fasilitasi</th>
                    <th>Status Validasi Fasilitasi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($fasilitasiRiwayat as  $fasilitasi)
                    <tr>
                        <td class="wrap-text">{{ $fasilitasi->rancangan->no_rancangan }}</td>
                        <td class="wrap-text w-50">{{ $fasilitasi->rancangan->tentang }}</td>
                        <td>
                            <mark
                                class="badge-{{ $fasilitasi->status_berkas_fasilitasi === 'Disetujui' ? 'success' : 'danger' }} badge-pill">
                                {{ $fasilitasi->status_berkas_fasilitasi ?? 'N/A' }}
                            </mark>
                        </td>
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
                                wire:click.prevent="openModalRiwayatFasilitasi({{ $fasilitasi->id }})"
                                data-target="#modalRiwayatFasilitasi" data-toggle="modal">
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
        {{ $fasilitasiRiwayat->links('pagination::bootstrap-4') }}
    </div>
    {{-- modal detail fasilitasi --}}
    <div wire:ignore.self class="modal fade" id="modalRiwayatFasilitasi" tabindex="-1" role="dialog"
        data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Fasilitasi</h5>

                </div>
                <div class="modal-body">
                    @if ($selectedFasilitasi)
                        {{-- Tabel Detail --}}
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
                                                <i class="bi bi-file-earmark-pdf mr-2 text-primary"></i> Lihat File
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
                                <tr>
                                    <th class="info-text w-25">Catatan Validasi</th>
                                    <td class="wrap-text w-75">
                                        {{ $selectedFasilitasi->catatan_validasi_fasilitasi ?? 'Tidak Ada Catatan' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        @if ($selectedFasilitasi->status_validasi_fasilitasi === 'Diterima')
                            <div class="alert alert-primary alert-dismissible fade show mb-2 mt-2" role="alert">
                                <span class="alert-icon"><i class="bi bi-send-exclamation"></i></span>
                                <span class="alert-text">
                                    Fasilitasi <strong> Anda Setujui!</strong>
                                    Hubungi admin untuk menggenerate Nota Dinas!
                                </span>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif
                        @if ($selectedFasilitasi->status_validasi_fasilitasi === 'Ditolak')
                            <div class="alert alert-warning alert-dismissible fade show mb-2 mt-2" role="alert">
                                <span class="alert-icon"><i class="bi bi-send-exclamation"></i></span>
                                <span class="alert-text">
                                    Fasilitasi <strong> Anda Tolak!</strong>
                                    Peneliti Akan mengoreksi file fasilitasi!
                                </span>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif
                    @else
                        {{-- Spinner Loading --}}

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
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-warning" data-dismiss="modal"
                        wire:click="resetData">Tutup Detail</button>
                </div>
            </div>
        </div>
    </div>
</div>
