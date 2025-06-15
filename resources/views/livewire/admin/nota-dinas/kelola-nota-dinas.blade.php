{{-- Header --}}
@section('title', 'Nota Dinas')
@section('manual')
    <div class="card  mb--2">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h3 class="mb-0">Nota Dinas</h3>
                <p class="description">
                    Pengajuan Rancangan Produk Hukum
                </p>
            </div>

            {{-- Tombol untuk Verifikator --}}
            <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">
                <i class="bi bi-skip-backward mr-2"></i> Kembali
            </a>
        </div>
    </div>
@endsection

<div>
    <div class="card shadow">
        {{-- Header --}}
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-2">Kelola Nota Dinas </h3>
                <small>Dibawah adalah daftar nota dinas klik cetak pd untuk , mengenerate pdfnya</small>
            </div>

            @can('create', App\Models\NotaDinas::class)
                <button class="btn btn-outline-default" wire:click="openModalTambahNotaDinas" data-target="#modalTambahNota"
                    data-toggle="modal">
                    <i class="bi bi-file-plus"></i> Tambah Nota Dinas
                </button>
            @endcan

        </div>

        {{-- Tab Navigasi --}}
        <div class="card-body">
            {{-- Pencarian dan Pilihan PerPage --}}
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
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Nomor Nota Generate</th>
                            <th>No Rancangan</th>
                            <th>Jenis Fasilitasi</th>
                            <th>Judul Fasilitasi</th>
                            <th>Tgl Nota Dibuat</th>
                            <th>Generet Pdf Nota</th>
                            @can('delete', App\Models\NotaDinas::class)
                                <th>Aksi</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($notaDinasList as $nota)
                            <tr>
                                <td>{{ $nota->nomor_nota }}</td>
                                <td>{{ $nota->fasilitasi->rancangan->no_rancangan }}</td>
                                <td>
                                    <mark
                                        class="badge-{{ $nota->fasilitasi->rancangan->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                        {{ $nota->fasilitasi->rancangan->jenis_rancangan }}
                                    </mark>
                                </td>
                                <td class="wrap-text w-25">{{ $nota->fasilitasi->rancangan->tentang }}</td>
                                <td>{{ \Carbon\Carbon::parse($nota->tanggal_nota)->translatedFormat('d F Y') }}</td>


                                @can('view', $nota)
                                    <td>
                                        <button class="btn btn-outline-primary"
                                            onclick="showLoadingSwal(); @this.generatePDF({{ $nota->id }})"
                                            wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="generatePDF({{ $nota->id }})">
                                                <i class="bi bi-file-earmark-pdf"></i> Cetak
                                            </span>
                                            <span wire:loading wire:target="generatePDF({{ $nota->id }})">
                                                <i class="spinner-border spinner-border-sm"></i> Loading ...
                                            </span>
                                        </button>
                                        <script>
                                            function showLoadingSwal() {
                                                Swal.fire({
                                                    title: "Sedang Memproses PDF...",
                                                    html: "Mohon tunggu sebentar...",
                                                    allowOutsideClick: false,
                                                    showConfirmButton: false,
                                                    didOpen: () => {
                                                        Swal.showLoading();
                                                    }
                                                });
                                            }

                                            // Event dari Livewire untuk menutup SweetAlert setelah proses selesai
                                            document.addEventListener('DOMContentLoaded', function() {
                                                Livewire.on('hideLoadingSwal', () => {
                                                    Swal.close(); // Menutup SweetAlert setelah PDF selesai dibuat
                                                });
                                            });
                                        </script>

                                    </td>
                                    @can('delete', $nota)
                                        <td>
                                            <button class="btn btn-danger " onclick="confirmDelete({{ $nota->id }})">
                                                <i class="bi bi-trash"></i></button>
                                        </td>
                                    @endcan
                                @else
                                    <td>
                                        <strong class="text-danger">Perlu Izin!</strong>
                                    </td>
                                @endcan
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center info-text">Tidak ada data rancangan sedang
                                    diajukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-3">
                    {{ $notaDinasList->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Tambah Nota Dinas -->
    <div wire:ignore.self class="modal fade" id="modalTambahNota" tabindex="-1" role="dialog"
        aria-labelledby="modalTambahNotaLabel" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahNotaLabel">Tambah Nota Dinas</h5>
                </div>

                <!-- Livewire Form -->
                <form wire:submit.prevent="store">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="fasilitasiId">Pilih Fasilitasi</label>
                            <select class="form-control" wire:model.defer="fasilitasiId">
                                @if (count($fasilitasiList) == 0)
                                    <option value="" disabled>⚠️ Tidak ada data tersedia</option>
                                @else
                                    <option value="">Pilih Fasilitasi</option>
                                    @foreach ($fasilitasiList as $fasilitasi)
                                        <option value="{{ $fasilitasi->id }}">
                                            {{ $fasilitasi->rancangan->no_rancangan }} -
                                            {{ $fasilitasi->rancangan->tentang }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>


                            @error('fasilitasiId')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="tandaTanganId">Pilih Tanda Tangan</label>
                            <select class="form-control" wire:model="tandaTanganId">
                                <option value="" hidden>Pilih Tanda Tangan</option>
                                @foreach ($tandaTanganList as $tandaTangan)
                                    <option value="{{ $tandaTangan->id }}">
                                        {{ $tandaTangan->nama_ttd }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tandaTanganId')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="nomor_inputan">Nomor Nota</label>
                            <small class="form-text text-muted"> * Masukkan Nomor Nota untuk bagian pagar
                                -> 180/###/NDPB/KUM/2025.</small>

                            <div class="input-group">
                                <input type="text" class="form-control text-center" value="180/" disabled>
                                <input type="text" id="nomor_inputan" class="form-control text-center"
                                    wire:model="nomorInputan" placeholder="###" maxlength="3"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,3)">
                                <input type="text" class="form-control text-center" value="/NDPB/KUM/2025"
                                    disabled>
                            </div>

                            @error('nomorInputan')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="kepada">Kepada</label>
                            <small class="form-text text-muted">*contoh : Kepala Bagian Hukum ...</small>
                            <input type="text" class="form-control" id="kepada" wire:model.defer="kepada"
                                placeholder="Masukkan penerima nota dinas">
                            @error('kepada')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>


                    </div>

                    <div class="modal-footer">
                        {{-- Tombol Tutup --}}
                        <button type="button" class="btn btn-outline-warning" data-dismiss="modal"
                            wire:click="resetForm">
                            <i class="bi bi-backspace mr-2"></i>Batal</button>

                        {{-- Tombol Simpan dengan wire:loading --}}
                        <button type="submit" class="btn btn-outline-default" wire:click="store"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="store">
                                <i class="bi bi-save mr-2"></i> Simpan
                            </span>
                            <span wire:loading wire:target="store">
                                <i class="spinner-border spinner-border-sm"></i> Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: "Yakin ingin menghapus?",
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Ya, Hapus!",
                cancelButtonText: "Batal"
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.call('deleteNotaDinas', id); // 🔥 Langsung kirim ID sebagai integer
                }
            });
        }
    </script>
</div>
