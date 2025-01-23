<div>
    {{-- Searching --}}
    <div class="d-flex justify-content-between mb-3">
        <input type="text" class="form-control w-50" placeholder="Cari berdasarkan No Rancangan atau Tentang"
            wire:model.debounce.500ms="search">
        <select class="form-control w-25" wire:model="perPage">
            <option value="5">5 Data</option>
            <option value="10">10 Data</option>
            <option value="20">20 Data</option>
        </select>
    </div>

    {{-- Daftar Riwayat Validasi --}}
    <div class="list-group">
        @forelse ($riwayatValidasi as $item)
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <h4>{{ $item->rancangan->no_rancangan }}</h4>
                    <p>{{ $item->rancangan->tentang }}</p>
                    <small>
                        <i class="bi bi-person"></i> {{ $item->rancangan->user->nama_user ?? 'N/A' }}
                        <i class="bi bi-houses ml-2"></i>
                        {{ $item->rancangan->user->perangkatDaerah->nama_perangkat_daerah ?? 'N/A' }}
                    </small>
                </div>
                <span
                    class="badge badge-{{ $item->status_validasi === 'Disetujui' ? 'success' : 'danger' }}">{{ $item->status_validasi }}</span>
            </div>
        @empty
            <p class="text-center">Tidak ada data yang ditemukan.</p>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="d-flex justify-content-center mt-3">
        {{ $riwayatValidasi->links() }}
    </div>
</div>
