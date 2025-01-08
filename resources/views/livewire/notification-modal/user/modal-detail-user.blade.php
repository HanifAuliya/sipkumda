<div>
    <div wire:ignore.self class="modal fade" id="ModalDetailUser" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl no-style-modal" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    {{-- Header --}}
                    @if ($rancangan)
                        <div class="card mb-3">
                            <div class="modal-header">
                                {{-- Teks Detail Rancangan --}}
                                <h5 class="modal-title mb-0" id="detailModalLabelPengajuan">
                                    Detail Pengajuan: {{ $rancangan->no_rancangan ?? 'N/A' }}
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
                                            <td class="info-text">{{ $rancangan->no_rancangan ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Jenis</th>
                                            <td>
                                                <mark
                                                    class="info-text badge-{{ $rancangan->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                                    {{ $rancangan->jenis_rancangan ?? 'N/A' }}
                                                </mark>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Tentang</th>
                                            <td class="info-text">{{ $rancangan->tentang ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal Pengajuan</th>
                                            <td class="info-text">
                                                {{ $rancangan->tanggal_pengajuan
                                                    ? \Carbon\Carbon::parse($rancangan->tanggal_pengajuan)->translatedFormat('d F Y')
                                                    : 'N/A' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>User Pengaju</th>
                                            <td class="info-text">{{ $rancangan->user->nama_user ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Perangkat Daerah</th>
                                            <td class="info-text">
                                                {{ $rancangan->user->perangkatDaerah->nama_perangkat_daerah ?? 'N/A' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Status Rancangan</th>
                                            <td class="info-text">
                                                <mark
                                                    class="badge-{{ $rancangan->status_rancangan === 'Disetujui'
                                                        ? 'success'
                                                        : ($rancangan->status_rancangan === 'Ditolak'
                                                            ? 'danger'
                                                            : 'warning') }} badge-pill">
                                                    {{ $rancangan->status_rancangan ?? 'N/A' }}
                                                </mark>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- File Pengajuan --}}
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0 font-weight-bold">File Pengajuan</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tbody>
                                        <tr>
                                            <th>Status Berkas</th>
                                            <td class="info-text">
                                                <mark
                                                    class="badge-{{ $rancangan->status_berkas === 'Disetujui' ? 'success' : ($rancangan->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                    {{ $rancangan->status_berkas ?? 'N/A' }}
                                                </mark>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal Berkas Disetujui</th>
                                            <td class="info-text">
                                                {{ $rancangan->tanggal_berkas_disetujui
                                                    ? \Carbon\Carbon::parse($rancangan->tanggal_berkas_disetujui)->translatedFormat('d F Y')
                                                    : 'N/A' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Nota Dinas <small>dari pd</small></th>
                                            <td class="info-text">
                                                <a href="{{ isset($rancangan->nota_dinas_pd) ? asset('storage/' . $rancangan->nota_dinas_pd) : '#' }}"
                                                    target="_blank" class="d-flex align-items-center">
                                                    <i class="bi bi-file-earmark-text mr-2"
                                                        style="font-size: 1.5rem; color: #ffc107;"></i>
                                                    <span>{{ isset($rancangan->nota_dinas_pd) ? 'Download Nota' : 'Tidak Ada Nota' }}</span>
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>File Rancangan</th>
                                            <td class="info-text">
                                                <a href="{{ isset($rancangan->rancangan) ? asset('storage/' . $rancangan->rancangan) : '#' }}"
                                                    target="_blank" class="d-flex align-items-center">
                                                    <i class="bi bi-file-earmark-text mr-2"
                                                        style="font-size: 1.5rem; color: #007bff;"></i>
                                                    <span>{{ isset($rancangan->rancangan) ? 'Download Rancangan' : 'Tidak Ada Rancangan' }}</span>
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Matrik</th>
                                            <td class="info-text">
                                                <a href="{{ isset($rancangan->matrik) ? asset('storage/' . $rancangan->matrik) : '#' }}"
                                                    target="_blank" class="d-flex align-items-center">
                                                    <i class="bi bi-file-earmark-spreadsheet mr-2"
                                                        style="font-size: 1.5rem; color: #28a745;"></i>
                                                    <span>{{ isset($rancangan->matrik) ? 'Download Matrik' : 'Tidak Ada Matrik' }}</span>
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Bahan Pendukung</th>
                                            <td class="info-text">
                                                <a href="{{ isset($rancangan->bahan_pendukung) ? asset('storage/' . $rancangan->bahan_pendukung) : '#' }}"
                                                    target="_blank" class="d-flex align-items-center">
                                                    <i class="bi bi-file-earmark-pdf mr-2"
                                                        style="font-size: 1.5rem; color: #dc3545;"></i>
                                                    <span>{{ isset($rancangan->bahan_pendukung) ? 'Download Bahan' : 'Tidak Ada Bahan' }}</span>
                                                </a>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th>Catatan Berkas</th>
                                            <td class="info-text">
                                                {{ $rancangan->catatan_berkas ?? 'Tidak Ada Catatan' }}</td>
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
                                @if ($rancangan->revisi->isNotEmpty())
                                    @foreach ($rancangan->revisi as $revisi)
                                        <table class="table table-borderless mb-4">
                                            <tbody>
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
                                                        {{ $revisi->tanggal_revisi ? \Carbon\Carbon::parse($revisi->tanggal_revisi)->translatedFormat('d F Y') : 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Peneliti</th>
                                                    <td class="info-text">
                                                        {{ $revisi->peneliti->nama_user ?? 'Belum Ditentukan' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Revisi Rancangan</th>
                                                    <td class="info-text">
                                                        <a href="{{ asset('storage/' . $revisi->revisi_rancangan) }}"
                                                            target="_blank" class="d-flex align-items-center">
                                                            <i class="bi bi-file-earmark-text mr-2"
                                                                style="font-size: 1.5rem; color: #007bff;"></i>
                                                            <span>Download Revisi</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Revisi Matrik</th>
                                                    <td class="info-text">
                                                        <a href="{{ asset('storage/' . $revisi->revisi_matrik) }}"
                                                            target="_blank" class="d-flex align-items-center">
                                                            <i class="bi bi-file-earmark-spreadsheet mr-2"
                                                                style="font-size: 1.5rem; color: #28a745;"></i>
                                                            <span>Download Matrik Revisi</span>
                                                        </a>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <th>Catatan Revisi</th>
                                                    <td class="info-text">
                                                        {{ $revisi->catatan_revisi ?? 'Tidak Ada Catatan' }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    @endforeach
                                @else
                                    <p class="text-center info-text">Belum ada revisi.</p>
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
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Livewire.on('openModalPeneliti', function() {
                const modal = new bootstrap.Modal(document.getElementById('penelitiModal'));
                modal.show();
            });
        });
    </script>

</div>
