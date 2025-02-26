@section('title', 'Manajemen Fasilitasi')

@section('manual')
    <div class="card  mb--2">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h3 class="mb-0">Manajemen Fasilitasi</h3>
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
    <div class="card shadow">
        {{-- Header --}}
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-2">Manajemen Fasilitasi </h3>
                <small>Dibawah ini daftar fasilitasi yang selesai dan lakukan update statusnya sampai mana pengajuan
                    fasilitasinya </small>
            </div>

        </div>
        <div class="card-body">
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

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nomor Rancangan</th>
                            <th>Jenis Rancangan</th>
                            <th>Paraf Kordinasi</th>
                            <th>Paraf Asisten</th>
                            <th>Paraf Sekda</th>
                            <th>Paraf Bupati</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($fasilitasiList as $fasilitasi)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $fasilitasi->rancangan->no_rancangan }}</td>
                                <td>{{ $fasilitasi->rancangan->jenis_rancangan }}</td>
                                <td>
                                    <span
                                        class="badge-{{ $fasilitasi->status_paraf_koordinasi === 'Selesai' ? 'success' : 'danger' }} badge pill">
                                        {{ $fasilitasi->status_paraf_koordinasi }}
                                    </span>
                                </td>
                                <td>
                                    <span
                                        class="badge-{{ $fasilitasi->status_asisten === 'Selesai' ? 'success' : 'danger' }} badge pill">
                                        {{ $fasilitasi->status_asisten }}
                                    </span>
                                </td>
                                <td>
                                    <span
                                        class=" badge-{{ $fasilitasi->status_sekda === 'Selesai' ? 'success' : 'danger' }} badge pill">
                                        {{ $fasilitasi->status_sekda }}
                                    </span>
                                </td>
                                <td>
                                    <span
                                        class="badge-{{ $fasilitasi->status_bupati === 'Selesai' ? 'success' : 'danger' }} badge pill">
                                        {{ $fasilitasi->status_bupati }}
                                    </span>
                                </td>

                                <td>
                                    @php
                                        $statusSelesai = collect([
                                            $fasilitasi->status_paraf_koordinasi,
                                            $fasilitasi->status_asisten,
                                            $fasilitasi->status_sekda,
                                            $fasilitasi->status_bupati,
                                        ])->every(fn($status) => $status === 'Selesai');
                                    @endphp

                                    @if ($statusSelesai)
                                        <button class="btn btn-success btn-sm" disabled>
                                            <i class="bi bi-check-circle"></i> Selesai
                                        </button>
                                    @else
                                        <button class="btn btn-outline-warning btn-sm"
                                            wire:click="openUpdateStatus({{ $fasilitasi->id }})"
                                            data-target="#modalUpdateStatus" data-toggle="modal">
                                            <i class="bi bi-paperclip"></i> Update Status
                                        </button>
                                    @endif
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        {{-- Pagination --}}
        <div class="d-flex justify-content-center w-100 w-md-auto">
            {{ $fasilitasiList->links('pagination::bootstrap-4') }}
        </div>
    </div>

    {{-- Modal Update Status --}}
    <div wire:ignore.self class="modal fade" id="modalUpdateStatus" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-md" data-backdrop="static" data-keyboard="false">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-clipboard-check"></i> Perbarui Status Fasilitasi
                    </h5>
                </div>

                <div class="modal-body">
                    {{-- 🔄 Loading jika Data Status Belum Siap --}}
                    @if (collect($statusOptions)->isEmpty())
                        <div class="d-flex justify-content-center align-items-center" style="min-height: 150px;">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-3 text-muted">Memuat data status, harap tunggu...</p>
                            </div>
                        </div>
                    @else
                        {{-- 🔹 Informasi Rancangan --}}
                        @if ($selectedFasilitasi)
                            <div class="table-responsive modal-table"> <!-- Tabel responsif di HP -->
                                <table class="table table-sm table-bordered">
                                    <tbody>
                                        <tr>
                                            <th class="w-25">📌 Nomor Rancangan</th>
                                            <td class="text-wrap">{{ $selectedFasilitasi->rancangan->no_rancangan }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="w-25">📖 Tentang</th>
                                            <td class="text-wrap">{{ $selectedFasilitasi->rancangan->tentang }}</td>
                                        </tr>
                                        <tr>
                                            <th class="w-25">🏛 Perangkat Daerah</th>
                                            <td class="text-wrap">
                                                {{ $selectedFasilitasi->rancangan->user->perangkatDaerah->nama_perangkat_daerah }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Alert Peringatan -->
                            <div class="alert alert-danger mt-3 p-2">
                                <i class="bi bi-exclamation-triangle-fill"></i> <strong>Pastikan rancangan yang akan
                                    diperbarui sudah benar!</strong>
                                Perubahan status <u>tidak dapat dibatalkan</u> setelah diperbarui.
                            </div>
                        @endif
                        {{-- 🔽 Pilih Status (Dropdown) --}}
                        <div class="form-group">
                            <label class="font-weight-bold"><i class="bi bi-list-check"></i> Pilih Status</label>
                            <select class="form-control" wire:model="selectedStatus" wire:change="refreshModal">
                                <option value="" hidden>-- Pilih Status --</option>
                                @foreach ($statusOptions as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <div wire:loading wire:target="selectedStatus" class="text-primary mt-2">
                                <i class="spinner-border spinner-border-sm"></i> Memuat status...
                            </div>
                        </div>

                        {{-- ✅ Konfirmasi Update (Toggle) --}}
                        @if ($selectedStatus)
                            <div class="form-group">
                                <label class="font-weight-bold"><i class="bi bi-shield-check"></i> Konfirmasi
                                    Perubahan</label>
                                <div class="d-flex align-items-center">
                                    <label class="custom-toggle">
                                        <input type="checkbox" wire:model="confirmUpdate" wire:change="refreshModal">
                                        <span class="custom-toggle-slider rounded-circle"></span>
                                    </label>
                                    <small class="text-muted ml-2">
                                        @if (!$confirmUpdate)
                                            Aktifkan untuk menyetujui perubahan
                                        @else
                                            Status siap diperbarui
                                        @endif
                                    </small>
                                </div>
                                <div wire:loading wire:target="confirmUpdate" class="text-primary mt-2">
                                    <i class="spinner-border spinner-border-sm"></i> Memproses konfirmasi...
                                </div>
                            </div>

                            {{-- 🟢 Tombol Update --}}
                            @if ($confirmUpdate)
                                <button class="btn btn-success btn-block mt-3" wire:click="updateStatus"
                                    wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="updateStatus">
                                        <i class="bi bi-check-circle"></i> Perbarui Status
                                    </span>
                                    <span wire:loading wire:target="updateStatus">
                                        <i class="spinner-border spinner-border-sm"></i> Memproses...
                                    </span>
                                </button>
                            @endif

                            <button type="button mt-2" class="btn btn-warning btn-block" data-dismiss="modal"
                                wire:click="resetModal">
                                <i class="bi bi-arrow-left-circle"></i> Batal
                            </button>
                        @endif
                    @endif
                </div>

                {{-- 🔙 Tombol Batal --}}
                <div class="modal-footer">
                </div>
            </div>
        </div>
    </div>




    {{-- Script Modal --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Livewire.on('openModalUpdateStatus', function() {
                $('#modalUpdateStatus').modal('show');
            });

            Livewire.on('closeModalUpdateStatus', function() {
                $('#modalUpdateStatus').modal('hide');
            });

            window.addEventListener('swal:modal', function(event) {
                const data = event.detail[0];
                Swal.fire({
                    icon: data.type, // 'success', 'error', 'warning', etc.
                    title: data.title,
                    text: data.message,
                    showConfirmButton: true,
                });
            });
        });
    </script>
</div>
