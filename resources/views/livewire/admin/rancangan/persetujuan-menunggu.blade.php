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
        @forelse ($rancanganMenunggu as $item)
            <div class="list-group-item d-flex justify-content-between align-items-center">
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
                        Pengajuan Rancangan Tahun {{ \Carbon\Carbon::parse($item->tanggal_pengajuan)->year }}
                    </p>

                    <div class="mt-2">
                        {{-- Tombol Tindakan --}}
                        <button class="btn btn-secondary" wire:click="openModal({{ $item->id_rancangan }})">
                            Verifikasi Berkas <i class="bi bi-question-circle"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="list-group-item text-center info-text">
                Tidak ada rancangan yang ditemukan.
            </div>
        @endforelse
    </div>


    <div class="d-flex justify-content-center mt-3">
        {{ $rancanganMenunggu->links('pagination::bootstrap-4') }}
    </div>

    {{--  Modal Tindakan  --}}
    <div wire:ignore.self class="modal fade" id="modalPersetujuan" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                {{--  Header Modal  --}}
                <div class="modal-header">
                    <h5 class="modal-title">Tindakan untuk Rancangan</h5>
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

                        {{--  Verifikasi Persetujuan  --}}
                        <div class="card shadow-sm">
                            <div class="card-header bg-warning text-white">
                                <h4 class="mb-0 text-white">Verifikasi Persetujuan Rancangan</h4>
                            </div>
                            {{-- Select  Dropdown --}}
                            <div class="card-body">
                                <p class="info-text mb-3">
                                    Pilih status persetujuan untuk rancangan ini. Tambahkan catatan jika diperlukan
                                    untuk memberikan masukan atau alasan penolakan.
                                </p>

                                <div class="form-group">
                                    @if ($statusBerkas === 'Menunggu Persetujuan')
                                        <label for="statusBerkasSelect" class="text-danger">Berkas Perlu
                                            Persetujuan</label>
                                    @else
                                        <label for="statusBerkasSelect">Status Berkas</label>
                                    @endif

                                    <div class="w-auto" style="max-width: 300px;"> <!-- Batasi lebar dropdown -->
                                        <select id="statusBerkasSelect" name="statusBerkas" class="form-control"
                                            wire:model="statusBerkas" @disabled(in_array($statusBerkas, ['Disetujui', 'Ditolak']))>
                                            <option hidden>Pilih Status</option>
                                            <option value="Disetujui">Disetujui</option>
                                            <option value="Ditolak">Ditolak</option>
                                        </select>
                                    </div>

                                    @error('statusBerkas')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <textarea wire:model.defer="catatan" class="form-control mb-3" rows="3" placeholder="Tambahkan catatan..."></textarea>
                                @error('catatan')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div class="d-flex align-items-center justify-content-between">
                                    <!-- Alert -->
                                    @if ($selectedRancangan->status_berkas === 'Disetujui' || $selectedRancangan->status_berkas === 'Ditolak')
                                        <div class="alert alert-{{ $selectedRancangan->status_berkas === 'Disetujui' ? 'success' : 'danger' }} mb-0"
                                            role="alert" style="flex: 1; text-align: center;">
                                            <strong>{{ $selectedRancangan->status_berkas }} !</strong> Rancangan Telah
                                            {{ $selectedRancangan->status_berkas }}
                                        </div>

                                        <!-- Tombol Reset -->
                                        <button class="btn btn-danger ml-3" wire:click="resetStatus"
                                            wire:loading.attr="disabled">
                                            <span wire:loading.remove>Reset Status</span>
                                            <span wire:loading>Memproses...</span>
                                        </button>
                                    @else
                                        <!-- Tombol Verifikasi -->
                                        <button class="btn btn-success ml-auto" wire:click="updateStatus"
                                            wire:loading.attr="disabled">
                                            <span wire:loading.remove>Verifikasi Rancangan</span>
                                            <span wire:loading>Memproses...</span>
                                        </button>
                                    @endif
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

    {{--  Script  --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Membuka Modal
            window.Livewire.on('openModalPersetujuan', () => {
                $('#modalPersetujuan').modal('show');
            });
            // Menmutup Modal
            window.Livewire.on('closeModalPersetujuan', () => {
                $('#modalPersetujuan').modal('hide');
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
