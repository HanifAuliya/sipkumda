<div>
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0 text-success">Riwayat Pengajuan Fasilitasi -
                <small class="text-default">Daftar Riwayat Fasilitasi !</small>
            </h3>
        </div>
        <div class="card-body">
            {{-- Searching dan PerPage --}}
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">Tidak ada riwayat fasilitasi</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
