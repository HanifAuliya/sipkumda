<div>
    <div class="mb-4">
        <input type="text" class="form-control" placeholder="Cari..." wire:model="search">
    </div>

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th> <!-- Kolom nomor lebih kecil -->
                    <th style="width: 15%;">Nomor Rancangan</th> <!-- Kolom nomor rancangan -->
                    <th style="width: 50%;">Tentang</th> <!-- Kolom tentang lebih besar -->
                    <th style="width: 15%;">Status Validasi</th> <!-- Kolom status lebih kecil -->
                    <th style="width: 15%;">Tanggal Revisi</th> <!-- Kolom tanggal lebih kecil -->
                </tr>
            </thead>
            <tbody>
                @forelse ($revisiList as $index => $revisi)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="text-truncate" style="max-width: 150px;">
                            {{ $revisi->rancangan->no_rancangan ?? '-' }}
                        </td>
                        <td class="text-wrap">
                            {{ $revisi->rancangan->tentang ?? '-' }} asjdlasjdaslkdl jasdjasldjlaskda
                            jasdjasjdklasdasdjaskldjas
                            ajsdasdjas klj
                        </td>
                        <td>
                            <span
                                class="badge badge-{{ $revisi->status_validasi === 'Diterima' ? 'success' : ($revisi->status_validasi === 'Ditolak' ? 'danger' : 'warning') }}">
                                {{ $revisi->status_validasi }}
                            </span>
                        </td>
                        <td>
                            {{ $revisi->tanggal_revisi ? \Carbon\Carbon::parse($revisi->tanggal_revisi)->format('d M Y') : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada revisi ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>

    <div class="mt-3">
        {{ $revisiList->links('pagination::bootstrap-4') }}
    </div>
</div>
