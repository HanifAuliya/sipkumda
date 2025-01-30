<div>
    <div class="card shadow">
        {{-- Header --}}
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-2">Kelola Tanda Tangan</h3>
                <small>Disini anda mengelola ttd nantinya yang untuk generete nota dinas!</small>
            </div>
        </div>

        {{-- Tab Navigasi --}}
        <div class="card-body">
            <div>
                {{-- Search Bar --}}
                <div class="mb-3 d-flex justify-content-between">
                    <input type="text" class="form-control w-25" placeholder="Cari tanda tangan..."
                        wire:model.live="search">
                    <button class="btn btn-outline-default" data-target="#modalTambahTtd" data-toggle="modal">
                        <i class="bi bi-plus-circle"></i> Tambah TTD
                    </button>
                </div>

                {{-- Tabel Data --}}
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Nama TTD</th>
                            <th>Gambar</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ttdList as $ttd)
                            <tr>
                                <td>{{ $ttd->nama_ttd }}</td>
                                <td><img src="{{ url('/view-private/tanda_tangan/ttd/' . basename($ttd->file_ttd)) }}"
                                        alt="TTD" width="100">
                                </td>
                                <td>
                                    <span
                                        class="badge {{ $ttd->status === 'Aktif' ? 'badge-success' : 'badge-danger' }}">
                                        {{ $ttd->status }}
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-warning btn-sm"
                                        wire:click="openModalEdit({{ $ttd->id }})" data-target="#openModalEditTtd"
                                        data-toggle="modal">
                                        <i class="bi
                                        bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" wire:click="delete({{ $ttd->id }})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">Tidak ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- Pagination --}}
                <div class="mt-3">
                    {{ $ttdList->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
    {{-- Modal Tambah --}}
    <div wire:ignore.self class="modal fade" id="modalTambahTtd" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah TTD</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    {{-- Input Nama TTD --}}
                    <div class="form-group">
                        <label>Nama TTD</label>
                        <input type="text" class="form-control" placeholder="Nama TTD" wire:model.defer="nama_ttd">
                        @error('nama_ttd')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Input File TTD --}}
                    <div class="form-group">
                        <label>Upload TTD (PNG)</label>
                        <input type="file" class="form-control" wire:model="file_ttd" accept="image/png">
                        <small class="form-text text-muted"> * Hanya file PNG. Ukuran disarankan 400x150 px. Maksimal
                            2MB.</small>
                        @error('file_ttd')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror

                        {{-- Loading saat upload --}}
                        <div wire:loading wire:target="file_ttd" class="text-primary mt-2">
                            <i class="spinner-border spinner-border-sm"></i> Mengunggah file...
                        </div>
                    </div>

                    {{-- Pilihan Status --}}
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" wire:model.defer="status">
                            <option value="Aktif">Aktif</option>
                            <option value="Tidak Aktif">Tidak Aktif</option>
                        </select>
                        @error('status')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-dismiss="modal" wire:click="resetForm">
                        <i class="bi bi-x-circle"></i> Batal
                    </button>
                    <button class="btn btn-primary" wire:click="store" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="store">
                            <i class="bi bi-save"></i> Simpan
                        </span>
                        <span wire:loading wire:target="store">
                            <i class="spinner-border spinner-border-sm"></i> Menyimpan...
                        </span>
                    </button>
                </div>
            </div>

        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.Livewire.on('openModalTambahTtd', () => {
                $('#modalTambahTtd').modal('show');
            });
            window.Livewire.on('closeModalTambahTtd', () => {
                $('#modalTambahTtd').modal('hide');
            });

            window.addEventListener('swal:ttd', function(event) {
                $('#modalAjukanFasilitasi').modal('hide');
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
