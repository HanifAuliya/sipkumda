<div>
    <div wire:ignore.self class="modal fade" id="modalDetailRancangan" tabindex="-1" role="dialog"
        aria-labelledby="modalDetailRancanganLabel" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-xl no-style-modal" role="document">
            <div class="modal-content ">
                {{-- Body Modal untuk header --}}

                {{-- Header --}}
                @if ($rancangan)
                    <div class="row mt-3">
                        {{-- Informasi Utama --}}
                        <div class="col-md-6 mb-2">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="mb-0 ">Informasi Utama</h5>
                                </div>
                                <div class="card-body table-responsive modal-table mt--3">
                                    <table class="table table-sm table-borderless">
                                        <tbody>
                                            <tr>
                                                <th class="info-text w-25">Nomor</th>
                                                <td class="wrap-text w-75">
                                                    {{ $rancangan->no_rancangan ?? 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Jenis</th>
                                                <td class="wrap-text w-75">
                                                    @if ($rancangan && $rancangan->jenis_rancangan)
                                                        <mark
                                                            class="badge-{{ $rancangan->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                                            {{ $rancangan->jenis_rancangan }}
                                                        </mark>
                                                    @else
                                                        <span class="text-danger">Data tidak tersedia</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Tentang</th>
                                                <td class="wrap-text w-75">
                                                    {{ $rancangan->tentang ?? 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Tanggal Pengajuan</th>
                                                <td class="wrap-text w-75">
                                                    {{ $rancangan->tanggal_pengajuan
                                                        ? \Carbon\Carbon::parse($rancangan->tanggal_pengajuan)->translatedFormat('d F Y, H:i')
                                                        : 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">User Pengaju</th>
                                                <td class="wrap-text w-75">
                                                    {{ $rancangan->user->nama_user ?? 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Perangkat Daerah</th>
                                                <td class="wrap-text w-75">
                                                    {{ $rancangan->user->perangkatDaerah->nama_perangkat_daerah ?? 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Nomor Nota</th>
                                                <td class="wrap-text w-75">
                                                    {{ $rancangan->nomor_nota ?? 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Tanggal Nota</th>
                                                <td class="wrap-text w-75">
                                                    {{ $rancangan->tanggal_nota
                                                        ? \Carbon\Carbon::parse($rancangan->tanggal_nota)->translatedFormat('d F Y')
                                                        : 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Status Rancangan</th>
                                                <td class="wrap-text w-75">
                                                    @if ($rancangan && $rancangan->status_rancangan)
                                                        <mark
                                                            class="badge-{{ $rancangan->status_rancangan === 'Disetujui'
                                                                ? 'success'
                                                                : ($rancangan->status_rancangan === 'Ditolak'
                                                                    ? 'danger'
                                                                    : 'warning') }} badge-pill">
                                                            {{ $rancangan->status_rancangan }}
                                                        </mark>
                                                    @else
                                                        <span class="badge badge-secondary">N/A</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Tanggal Rancangan disetujui</th>
                                                <td class="wrap-text w-75">
                                                    {{ $rancangan->tanggal_rancangan_disetujui
                                                        ? \Carbon\Carbon::parse($rancangan->tanggal_rancangan_disetujui)->translatedFormat('d F Y, H:i')
                                                        : 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Status Berkas</th>
                                                @if ($rancangan && $rancangan->status_berkas)
                                                    <td class="wrap-text w-75">
                                                        <mark
                                                            class="badge-{{ $rancangan->status_berkas === 'Disetujui' ? 'success' : ($rancangan->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                            {{ $rancangan->status_berkas }}
                                                        </mark>
                                                    </td>
                                                @else
                                                    <td class="wrap-text w-75">
                                                        <span class="badge badge-secondary">N/A</span>
                                                    </td>
                                                @endif
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Tanggal Berkas Disetujui</th>
                                                <td class="wrap-text w-75">
                                                    {{ $rancangan->tanggal_berkas_disetujui
                                                        ? \Carbon\Carbon::parse($rancangan->tanggal_berkas_disetujui)->translatedFormat('d F Y, H:i')
                                                        : 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Nota Dinas</th>
                                                <td class="wrap-text w-75">
                                                    @if (isset($rancangan->nota_dinas_pd))
                                                        <a href="{{ url('/view-private/rancangan/nota_dinas/' . basename($rancangan->nota_dinas_pd)) }}"
                                                            target="_blank" class="d-flex align-items-center">
                                                            <i class="bi bi-file-earmark-pdf mr-2 text-warning"></i>
                                                            <span>Lihat Nota</span>
                                                        </a>
                                                    @else
                                                        <span style="color: #6c757d;">Data Tidak Ada</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">File Rancangan</th>
                                                <td class="wrap-text w-75">
                                                    @if (isset($rancangan->rancangan))
                                                        <a href="{{ url('/view-private/rancangan/rancangan/' . basename($rancangan->rancangan)) }}"
                                                            target="_blank" class="d-flex align-items-center">
                                                            <i class="bi bi-file-earmark-word mr-2 text-primary"></i>
                                                            <span>Lihat Rancangan</span>
                                                        </a>
                                                    @else
                                                        <span style="color: #6c757d;">Data Tidak Ada</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Matrik</th>
                                                <td class="wrap-text w-75">
                                                    @if (isset($rancangan->matrik))
                                                        <a href="{{ url('/view-private/rancangan/matrik/' . basename($rancangan->matrik)) }}"
                                                            target="_blank" class="d-flex align-items-center">
                                                            <i class="bi bi-file-earmark-word mr-2 text-success"></i>
                                                            <span>Lihat Matrik</span>
                                                        </a>
                                                    @else
                                                        <span style="color: #6c757d;">Data Tidak Ada</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Bahan Pendukung</th>
                                                <td class="wrap-text w-75">
                                                    @if (isset($rancangan->bahan_pendukung))
                                                        <a href="{{ url('/view-private/rancangan/bahan_pendukung/' . basename($rancangan->bahan_pendukung)) }}"
                                                            target="_blank" class="d-flex align-items-center">
                                                            <i class="bi bi-file-earmark-pdf mr-2 text-danger"></i>
                                                            <span>Lihat Bahan</span>
                                                        </a>
                                                    @else
                                                        <span style="color: #6c757d;">Data Tidak Ada</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text w-25">Catatan Berkas</th>
                                                <td class="wrap-text w-75">
                                                    {{ $rancangan->catatan_berkas ?? 'Tidak Ada Catatan' }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        {{-- Informasi Utama --}}
                        <div class="col-md-6 mb-2">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="mb-0 ">Hasil Penelitian</h5>
                                </div>
                                <div class="card-body table-responsive modal-table mt--3">
                                    @if ($rancangan && $rancangan->revisi->isNotEmpty())
                                        @foreach ($rancangan->revisi as $revisi)
                                            <table class="table table-sm table-borderless">
                                                <tbody>
                                                    <tr>
                                                        <th class="info-text w-25">Status Revisi</th>
                                                        <td class="wrap-text w-75">
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
                                                        <th class="info-text w-25">Status Validasi Revisi</th>
                                                        <td class="wrap-text w-75">
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
                                                        <th class="info-text w-25">Tanggal Revisi</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $revisi->tanggal_revisi
                                                                ? \Carbon\Carbon::parse($revisi->tanggal_revisi)->translatedFormat('d F Y, H:i')
                                                                : 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Tanggal Validasi</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $revisi->tanggal_validasi
                                                                ? \Carbon\Carbon::parse($revisi->tanggal_validasi)->translatedFormat('d F Y, H:i')
                                                                : 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Peneliti</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $revisi->peneliti->nama_user ?? 'Belum Ditentukan' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Tanggal Peneliti Ditunjuk</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $revisi->tanggal_peneliti_ditunjuk
                                                                ? \Carbon\Carbon::parse($revisi->tanggal_peneliti_ditunjuk)->translatedFormat('d F Y H:i')
                                                                : 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    @if (!auth()->user()->hasRole('Perangkat Daerah'))
                                                        <tr>
                                                            <th class="info-text w-25">Revisi Matrik</th>
                                                            <td class="wrap-text w-75">
                                                                @if (isset($revisi->revisi_matrik))
                                                                    <a href="{{ url('/view-private/revisi/matrik/' . basename($revisi->revisi_matrik)) }}"
                                                                        target="_blank"
                                                                        class="d-flex align-items-center">
                                                                        <i
                                                                            class="bi bi-file-earmark-word mr-2 text-success"></i>
                                                                        <span>Lihat Matrik Revisi</span>
                                                                    </a>
                                                                @else
                                                                    <span style="color: #6c757d;">Data Tidak Ada</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endif
                                                    <tr>
                                                        <th class="info-text w-25">Catatan Revisi</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $revisi->catatan_revisi ?? 'Tidak Ada Catatan' }}
                                                        </td>
                                                    </tr>
                                                </tbody>

                                            </table>
                                        @endforeach
                                    @else
                                        <p class="text-center text-muted">Belum ada revisi.</p>
                                    @endif
                                    <div class="d-flex justify-content-end mt-4">
                                        {{-- Tombol Tutup --}}
                                        <button type="button" class="btn btn-outline-warning mr-3"
                                            data-dismiss="modal"></i> tutup
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
</div>
