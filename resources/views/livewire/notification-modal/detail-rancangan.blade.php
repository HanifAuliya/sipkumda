<div>
    <div wire:ignore.self class="modal fade" id="modalDetailRancangan" tabindex="-1" role="dialog"
        aria-labelledby="modalDetailRancanganLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-xl no-style-modal" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    {{-- Header --}}
                    @if ($rancangan)
                        <div class="card mb-3">
                            <div class="modal-header">
                                {{-- Teks Detail Rancangan --}}
                                <h5 class="modal-title mb-0" id="detailModalLabel">
                                    Detail Rancangan: {{ $rancangan->no_rancangan ?? 'N/A' }}
                                </h5>

                                {{-- Tombol --}}
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>

                        </div>
                        {{-- Informasi Utama --}}
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0 font-weight-bold">Informasi Utama</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tbody>
                                        <tr>
                                            <th>Nomor</th>
                                            <td class="wrap-text-td-70">
                                                {{ $rancangan->no_rancangan ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Jenis</th>
                                            <td class="wrap-text-td-70 ">
                                                @if ($rancangan && $rancangan->jenis_rancangan)
                                                    <mark
                                                        class="badge-{{ $rancangan->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                                        {{ $rancangan->jenis_rancangan }}
                                                    </mark>
                                                @else
                                                    <span class="text-danger">Data tidak tersedia</span>
                                                @endif
                                        </tr>
                                        <tr>
                                            <th>Tentang</th>
                                            <td class="wrap-text-td-70 ">
                                                {{ $rancangan->tentang ?? 'N/A' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal Pengajuan</th>
                                            <td>
                                                {{ $rancangan->tanggal_pengajuan
                                                    ? \Carbon\Carbon::parse($rancangan->tanggal_pengajuan)->translatedFormat('d F Y, H:i')
                                                    : 'N/A' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>User Pengaju</th>
                                            <td class="wrap-text-td-70 ">
                                                {{ $rancangan->user->nama_user ?? 'N/A' }}
                                            </td>
                                        </tr>

                                        <tr>
                                            <th>Perangkat Daerah</th>
                                            <td class="wrap-text-td-70 ">
                                                {{ $rancangan->user->perangkatDaerah->nama_perangkat_daerah ?? 'N/A' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Status Rancangan</th>
                                            @if ($rancangan && $rancangan->status_rancangan)
                                                <td class="wrap-text-td-70 ">
                                                    <mark
                                                        class="badge-{{ $rancangan->status_rancangan === 'Disetujui'
                                                            ? 'success'
                                                            : ($rancangan->status_rancangan === 'Ditolak'
                                                                ? 'danger'
                                                                : 'warning') }} badge-pill">
                                                        {{ $rancangan->status_rancangan }}
                                                    </mark>
                                                </td>
                                            @else
                                                <td class="wrap-text-td-70 ">
                                                    <span class="badge badge-secondary">N/A</span>
                                                </td>
                                            @endif
                                        </tr>
                                        <tr>
                                            <th>Tanggal Rancangan disetujui</th>
                                            <td>
                                                {{ $rancangan->tanggal_rancangan_disetujui
                                                    ? \Carbon\Carbon::parse($rancangan->tanggal_rancangan_disetujui)->translatedFormat('d F Y, H:i')
                                                    : 'N/A' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- File Rancangan --}}
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0 font-weight-bold">File Rancangan</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tbody>
                                        <tr>
                                            <th>Status Berkas</th>
                                            @if ($rancangan && $rancangan->status_berkas)
                                                <td class="wrap-text-td-70 ">
                                                    <mark
                                                        class="badge-{{ $rancangan->status_berkas === 'Disetujui' ? 'success' : ($rancangan->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                        {{ $rancangan->status_berkas }}
                                                    </mark>
                                                </td>
                                            @else
                                                <td class="wrap-text-td-70 ">
                                                    <span class="badge badge-secondary">N/A</span>
                                                </td>
                                            @endif
                                        </tr>
                                        <tr>
                                            <th>
                                                Tanggal Berkas Disetujui
                                            </th>
                                            <td>
                                                {{ $rancangan->tanggal_berkas_disetujui
                                                    ? \Carbon\Carbon::parse($rancangan->tanggal_berkas_disetujui)->translatedFormat('d F Y, H:i')
                                                    : 'N/A' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Nota Dinas</th>
                                            <td class="wrap-text-td-70">
                                                @if (isset($rancangan->nota_dinas_pd))
                                                    <a href="{{ url('/view-private/rancangan/nota_dinas/' . basename($rancangan->nota_dinas_pd)) }}"
                                                        target="_blank" class="d-flex align-items-center">
                                                        <i class="bi bi-file-earmark-pdf mr-2"
                                                            style="font-size: 1.5rem; color: #ffc107;"></i>
                                                        <span>lihat Nota</span>
                                                    </a>
                                                @else
                                                    <span style="color: #6c757d;">Data Tidak Ada</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>File Rancangan</th>
                                            <td class="wrap-text-td-70">
                                                @if (isset($rancangan->rancangan))
                                                    <a href="{{ url('/view-private/rancangan/rancangan/' . basename($rancangan->rancangan)) }}"
                                                        target="_blank" class="d-flex align-items-center">
                                                        <i class="bi bi-file-earmark-pdf mr-2"
                                                            style="font-size: 1.5rem; color: #007bff;"></i>
                                                        <span>lihat Rancangan</span>
                                                    </a>
                                                @else
                                                    <span style="color: #6c757d;">Data Tidak Ada</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Matrik</th>
                                            <td class="wrap-text-td-70">
                                                @if (isset($rancangan->matrik))
                                                    <a href="{{ url('/view-private/rancangan/matrik/' . basename($rancangan->matrik)) }}"
                                                        target="_blank" class="d-flex align-items-center">
                                                        <i class="bi bi-file-earmark-pdf mr-2"
                                                            style="font-size: 1.5rem; color: #28a745;"></i>
                                                        <span>lihat Matrik</span>
                                                    </a>
                                                @else
                                                    <span style="color: #6c757d;">Data Tidak Ada</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Bahan Pendukung</th>
                                            <td class="wrap-text-td-70">
                                                @if (isset($rancangan->bahan_pendukung))
                                                    <a href="{{ url('/view-private/rancangan/bahan_pendukung/' . basename($rancangan->bahan_pendukung)) }}"
                                                        target="_blank" class="d-flex align-items-center">
                                                        <i class="bi bi-file-earmark-pdf mr-2"
                                                            style="font-size: 1.5rem; color: #dc3545;"></i>
                                                        <span>lihat Bahan</span>
                                                    </a>
                                                @else
                                                    <span style="color: #6c757d;">Data Tidak Ada</span>
                                                @endif
                                            </td>
                                        </tr>


                                        <tr>
                                            <th>Catatan Berkas</th>
                                            <td class="wrap-text-td-70 ">
                                                {{ $rancangan->catatan_berkas ?? 'Tidak Ada Catatan' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>


                        {{-- Revisi --}}
                        <div class="card mb-1">
                            <div class="card-header">
                                <h5 class="mb-0 font-weight-bold">Revisi</h5>
                            </div>
                            <div class="card-body">
                                @if ($rancangan && $rancangan->revisi->isNotEmpty())
                                    @foreach ($rancangan->revisi as $revisi)
                                        <table class="table table-borderless mb-4">
                                            <tbody>
                                                <tr>
                                                    <th>Status Revisi</th>
                                                    <td class="wrap-text-td-70 ">
                                                        <mark
                                                            class="badge-{{ $revisi->status_revisi === 'Direvisi'
                                                                ? 'success'
                                                                : ($revisi->status_revisi === 'Menunggu Peneliti'
                                                                    ? 'info text-default'
                                                                    : ($revisi->status_revisi === 'Proses Revisi'
                                                                        ? 'warning'
                                                                        : ($revisi->status_revisi === 'Belum Tahap Revisi'
                                                                            ? 'danger'
                                                                            : 'secondary'))) }} badge-pill">
                                                            {{ $revisi->status_revisi }}
                                                        </mark>
                                                    </td>

                                                </tr>
                                                <tr>
                                                    <th>Status Validasi Revisi</th>
                                                    <td class="wrap-text-td-70">
                                                        <mark
                                                            class="badge-{{ $revisi->status_validasi === 'Diterima'
                                                                ? 'success'
                                                                : ($revisi->status_validasi === 'Ditolak'
                                                                    ? 'danger'
                                                                    : ($revisi->status_validasi === 'Belum Tahap Validasi'
                                                                        ? 'danger'
                                                                        : 'warning')) }} badge-pill">
                                                            {{ $revisi->status_validasi }}
                                                        </mark>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Tanggal Revisi</th>
                                                    <td class="wrap-text-td-70">
                                                        {{ $revisi->tanggal_revisi ? \Carbon\Carbon::parse($revisi->tanggal_revisi)->translatedFormat('d F Y, H:i') : 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Tanggal Validasi</th>
                                                    <td class="wrap-text-td-70">
                                                        {{ $revisi->tanggal_validasi ? \Carbon\Carbon::parse($revisi->tanggal_validasi)->translatedFormat('d F Y, H:i') : 'N/A' }}
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <th>Peneliti</th>
                                                    <td class="wrap-text-td-70 ">
                                                        {{ $revisi->peneliti->nama_user ?? 'Belum Ditentukan' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Tanggal Peneliti Ditunjuk</th>
                                                    <td>
                                                        {{ $revisi->tanggal_peneliti_ditunjuk
                                                            ? \Carbon\Carbon::parse($revisi->tanggal_peneliti_ditunjuk)->translatedFormat('d F Y H:i')
                                                            : 'N/A' }}
                                                    </td>
                                                </tr>
                                                @if (!auth()->user()->hasRole('Perangkat Daerah'))
                                                    <tr>
                                                        <th>Revisi Rancangan</th>
                                                        <td class="wrap-text-td-70">
                                                            @if (isset($revisi->revisi_rancangan))
                                                                <a href="{{ url('/view-private/revisi/rancangan/' . basename($revisi->revisi_rancangan)) }}"
                                                                    target="_blank" class="d-flex align-items-center">
                                                                    <i class="bi bi-file-earmark-pdf mr-2"
                                                                        style="font-size: 1.5rem; color: #007bff;"></i>
                                                                    <span>Lihat Revisi</span>
                                                                </a>
                                                            @else
                                                                <span style="color: #6c757d;">Data Tidak Ada</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Revisi Matrik</th>
                                                        <td class="wrap-text-td-70">
                                                            @if (isset($revisi->revisi_matrik))
                                                                <a href="{{ url('/view-private/revisi/matrik/' . basename($revisi->revisi_matrik)) }}"
                                                                    target="_blank" class="d-flex align-items-center">
                                                                    <i class="bi bi-file-earmark-pdf mr-2"
                                                                        style="font-size: 1.5rem; color: #28a745;"></i>
                                                                    <span>Lihat Matrik Revisi</span>
                                                                </a>
                                                            @else
                                                                <span style="color: #6c757d;">Data Tidak Ada</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endif
                                                <tr>
                                                    <th>Catatan Revisi</th>
                                                    <td class="wrap-text-td-70 ">
                                                        {{ $revisi->catatan_revisi ?? 'Tidak Ada Catatan' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    @endforeach
                                @else
                                    <p class="text-center text-muted">Belum ada revisi.</p>
                                @endif
                            </div>
                        </div>
                    @else
                        {{-- Spinner Loading --}}
                        <div class="card mb-3">
                            <div class="d-flex justify-content-center align-items-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-3 ml-3 text-muted">Sedang memuat data, harap tunggu...</p>
                            </div>
                        </div>
                    @endif
                    <div class="card mt-4">
                        <button type="button" class="btn btn-neutral" data-dismiss="modal">Tutup Detail
                            Rancangan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
