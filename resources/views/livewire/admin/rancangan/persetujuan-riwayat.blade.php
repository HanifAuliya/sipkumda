<div>
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
    {{-- Daftar Rancangan --}}
    <div class="list-group">
        @forelse ($riwayatRancangan as $item)
            <div class="list-group-item d-flex justify-content-between bg-light align-items-center">
                {{-- Informasi Utama --}}
                <div class="d-flex flex-column">
                    <h4 class="mb-1 font-weight-bold">{{ $item->no_rancangan }}</h4>
                    <p class="mb-1 mt-2 font-weight-bold">{{ $item->tentang }}</p>
                    <p class="mb-0 info-text small">
                        <i class="bi bi-person"></i>
                        {{ $item->user->nama_user ?? 'N/A' }}
                        <span class="badge badge-secondary">
                            Pemohon
                        </span>
                    </p>
                    <p class="info-text small mb-0">
                        <i class="bi bi-calendar"></i> Tanggal Pengajuan:
                        {{ \Carbon\Carbon::parse($item->tanggal_pengajuan)->translatedFormat('d F Y') }}
                    </p>
                    <p class="info-text small mb-0">
                        <i class="bi bi-calendar"></i> Tanggal Berkas Disetujui:
                        {{ $item->tanggal_berkas_disetujui
                            ? \Carbon\Carbon::parse($item->tanggal_berkas_disetujui)->translatedFormat('d F Y')
                            : 'N/A' }}
                    </p>
                    <p class="mb-0 info-text small">
                        <i class="bi bi-houses"></i>
                        {{ $item->user->perangkatDaerah->nama_perangkat_daerah ?? '-' }}
                    </p>


                </div>

                {{-- Bagian Kanan --}}
                <div class="text-right">
                    {{-- Status Berkas --}}
                    <h4 class="mb-2">
                        <mark
                            class="badge-{{ $item->status_berkas === 'Disetujui' ? 'success' : ($item->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                            <i
                                class="{{ $item->status_berkas === 'Disetujui' ? 'bi bi-check-circle' : ($item->status_berkas === 'Ditolak' ? 'bi bi-x-circle-fill' : 'bi bi-gear-fill') }}"></i>
                            <span>Berkas</span> {{ $item->status_berkas }}
                        </mark>
                    </h4>

                    <p class="info-text mb-1 small">
                        Pengajuan Rancangan Tahun {{ now()->year }}
                    </p>
                    <div class="mt-2">

                        {{-- Tombol Tindakan --}}
                        <div class="btn-group">
                            <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown"
                                aria-expanded="false">
                                Aksi
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item" href="#"
                                    wire:click.prevent="openModal({{ $item->id_rancangan }})">
                                    Lihat Detail
                                </a>
                                <a class="dropdown-item text-danger" href="#" data-toggle="modal"
                                    data-target="#resetStatusModal"
                                    wire:click="setSelectedRancangan({{ $item->id_rancangan }})">
                                    Reset Status
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        @empty
            <div class="list-group-item text-center info-text">
                Tidak ada rancangan dalam riwayat.
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="d-flex justify-content-center mt-3">
        {{ $riwayatRancangan->links('pagination::bootstrap-4') }}
    </div>

    {{-- Modal Detail Persetujuan --}}
    <div wire:ignore.self class="modal fade" id="modalPersetujuan" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                {{--  Header Modal  --}}
                <div class="modal-header">
                    <h5 class="modal-title">Detail Rancangan</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                {{--  Body Modal  --}}
                <div class="modal-body">
                    @if ($selectedRancangan)
                        <div class="row">
                            {{--  Informasi Utama  --}}
                            <div class="col-md-6 mb-4">
                                <div class="card shadow-sm">
                                    <div class="card-header">
                                        <h4 class="mb-0">Informasi Utama</h4>
                                    </div>
                                    <div class="card-body">
                                        <p class="info-text mb-3">Berikut adalah informasi dasar dari rancangan yang
                                            diajukan. Pastikan semua informasi sudah sesuai.</p>
                                        <table class="table table-sm table-borderless">
                                            <tbody>
                                                <tr>
                                                    <th class="info-text">Nomor</th>
                                                    <td>{{ $selectedRancangan->no_rancangan ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Jenis</th>
                                                    <td>
                                                        <mark
                                                            class="badge-{{ $selectedRancangan->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                                            {{ $selectedRancangan->jenis_rancangan ?? 'N/A' }}
                                                        </mark>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text">Tentang</th>
                                                    <td>{{ $selectedRancangan->tentang ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text">Tanggal Pengajuan</th>
                                                    <td>{{ $selectedRancangan->tanggal_pengajuan ? \Carbon\Carbon::parse($selectedRancangan->tanggal_pengajuan)->translatedFormat('d F Y') : 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text">User Pengaju</th>
                                                    <td>{{ $selectedRancangan->user->nama_user ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text">Perangkat Daerah</th>
                                                    <td>{{ $selectedRancangan->user->perangkatDaerah->nama_perangkat_daerah ?? 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text">Status Rancangan</th>
                                                    <td>
                                                        <mark
                                                            class="badge-{{ $selectedRancangan->status_rancangan === 'Disetujui' ? 'success' : ($selectedRancangan->status_rancangan === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                            {{ $selectedRancangan->status_rancangan ?? 'N/A' }}
                                                        </mark>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{--  File Persetujuan --}}
                            <div class="col-md-6 mb-4">
                                <div class="card shadow-sm">
                                    <div class="card-header">
                                        <h4 class="mb-0">File Persetujuan</h4>
                                    </div>
                                    <div class="card-body">
                                        <p class="info-text mb-3">Pastikan file yang diajukan sudah lengkap dan sesuai.
                                            Anda dapat mengunduh file untuk memverifikasinya.</p>
                                        <table class="table table-sm table-borderless">
                                            <tbody>
                                                <tr>
                                                    <th class="info-text">Nota Dinas</th>
                                                    <td>
                                                        <a href="{{ $selectedRancangan->nota_dinas_pd ? asset('storage/' . $selectedRancangan->nota_dinas_pd) : '#' }}"
                                                            target="_blank">
                                                            <i class="bi bi-file-earmark-text mr-2 text-warning"></i>
                                                            {{ $selectedRancangan->nota_dinas_pd ? 'Download Nota' : 'Tidak Ada Nota' }}
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text">File Rancangan</th>
                                                    <td>
                                                        <a href="{{ $selectedRancangan->rancangan ? asset('storage/' . $selectedRancangan->rancangan) : '#' }}"
                                                            target="_blank">
                                                            <i class="bi bi-file-earmark-text mr-2 text-primary"></i>
                                                            {{ $selectedRancangan->rancangan ? 'Download Rancangan' : 'Tidak Ada Rancangan' }}
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text">Matrik</th>
                                                    <td>
                                                        <a href="{{ $selectedRancangan->matrik ? asset('storage/' . $selectedRancangan->matrik) : '#' }}"
                                                            target="_blank">
                                                            <i
                                                                class="bi bi-file-earmark-spreadsheet mr-2 text-success"></i>
                                                            {{ $selectedRancangan->matrik ? 'Download Matrik' : 'Tidak Ada Matrik' }}
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text">Bahan Pendukung</th>
                                                    <td>
                                                        <a href="{{ $selectedRancangan->bahan_pendukung ? asset('storage/' . $selectedRancangan->bahan_pendukung) : '#' }}"
                                                            target="_blank">
                                                            <i class="bi bi-file-earmark-pdf mr-2 text-danger"></i>
                                                            {{ $selectedRancangan->bahan_pendukung ? 'Download Bahan' : 'Tidak Ada Bahan' }}
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Status Berkas</th>
                                                    <td>
                                                        <mark
                                                            class="badge-{{ $selectedRancangan->status_berkas === 'Disetujui' ? 'success' : ($selectedRancangan->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                            {{ $selectedRancangan->status_berkas ?? 'N/A' }}
                                                        </mark>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Tanggal Berkas Disetujui</th>
                                                    <td class="info-text">
                                                        {{ $selectedRancangan->tanggal_berkas_disetujui
                                                            ? \Carbon\Carbon::parse($selectedRancangan->tanggal_berkas_disetujui)->translatedFormat('d F Y')
                                                            : 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Catatan Berkas</th>
                                                    <td>{{ $selectedRancangan->catatan_berkas ?? 'Tidak Ada Catatan' }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
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
                {{-- Footer Modal --}}
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Reset Status -->
    <div wire:ignore.self class="modal fade" id="resetStatusModal" tabindex="-1" role="dialog"
        aria-labelledby="resetStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetStatusModalLabel">Konfirmasi Reset Status</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin mereset status rancangan ini ke "Menunggu Persetujuan"?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" wire:click="confirmResetStatus">Reset
                        Status</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.Livewire.on('openModalPersetujuan', () => {
                $('#modalPersetujuan').modal('show');
            });

            window.addEventListener('swal:modal', function(event) {
                const data = event.detail[0];

                $('#resetStatusModal').modal('hide');
                Swal.fire({
                    icon: data.type || 'info',
                    title: data.title || 'Informasi',
                    text: data.message || 'Tidak ada pesan yang diterima.',
                    showConfirmButton: true,
                });
            });
        });
    </script>
</div>
