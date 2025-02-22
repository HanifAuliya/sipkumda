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
                        <i class="bi bi-pen"></i> Tambah TTD
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
                                <td>
                                    <!-- Tombol Preview -->
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal"
                                        data-target="#previewModal"
                                        onclick="showPreview('{{ url('/view-private/tanda_tangan/ttd/' . basename($ttd->file_ttd)) }}')">
                                        <i class="bi bi-eye"></i> Preview
                                    </button>
                                </td>

                                <!-- Modal Preview -->
                                <div class="modal fade" id="previewModal" tabindex="-1"
                                    aria-labelledby="previewModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="previewModalLabel">Preview Tanda Tangan</h5>
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body text-center">
                                                <img id="previewImage" src="" class="img-fluid border rounded"
                                                    style="max-width: 100%;" alt="Preview TTD">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Script untuk Menampilkan Preview -->
                                <script>
                                    function showPreview(imageUrl) {
                                        document.getElementById('previewImage').src = imageUrl;
                                    }
                                </script>

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
                                    <button class="btn btn-danger btn-sm"
                                        onclick="confirmDeleteTtd({{ $ttd->id }})">
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
    <div wire:ignore.self class="modal fade" id="modalTambahTtd" tabindex="-1" aria-hidden="true"
        data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah TTD</h5>
                </div>
                <div class="modal-body">
                    {{-- Input Nama TTD --}}
                    <div class="form-group">
                        <label class="font-weight-bold">Nama Tanda Tangan</label>
                        <input type="text" class="form-control" placeholder="Masukkan Nama Tanda Tangan"
                            wire:model.defer="nama_ttd">
                        @error('nama_ttd')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    {{-- NIp --}}
                    <div class="form-group">
                        <label class="font-weight-bold">NIP</label>
                        <input type="text" class="form-control" placeholder="Masukkan NIP"
                            wire:model.defer="nip_ttd">
                        @error('nip_ttd')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>


                    {{-- Input Jabatan --}}
                    <div class="form-group">
                        <label class="font-weight-bold">Jabatan</label>
                        <input type="text" class="form-control" placeholder="Masukkan Jabatan"
                            wire:model.defer="jabatan">
                        @error('jabatan')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Input File --}}
                    <div class="form-group">
                        <label class="font-weight-bold">Unggah Tanda Tangan (PNG)</label>
                        <input type="file" class="form-control" wire:model="file_ttd" accept="image/png"
                            {{ $file_ttd_preview ? 'disabled' : '' }}>

                        <small class="form-text text-muted mt-1">
                            <i class="bi bi-info-circle"></i> <strong>Ketentuan:</strong>
                            <ul class="mb-0">
                                <li>Format <strong>harus PNG</strong>.</li>
                                <li>Rasio yang disarankan <strong>4:3</strong> (contoh: 400x300 px).</li>
                                <li>Maksimum ukuran file <strong>2MB</strong>.</li>
                            </ul>
                        </small>

                        @error('file_ttd')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror

                        <div wire:loading wire:target="file_ttd" class="text-primary mt-2">
                            <i class="spinner-border spinner-border-sm"></i> Mengunggah file...
                        </div>

                        @if ($file_ttd_preview)
                            <div class="mt-3 text-center">
                                <p class="mb-1"><strong>File yang diunggah:</strong></p>
                                <img src="{{ $file_ttd_preview }}" class="border rounded" width="150"
                                    alt="Preview TTD">
                                <br>
                                <button class="btn btn-danger btn-sm mt-2" wire:click="removeFile">
                                    <i class="bi bi-trash"></i> Hapus
                                </button>
                            </div>
                        @endif
                    </div>

                    {{-- Pilihan Status --}}
                    <div class="form-group">
                        <label class="font-weight-bold">Status Tanda Tangan</label>
                        <select class="form-control" wire:model.defer="status">
                            <option value="" hidden>Pilih Status</option>
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


    {{-- Modal Edit TTD --}}
    <div wire:ignore.self class="modal fade" id="openModalEditTtd" tabindex="-1" aria-hidden="true"
        data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Tanda Tangan (TTD)</h5>
                </div>
                <div class="modal-body">

                    {{-- Informasi --}}
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> <strong>Perhatian!</strong>
                        Data ini akan digunakan dalam **Nota Dinas**. Harap isi dengan benar.
                    </div>

                    {{-- Input Nama TTD --}}
                    <div class="form-group">
                        <label class="font-weight-bold">Nama Lengkap (Sesuai Nota Dinas)</label>
                        <input type="text" class="form-control" placeholder="Masukkan Nama TTD"
                            wire:model.defer="nama_ttd">
                        <small class="form-text text-muted">Contoh: <strong>TAUFIK RAHMAN, SH.</strong></small>
                        @error('nama_ttd')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Input NIP --}}
                    <div class="form-group">
                        <label class="font-weight-bold">NIP (Nomor Induk Pegawai)</label>
                        <input type="text" class="form-control" placeholder="Masukkan NIP"
                            wire:model.defer="nip_ttd">
                        <small class="form-text text-muted">Format: <strong>18 Digit Angka</strong></small>
                        @error('nip_ttd')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Input Jabatan --}}
                    <div class="form-group">
                        <label class="font-weight-bold">Jabatan</label>
                        <input type="text" class="form-control" placeholder="Masukkan Jabatan"
                            wire:model.defer="jabatan">
                        <small class="form-text text-muted">Contoh: <strong>Pembina Tk. I</strong></small>
                        @error('jabatan')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Tampilkan File TTD yang Ada --}}
                    @if ($existingFile)
                        <div class="form-group">
                            <label class="font-weight-bold">Tanda Tangan Saat Ini</label>
                            <br>
                            <img src="{{ url('/view-private/tanda_tangan/ttd/' . basename($existingFile)) }}"
                                alt="TTD" class="img-thumbnail border rounded" width="200">
                        </div>
                    @endif

                    {{-- Input File Baru untuk Mengganti --}}
                    <div class="form-group">
                        <label class="font-weight-bold">Upload Tanda Tangan Baru (PNG)</label>
                        <input type="file" class="form-control" wire:model="file_ttd" accept="image/png">
                        <small class="form-text text-muted">
                            <i class="bi bi-exclamation-triangle"></i> **Kosongkan jika tidak ingin mengganti.** <br>
                            - Format <strong>PNG</strong>, Maksimal **2MB**. <br>
                            - Rasio disarankan **4:3** (contoh: **400x300 px**).
                        </small>
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
                        <label class="font-weight-bold">Status Tanda Tangan</label>
                        <select class="form-control" wire:model.defer="status">
                            <option value="" hidden>Pilih Status</option>
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
                    <button class="btn btn-primary" wire:click="update" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="update">
                            <i class="bi bi-save"></i> Simpan Perubahan
                        </span>
                        <span wire:loading wire:target="update">
                            <i class="spinner-border spinner-border-sm"></i> Menyimpan...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>


    <script>
        function confirmDeleteTtd(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "TTD ini akan dihapus dan tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch('deleteConfirmedTtd', {
                        id: id
                    }); // Kirim ID sebagai objek
                }
            });
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.Livewire.on('openModalTambahTtd', () => {
                $('#modalTambahTtd').modal('show');
            });
            window.Livewire.on('closeModalTambahTtd', () => {
                $('#modalTambahTtd').modal('hide');
            });

            Livewire.on('openModalEditTtd', () => {
                $('#openModalEditTtd').modal('show');
            });

            Livewire.on('closeModalEditTtd', () => {
                $('#openModalEditTtd').modal('hide');
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
