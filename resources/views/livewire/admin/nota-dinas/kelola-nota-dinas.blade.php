<div>
    <div class="card shadow">
        {{-- Header --}}
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-2">Kelola Nota Dinas </h3>
                <small>Dibawah adalah daftar nota dinas klik cetak pd untuk , mengenerate pdfnya</small>
            </div>

            @if (Auth::user()->hasAnyRole(['Admin', 'Verifikator']))
                <button class="btn btn-outline-default mb-3" data-target="#modalTambahNota" data-toggle="modal">
                    <i class="bi bi-file-plus mr-2"></i>Tambah Nota Dinas
                </button>
            @endif

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
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nomor Nota Generate</th>
                        <th>Nomor Rancangan</th>
                        <th>Jenis Fasilitasi</th>
                        <th>Judul Fasilitasi</th>
                        <th>Tgl Nota Dibuat</th>
                        <th>Generet Pdf Nota</th>
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
                            <td>{{ $nota->tanggal_nota }}</td>

                            @if (Auth::user()->hasRole('Perangkat Daerah'))
                                {{-- Perangkat Daerah hanya bisa melihat dan mencetak rancangannya sendiri --}}
                                @if ($nota->fasilitasi->rancangan->user_id == Auth::id())
                                    <td>
                                        <button class="btn btn-default" wire:click="generatePDF({{ $nota->id }})"
                                            wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="generatePDF({{ $nota->id }})">
                                                <i class="bi bi-file-earmark-pdf"></i> Cetak PDF
                                            </span>
                                            <span wire:loading wire:target="generatePDF({{ $nota->id }})">
                                                <i class="spinner-border spinner-border-sm"></i> Sedang memproses...
                                            </span>
                                        </button>
                                    </td>
                                @else
                                    <td> <small class="text-danger">Perlu Izin!</small></td>
                                @endif
                            @else
                                <td>
                                    <button class="btn btn-default" wire:click="generatePDF({{ $nota->id }})"
                                        wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="generatePDF({{ $nota->id }})">
                                            <i class="bi bi-file-earmark-pdf"></i> Cetak PDF
                                        </span>
                                        <span wire:loading wire:target="generatePDF({{ $nota->id }})">
                                            <i class="spinner-border spinner-border-sm"></i> Generate PDF ...
                                        </span>
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center info-text">Tidak ada data rancangan sedang diajukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center w-100 w-md-auto">
            {{ $notaDinasList->links('pagination::bootstrap-4') }}
        </div>
    </div>
    <!-- Modal Tambah Nota Dinas -->
    <div wire:ignore.self class="modal fade" id="modalTambahNota" tabindex="-1" role="dialog"
        aria-labelledby="modalTambahNotaLabel" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahNotaLabel">Tambah Nota Dinas</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <!-- Livewire Form -->
                <form wire:submit.prevent="store">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="fasilitasiId">Pilih Fasilitasi</label>
                            <select class="form-control" wire:model="fasilitasiId">
                                <option value="" hidden>-- Pilih --</option>
                                @foreach ($fasilitasiList as $fasilitasi)
                                    <option value="{{ $fasilitasi->id }}">
                                        {{ $fasilitasi->rancangan->no_rancangan }} -
                                        {{ $fasilitasi->rancangan->tentang }}
                                    </option>
                                @endforeach
                            </select>
                            @error('fasilitasiId')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="tandaTanganId">Pilih Tanda Tangan</label>
                            <select class="form-control" wire:model="tandaTanganId">
                                <option value="">-- Pilih --</option>
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
                            <label for="nomor_inputan">Nomor Inputan</label>
                            <small class="form-text text-muted"> * Masukkan Nomor Nota untuk bagian pagar
                                ->180/###/NDPB/KUM/2025.</small>
                            <input type="text" id="nomor_inputan" class="form-control" wire:model="nomorInputan"
                                placeholder="Masukkan Nomor">
                            @error('nomorInputan')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                    </div>

                    <div class="modal-footer">
                        {{-- Tombol Tutup --}}
                        <button type="button" class="btn btn-outline-warning" data-dismiss="modal">
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
        document.addEventListener('DOMContentLoaded', function() {
            Livewire.on('closeModalTambahNotaDinas', () => {
                $('#modalTambahNota').modal('hide');
            });

            window.addEventListener('swal:nota', function(event) {
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
