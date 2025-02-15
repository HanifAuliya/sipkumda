<div>
    {{-- Tabel Peneliti --}}
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Peneliti</th>
                    <th>Daftar Penelitian Sedang Berlangsung</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($peneliti as $index => $user)
                    <tr>
                        <td>{{ $peneliti->firstItem() + $index }}</td>
                        <td>{{ $user->nama_user }}</td>
                        <td>
                            <ul class="list-group">
                                @forelse ($user->revisi as $revisi)
                                    <li
                                        class="list-group-item d-flex justify-content-between align-items-start flex-column">
                                        <div class="d-flex justify-content-between align-items-center w-100">
                                            <span>Rancangan Nomor:
                                                {{ $revisi->rancangan->no_rancangan ?? 'N/A' }}</span>
                                            <mark
                                                class="badge-{{ $revisi->rancangan->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                                {{ $revisi->rancangan->jenis_rancangan ?? 'N/A' }}
                                            </mark>
                                        </div>
                                        <small class="mt-2 text-muted">
                                            Tentang: {{ $revisi->rancangan->tentang ?? 'N/A' }}
                                        </small>
                                    </li>
                                @empty
                                    <li class="list-group-item text-muted">Tidak ada penelitian berlangsung.</li>
                                @endforelse
                            </ul>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">Tidak ada peneliti ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>

        </table>
    </div>

    {{-- Pagination --}}
    <div class="d-flex justify-content-center mt-4">
        {{ $peneliti->links('pagination::bootstrap-4') }}
    </div>
</div>
