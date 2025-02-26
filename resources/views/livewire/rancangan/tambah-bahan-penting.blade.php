@section('title', 'Tambah Bahan Penting')
@section('manual')
    <div class="card  mb--2">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h3 class="mb-0">Panduan Daftar Isi</h3>
                <p class="description">
                    © Hak Cipta Bagian Hukum Sekretariat Daerah Kabupaten Hulu
                    Sungai Tengah.
                </p>
            </div>

            {{-- Tombol untuk Verifikator --}}
            <button class="btn btn-outline-warning">
                <i class="bi bi-info-circle"></i> Panduan
            </button>

        </div>
    </div>
@endsection

<div>

    <div class="row">
        <!-- Card Kiri -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Daftar Bahan Penting</h5>
                </div>
                <div class="card-body">
                    {{-- Dropdown Bahan Penting --}}
                    <div class="dropdown mb-4">
                        <h5>Tampulan di menu rancangaku <small>untuk Perangkat Daerah</small></h5>
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="dropdownBahanPenting"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="ni ni-cloud-download-95 mr-1"></i> Bahan Penting
                        </button>
                        <div class="dropdown-menu shadow-lg" aria-labelledby="dropdownBahanPenting">
                            {{-- Item Dropdown --}}
                            @foreach ($materials as $material)
                                <a class="dropdown-item d-flex align-items-center" href="#"
                                    wire:click.prevent="download({{ $material->id }})">
                                    <i class="{{ $this->getFileIcon($material->file_path) }} mr-2"></i>
                                    {{ $material->button_name }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <!-- Tabel -->
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Button Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($materials as $material)
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ $material->button_name }}</td>
                                    <td>
                                        <a href="#" wire:click="download({{ $material->id }})"
                                            class="btn btn-primary btn-sm">
                                            Download
                                        </a>
                                        <button class="btn btn-danger btn-sm"
                                            onclick="confirmDelete({{ $material->id }})">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Card Kanan -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Daftar Bahan Penting</h5>
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="addMaterial" class="mb-4">
                        <div class="form-group mb-3">
                            <label for="buttonName">Nama Tombol</label>
                            <input type="text" class="form-control" id="buttonName" wire:model="buttonName" required
                                placeholder="Masukkan nama tombol">
                        </div>
                        <div class="form-group mb-3">
                            <label for="file">File</label>
                            <input type="file" class="form-control" id="file" wire:model="file" required
                                accept=".doc,.docx,.pdf">

                            <!-- Preview file yang telah diunggah -->
                            @if ($file)
                                <div class="mt-2 p-2 border rounded bg-light d-flex align-items-center">
                                    <i class="bi bi-file-earmark-pdf text-danger mr-2"></i>
                                    <span class="flex-grow-1">{{ $file->getClientOriginalName() }}</span>
                                    <button type="button" class="btn btn-sm btn-outline-danger ml-2"
                                        wire:click="removeFile">
                                        <i class="bi bi-trash"></i> Hapus File
                                    </button>
                                </div>
                            @endif

                            <!-- Loading saat file diunggah -->
                            <div wire:loading wire:target="file" class="mt-2">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <span class="text-muted ml-2">Mengunggah file...</span>
                            </div>
                        </div>

                        <!-- Tombol Tambah dengan Loading -->
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"
                                wire:target="addMaterial">
                                <span wire:loading.remove wire:target="addMaterial">Tambah</span>
                                <span wire:loading wire:target="addMaterial">
                                    <span class="spinner-border spinner-border-sm" role="status"
                                        aria-hidden="true"></span>
                                    Menambahkan...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data yang dihapus tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch('deleteConfirmed', {
                        id: id
                    }); // Kirim event ke Livewire
                }
            });
        }
    </script>

</div>
