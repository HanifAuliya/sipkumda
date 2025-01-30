@section('title', 'Riwayat Pengajuan Fasilitasi')

@section('manual')
    <div class="card  mb--2">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h3 class="mb-0">Panduan Verifikasi Berkas</h3>
                <p class="description">
                    © Hak Cipta Bagian Hukum Sekretariat Daerah Kabupaten Hulu
                    Sungai Tengah.
                </p>
            </div>

            {{-- Tombol untuk Verifikator --}}
            <button class="btn btn-outline-warning">
                <i class="bi bi-info-circle"></i> Panduan
            </button>
        </div>
    </div>
@endsection
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
            @forelse ($riwayatFasilitasi as $fasilitasi)
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
                            <div class="dropdown-menu shadow-lg">
                                {{-- Tombol Lihat Detail --}}
                                <a class="dropdown-item d-flex align-items-center" href="#"
                                    wire:click.prevent="openDetailRiwayatFasilitasi({{ $fasilitasi->id }})"
                                    data-target="#modalDetailRiwayatFasilitasi" data-toggle="modal">
                                    <i class="bi bi-info-circle"></i> Lihat Detail Fasilitasi
                                </a>
                                {{-- Upload Ulang Berkas --}}
                                <a class="dropdown-item d-flex align-items-center text-default"
                                    wire:click.prevent="openUploadRevisi({{ $fasilitasi->id }})">
                                    <i class="bi bi-upload text-success"></i> Upload Ulang Fasilitasi
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Pagination --}}
    <div class="mt-3">
        {{ $riwayatFasilitasi->links('pagination::bootstrap-4') }}
    </div>

    {{-- modal detail fasilitasi --}}
    <div wire:ignore.self class="modal fade" id="modalDetailRiwayatFasilitasi" tabindex="-1" role="dialog"
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
                            </tbody>
                        </table>

                        <div class="alert alert-success alert-dismissible fade show mb-2 mt-2" role="alert">
                            <span class="alert-icon"><i class="bi bi-send-check"></i></span>
                            <span class="alert-text">
                                Rancangan <strong> Anda Setujui!</strong>
                                Tunggu Validasi dari Verifikator!
                            </span>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
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
                        wire:click="resetDetail">Tutup Detail</button>
                </div>
            </div>
        </div>
    </div>
</div>
