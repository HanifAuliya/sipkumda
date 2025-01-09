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

    <div>
        @forelse ($rancangan as $item)
            {{-- Card Tab Sedang Diajukan --}}
            <div class="card p-3 shadow-sm border mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    {{-- Bagian Kiri --}}
                    <div class="d-flex align-items-start">
                        <div>
                            <div class="d-flex align-items-center">
                                <h4 class="mb-1 font-weight-bold">
                                    {{ $item->no_rancangan }}
                                </h4>
                                <h5 class="ml-2">
                                    <mark
                                        class="badge-{{ $item->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                        {{ $item->jenis_rancangan }}
                                    </mark>
                                </h5>
                            </div>

                            <p class="mb-1 mt-2 font-weight-bold">
                                {{ $item->tentang }}
                            </p>
                            <p class="mb-0 info-text small">
                                <i class="bi bi-houses"></i>
                                {{ $item->user->perangkatDaerah->nama_perangkat_daerah ?? '-' }}
                            </p>
                            <p class="mb-0 info-text small">
                                <i class="bi bi-calendar"></i>
                                Tanggal Pengajuan:
                                {{ \Carbon\Carbon::parse($item->tanggal_pengajuan)->translatedFormat('d F Y') }}
                            </p>
                            <p class="mb-0 info-text small">
                                <i class="bi bi-person-gear"></i>
                                <span
                                    class="{{ $item->revisi->first()->peneliti->nama_user ?? false ? 'info-text' : 'text-danger' }} text-primary">
                                    {{ $item->revisi->first()->peneliti->nama_user ?? 'Belum Ditentukan' }}
                                </span>
                                <span class="badge badge-secondary">
                                    Peneliti
                                </span>
                            </p>
                            <p class="mb-1 info-text small">
                                <i class="bi bi-file-check"></i>
                                Persetujuan Berkas:
                                <mark
                                    class="badge-{{ $item->status_berkas === 'Disetujui' ? 'success' : ($item->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                    {{ $item->status_berkas }}
                                </mark>
                            </p>
                            <p class="mb-0 info-text small">
                                <i class="bi bi-file-earmark-text"></i>
                                Status Revisi:
                                <mark
                                    class="badge-{{ $item->revisi->first()->status_revisi === 'Direvisi' ? 'success' : ($item->revisi->first()->status_revisi === 'Menunggu Revisi' ? 'warning' : 'danger') }} badge-pill">
                                    {{ $item->revisi->first()->status_revisi ?? 'N/A' }}
                                </mark>
                            </p>
                        </div>
                    </div>

                    {{-- Bagian Kanan --}}
                    <div class="text-right">
                        {{-- Status Rancangan --}}
                        <h4>
                            <mark
                                class="badge-{{ $item->revisi->first()?->peneliti ? 'success' : 'danger' }} badge-pill">
                                @if ($item->revisi->first()?->peneliti)
                                    <i class="bi bi-person-check"></i>
                                    {{ $item->revisi->first()?->peneliti->name }}
                                @else
                                    <i class="bi bi-person-dash"></i>
                                    Menunggu Pemilihan Peneliti
                                @endif
                            </mark>
                        </h4>

                        <p class="info-text mb-1 small">
                            Pengajuan Rancangan Tahun {{ \Carbon\Carbon::parse($item->tanggal_pengajuan)->year }}
                        </p>

                        <div class="mt-2">
                            <div class="dropdown">
                                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton"
                                    data-toggle="dropdown">
                                    Kelola Rancangan
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    {{-- Button untuk membuka modal --}}
                                    <a href="#" class="dropdown-item" data-toggle="modal"
                                        data-target="#detailModalPengajuan-{{ $item->id_rancangan }}">
                                        <i class="bi bi-folder"></i>
                                        Detail Pengajuan
                                    </a>
                                    <a class="btn btn-primary btn-sm"
                                        wire:click="openModal({{ $item->id_rancangan }})">
                                        Pilih Peneliti
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-center info-text">Tidak ada data rancangan sedang diajukan.</p>
        @endforelse

        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div class="mb-2 mb-md-0">
                @if ($rancangan->total() > 0)
                    Menampilkan {{ $rancangan->firstItem() }} hingga
                    {{ $rancangan->lastItem() }} dari
                    {{ $rancangan->total() }}
                    data
                @else
                    Tidak ada data yang tersedia.
                @endif
            </div>
            <div class="d-flex justify-content-center w-100 w-md-auto">
                {{ $rancangan->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>


    {{-- Modal --}}
    <div wire:ignore.self class="modal fade" id="modalPilihPeneliti" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header flex-column align-items-start" style="border-bottom: 2px solid #dee2e6;">
                    <h4 class="modal-title w-100 mb-2">Pilih Peneliti</h4>
                    <p class=" description mb-0 w-100 info-text">
                        Silakan cek informasi rancangan di bawah ini, termasuk file yang diajukan, lalu pilih
                        status
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
                                        <p class="description info-text mb-3">Berikut adalah informasi dasar dari
                                            rancangan
                                            yang
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
                                                    <td class="wrap-text">
                                                        {{ $selectedRancangan->catatan_berkas ?? 'Tidak Ada Catatan' }}
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
                                        <p class="description info-text mb-3">Pastikan file yang diajukan sudah lengkap
                                            dan
                                            sesuai.
                                            Anda dapat mengunduh file untuk memverifikasinya.</p>
                                        <table class="table table-sm table-borderless">
                                            <tbody>
                                                @foreach ($item->revisi as $revisi)
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
                                                                <i
                                                                    class="bi bi-file-earmark-text mr-2 text-primary"></i>

                                                                <span>Download Revisi</span>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Revisi Matrik</th>
                                                        <td class="info-text">
                                                            <a href="{{ asset('storage/' . $revisi->revisi_matrik) }}"
                                                                target="_blank" class="d-flex align-items-center">
                                                                <i
                                                                    class="bi bi-file-earmark-spreadsheet mr-2 text-success"></i>
                                                                <span>Download Matrik Revisi</span>
                                                            </a>
                                                        </td>
                                                    </tr>

                                                    <tr>
                                                        <th>Catatan Revisi</th>
                                                        <td class="wrap-text">
                                                            {{ $revisi->catatan_revisi ?? 'Tidak Ada Catatan' }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    {{-- Select Dropdown dengan Select2 --}}
                                    <div class="card-body">
                                        {{-- Penjelasan Pilihan Peneliti --}}
                                        <p class="info-text description">
                                            Silakan pilih peneliti dari daftar di bawah ini untuk menugaskan rancangan
                                            yang sedang diproses.
                                        </p>

                                        <div class="form-group">
                                            <label for="peneliti">
                                                <h4>Pilih Peneliti</h4>
                                            </label>
                                            {{-- Dropdown dengan Select2 --}}
                                            <select id="peneliti" class="form-control select2"
                                                wire:model="selectedPeneliti">
                                                <option value="" selected>Pilih Peneliti...</option>
                                                @foreach ($listPeneliti as $peneliti)
                                                    <option value="{{ $peneliti->id }}">{{ $peneliti->nama_user }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            {{-- Error Handling --}}
                                            @error('selectedPeneliti')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        {{-- Tombol Batal --}}
                                        <button class="btn btn-secondary" data-dismiss="modal">
                                            <i class="bi bi-x-circle"></i> Batal
                                        </button>

                                        {{-- Tombol Simpan dengan wire:loading --}}
                                        <button class="btn btn-primary" wire:click="assignPeneliti"
                                            wire:loading.attr="disabled">
                                            <i class="bi bi-check-circle" wire:loading.remove></i>
                                            <span wire:loading.remove>Simpan</span>
                                            <i class="spinner-border spinner-border-sm text-light" wire:loading></i>
                                            <span wire:loading>Memproses...</span>
                                        </button>
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
