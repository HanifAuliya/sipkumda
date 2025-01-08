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

    {{-- Tabel --}}
    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>Nomor</th>
                <th>Tentang</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rancangans as $rancangan)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $rancangan->no_rancangan }}</td>
                    <td>{{ $rancangan->tentang }}</td>
                    <td>
                        <button class="btn btn-primary btn-sm" wire:click="openModal({{ $rancangan->id_rancangan }})">
                            Pilih Peneliti
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div class="mb-2 mb-md-0">
            Menampilkan {{ $rancangans->firstItem() }} hingga
            {{ $rancangans->lastItem() }} dari
            {{ $rancangans->total() }}
            data
        </div>
        <div class="d-flex justify-content-center w-100 w-md-auto">
            {{ $rancangans->links('pagination::bootstrap-4') }}
        </div>
    </div>


    {{-- Modal --}}
    <div wire:ignore.self class="modal fade" id="modalPilihPeneliti" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header flex-column align-items-start" style="border-bottom: 2px solid #dee2e6;">
                    <h4 class="modal-title w-100 mb-2">Persetujuan Rancangan</h4>
                    <p class="mb-0 w-100 info-text">
                        Silakan cek informasi rancangan di bawah ini, termasuk file yang diajukan, lalu pilih status
                        persetujuan.
                    </p>
                    <button type="button" class="close position-absolute" style="top: 10px; right: 10px;"
                        data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

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

                        {{--  Verifikasi Persetujuan  --}}
                        <div class="card shadow-sm">
                            <div class="card-header bg-warning text-white">
                                <h4 class="mb-0 text-white">Verifikasi Persetujuan Rancangan</h4>
                            </div>
                            {{-- Select  Dropdown --}}
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="peneliti">Pilih Peneliti</label>
                                    <select id="peneliti" class="form-control" wire:model="selectedPeneliti">
                                        <option value="" selected>Pilih...</option>
                                        @foreach ($penelitis as $peneliti)
                                            <option value="{{ $peneliti->id }}">{{ $peneliti->nama_user }}</option>
                                        @endforeach
                                    </select>
                                    @error('selectedPeneliti')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
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
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button class="btn btn-primary" wire:click="assignPeneliti">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.Livewire.on('openModalPilihPeneliti', () => {
                $('#modalPilihPeneliti').modal('show');
            });

            window.Livewire.on('closeModalPilihPeneliti', () => {
                $('#modalPilihPeneliti').modal('hide');
            });

            // Sweeet Alert 2
            window.Livewire.on('swal:modal', (data) => {

                // Jika data adalah array, akses elemen pertama
                if (Array.isArray(data)) {
                    data = data[0];
                }

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
