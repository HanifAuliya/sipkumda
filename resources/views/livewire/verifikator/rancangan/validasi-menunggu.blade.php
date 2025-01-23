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

    {{-- Daftar Rancangan --}}
    <div class="list-group">
        @forelse ($rancanganMenunggu as $item)
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
                                <i class="bi bi-person"></i>
                                {{ $item->user->nama_user ?? 'N/A' }}
                                <span class="badge badge-secondary">
                                    Pemohon
                                </span>
                            </p>
                            <p class="mb-0 info-text small">
                                <i class="bi bi-calendar"></i>
                                Tanggal Berkas Disetujui:
                                {{ \Carbon\Carbon::parse($item->tanggal_berkas_disetujui)->translatedFormat('d F Y, H:i') }}
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
                                    class="badge-{{ $item->revisi->first()?->status_revisi === 'Direvisi' ? 'success' : ($item->revisi->first()?->status_revisi === 'Menunggu Revisi' ? 'warning' : 'danger') }} badge-pill">
                                    {{ $item->revisi->first()?->status_revisi ?? 'N/A' }}
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
                            <a href="#" class="btn btn-neutral" data-toggle="modal"
                                wire:click="openModal({{ $item->id_rancangan }})" data-target="#modalPilihPeneliti">
                                Pilih Peneliti <i class="bi bi-question-circle"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-center">Tidak ada data yang ditemukan.</p>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="d-flex justify-content-center mt-3">
        {{ $rancanganMenunggu->links() }}
    </div>
</div>
