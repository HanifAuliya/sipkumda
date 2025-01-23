<div>
    {{-- Daftar Revisi --}}
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Daftar Rancangan - Menunggu Revisi</h3>
        </div>

        <div class="card-body">
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
            {{-- Daftar Revisi --}}
            @forelse ($revisi as $item)
                <div class="card p-3 shadow-sm border mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        {{-- Bagian Kiri --}}
                        <div class="d-flex align-items-start">
                            <div>
                                {{-- Informasi Utama --}}
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
                                    <span class="badge badge-secondary">Pemohon</span>
                                </p>
                                <p class="mb-0 info-text small">
                                    <i class="bi bi-person-gear"></i>
                                    <span
                                        class="{{ $item->revisi->first()->peneliti->nama_user ?? false ? 'info-text' : 'text-danger' }}">
                                        {{ $item->revisi->first()->peneliti->nama_user ?? 'Belum Ditentukan' }}
                                    </span>
                                    <span class="badge badge-secondary">Peneliti</span>
                                </p>
                                <p class="mb-0 info-text small">
                                    <i class="bi bi-calendar"></i>
                                    Tanggal Peneliti Ditunjuk
                                    {{ \Carbon\Carbon::parse($item->revisi->first()->tanggal_peneliti_ditunjuk)->translatedFormat('d F Y, H:i') }}
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
                                        class="badge-{{ $item->revisi->first()->status_revisi === 'Menunggu Revisi' ? 'warning' : 'danger' }} badge-pill">
                                        {{ $item->revisi->first()->status_revisi ?? 'N/A' }}
                                    </mark>
                                </p>
                            </div>
                        </div>

                        {{-- Bagian Kanan --}}
                        <div class="text-right">
                            <h4>
                                <mark
                                    class="badge-{{ $item->revisi->first()->status_revisi === 'Menunggu Revisi' ? 'warning' : 'danger' }} badge-pill">
                                    <i class="bi bi-clipboard2-check"></i>
                                    {{ $item->revisi->first()->status_revisi ?? 'N/A' }}
                                </mark>
                            </h4>
                            <p class="info-text mb-1 small">
                                Pengajuan Rancangan Tahun {{ \Carbon\Carbon::parse($item->tanggal_pengajuan)->year }}
                            </p>
                            <div class="mt-2">
                                <button class="btn btn-outline-default"
                                    wire:click="selectRevisi({{ $item->revisi->first()->id_revisi ?? 'null' }})">
                                    Unggah Revisi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-center info-text">Tidak ada data rancangan menunggu revisi.</p>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="d-flex justify-content-center mt-3">
            {{ $revisi->links('pagination::bootstrap-4') }}
        </div>
    </div>

    {{-- Modal Unggah Revisi --}}
    <div wire:ignore.self class="modal fade" id="uploadRevisiModal" tabindex="-1" role="dialog"
        aria-labelledby="uploadRevisiModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form wire:submit.prevent="uploadRevisi">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title" id="uploadRevisiModalLabel">Upload Revisi</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                            wire:click="resetForm">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        {{-- Input File Revisi Rancangan --}}
                        <div class="mb-4">
                            <label for="revisiRancangan" class="font-weight-bold form-control-label">
                                <i class="bi bi-file-earmark-pdf text-primary"></i> File Revisi Rancangan
                                <small class="text-muted d-block">Unggah dokumen rancangan dalam format PDF (max:
                                    2MB).</small>
                            </label>
                            <input type="file" class="form-control" wire:model="revisiRancangan" accept=".pdf" />
                            <div wire:loading wire:target="revisiRancangan" class="text-info mt-2">
                                <i class="spinner-border spinner-border-sm"></i> Mengunggah...
                            </div>
                            @error('revisiRancangan')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Input File Revisi Matrik --}}
                        <div class="mb-4">
                            <label for="revisiMatrik" class="font-weight-bold form-control-label">
                                <i class="bi bi-file-earmark-pdf text-primary"></i> File Revisi Matrik
                                <small class="text-muted d-block">Unggah dokumen rancangan dalam format PDF (max:
                                    10 MB).</small>
                            </label>
                            <input type="file" class="form-control" wire:model="revisiMatrik" accept=".pdf" />
                            <div wire:loading wire:target="revisiMatrik" class="text-info mt-2">
                                <i class="spinner-border spinner-border-sm"></i> Mengunggah...
                            </div>
                            @error('revisiMatrik')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Catatan Revisi --}}
                        <div class="mb-4">
                            <label for="catatanRevisi" class="font-weight-bold">Catatan Revisi</label>
                            <textarea id="catatanRevisi" class="form-control" wire:model="catatanRevisi" rows="4"
                                placeholder="Tambahkan catatan revisi jika diperlukan"></textarea>
                            @error('catatanRevisi')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-warning" data-dismiss="modal"
                            wire:click="resetForm">
                            Batal
                        </button>
                        <button class="btn btn-outline-default" type="submit" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="uploadRevisi">Upload</span>
                            <span wire:loading wire:target="uploadRevisi">
                                <i class="spinner-border spinner-border-sm"></i> Memproses...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>



</div>
