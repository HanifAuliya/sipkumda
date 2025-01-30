<div>
    {{-- Search Bar --}}
    <div class="form-group">
        <input type="text" class="form-control" placeholder="Cari nomor rancangan atau tentang..." wire:model="search">
    </div>

    {{-- Table --}}
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>No</th>
                <th>Nomor Rancangan</th>
                <th>Jenis Rancangan</th>
                <th>Tentang</th>
                <th>Status Sekarang</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($fasilitasiList as $fasilitasi)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $fasilitasi->rancangan->no_rancangan }}</td>
                    <td>{{ $fasilitasi->rancangan->jenis_rancangan }}</td>
                    <td>{{ $fasilitasi->rancangan->tentang }}</td>
                    <td>
                        <span class="badge badge-info">{{ $fasilitasi->status_paraf_koordinasi }}</span>
                    </td>
                    <td>
                        <button class="btn btn-warning btn-sm"
                            wire:click="$dispatch('openUpdateStatus', {{ $fasilitasi->id }})">
                            Update Status
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Pagination --}}
    <div class="mt-3">
        {{ $fasilitasiList->links('pagination::bootstrap-4') }}
    </div>
</div>
