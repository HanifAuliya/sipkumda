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

    {{-- Tabel Rancangan --}}
    <div class="table-responsive">
        <table class="table text-sm">
            <thead>
                <tr>
                    <th>Nomor Rancangan</th>
                    <th>Jenis Rancangan</th>
                    <th>Tentang</th>
                    <th>Peneliti</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rancangans as $rancangan)
                    @foreach ($rancangan->revisi as $revisi)
                        <tr>
                            <td>{{ $rancangan->no_rancangan }}</td>
                            <td>
                                @if ($rancangan && $rancangan->jenis_rancangan)
                                    <mark
                                        class="badge-{{ $rancangan->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                        {{ $rancangan->jenis_rancangan }}
                                    </mark>
                                @else
                                    <span class="text-danger">Data tidak tersedia</span>
                                @endif
                            <td class="wrap-text">{{ $rancangan->tentang }}</td>
                            <td>
                                <i class="bi bi-person-gear"></i>
                                {{ $revisi->peneliti?->nama_user ?? 'Belum Ditentukan' }}
                            </td>
                            <td>
                                {{-- Tombol Lihat Detail --}}
                                <button class="btn btn-info btn-sm" wire:click="openModal({{ $revisi->id_revisi }})">
                                    <i class="bi bi-eye"></i> Lihat Detail
                                </button>
                                {{-- Tombol Reset Peneliti --}}
                                <button class="btn btn-warning btn-sm"
                                    wire:click="resetPeneliti({{ $revisi->id_revisi }})" wire:loading.attr="disabled">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset Peneliti
                                </button>
                            </td>

                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="5" class="text-center">Tidak ada data tersedia</td>
                    </tr>
                @endforelse
            </tbody>

        </table>

        <div class="d-flex justify-content-center w-100 w-md-auto">
            {{ $rancangans->links('pagination::bootstrap-4') }}
        </div>

        {{-- Modal Detail Revisi --}}
        <div wire:ignore.self class="modal fade" id="modalPilihPeneliti" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Detail Revisi</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        @if ($selectedRevisi)
                            <table class="table table-borderless">
                                <tr>
                                    <th>Nomor Rancangan:</th>
                                    <td>{{ $selectedRevisi->rancangan->no_rancangan }}</td>
                                </tr>
                                <tr>
                                    <th>Tentang:</th>
                                    <td>{{ $selectedRevisi->rancangan->tentang }}</td>
                                </tr>
                                <tr>
                                    <th>Peneliti:</th>
                                    <td>{{ $selectedRevisi->peneliti?->name ?? 'Belum Ditentukan' }}</td>
                                </tr>
                                <tr>
                                    <th>Status Revisi:</th>
                                    <td>{{ $selectedRevisi->status_revisi }}</td>
                                </tr>
                            </table>
                        @else
                            <p class="text-center">Tidak ada data tersedia.</p>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Script Modal --}}
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                window.Livewire.on('openModalPilihPeneliti', () => {
                    $('#modalPilihPeneliti').modal('show');
                });

                window.Livewire.on('closeModalPilihPeneliti', () => {
                    $('#modalPilihPeneliti').modal('hide');
                });
            });
        </script>
    </div>
</div>
