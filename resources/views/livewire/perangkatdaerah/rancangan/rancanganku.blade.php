@section('header', 'Rancangan Pengajuan')

{{-- Header --}}
@section('title', 'Rancanganku')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
        <ol class="breadcrumb breadcrumb-links bg-gradient-orange">

            <li class="breadcrumb-item">
                <a href="{{ route('rancanganku') }}" class="text-white">
                    <i class="fas fa-home"></i>
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('rancanganku') }}" class="text-white">Rancanganku</a>
            </li>
            <li class="breadcrumb-item active text-white" aria-current="page">List</li>
        </ol>
    </nav>
@endsection

@section('actions')
    <a href="#" class="btn btn-sm btn-neutral">New</a>
    <a href="#" class="btn btn-sm btn-neutral">Filters</a>
@endsection
<div>
    <div class="card shadow-sm">
        {{-- Header --}}
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-0">Daftar Rancanganku</h3>
                <small>Pastikan perhatikan tab di bawah!</small>
            </div>
            <button class="btn btn-outline-default" data-toggle="modal" data-target="#ajukanRancanganModal"
                wire:click="resetForm">Ajukan
                Rancangan Baru</button>
        </div>

        {{-- Tabs --}}
        <div class="card-body">
            <ul class="info-text ">
                <li>
                    <p class="description">
                        <strong>Sedang Diajukan:</strong>
                        Menampilkan daftar rancangan yang anda ajukan.
                        Anda dapat memeriksa berkas dan memantau sampai tahapan mana rancangan anda.
                    </p>
                </li>
                <li>
                    <p class="description mt--2">
                        <strong>Riwayat Pengajuan:</strong>
                        Menampilkan riwayat rancangan yang sudah diproses, yang telah disetujui
                    </p>
                </li>
            </ul>
            <ul class="nav nav-pills flex-row" role="tablist">
                <li class="nav-item">
                    <a class="nav-link {{ $tab == 'sedang_diajukan' ? 'active' : '' }}" href="#"
                        wire:click.prevent="$set('tab', 'sedang_diajukan')">
                        <i class="ni ni-send mr-2"></i> Sedang Diajukan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $tab == 'riwayat_pengajuan' ? 'active' : '' }}" href="#"
                        wire:click.prevent="$set('tab', 'riwayat_pengajuan')">
                        <i class="ni ni-check-bold mr-2"></i> Riwayat Pengajuan
                    </a>
                </li>
            </ul>

            {{-- Content --}}
            <div class="tab-content mt-3">
                @if ($tab == 'sedang_diajukan')
                    @livewire('perangkatdaerah.rancangan.sedang-diajukan')
                @elseif ($tab == 'riwayat_pengajuan')
                    @livewire('perangkatdaerah.rancangan.riwayat-pengajuan')
                @endif
            </div>
        </div>
    </div>

    {{-- Modal Ajukan Rancangan --}}
    <div wire:ignore.self class="modal fade" id="ajukanRancanganModal" tabindex="-1" role="dialog"
        aria-labelledby="ajukanRancanganModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form wire:submit.prevent="submit">
                    <div class="modal-header">
                        <h5 class="modal-title" id="ajukanRancanganModalLabel">Ajukan Rancangan Baru</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        {{-- Pilih Jenis Rancangan --}}
                        <div class="mb-3">
                            <label for="jenisRancangan" class="form-label font-weight-bold">
                                Jenis Rancangan
                            </label>
                            <select class="form-control" wire:model="jenisRancangan" required>
                                <option hidden>Pilih Jenis Rancangan</option>
                                <option value="Peraturan Bupati">Peraturan Bupati</option>
                                <option value="Surat Keputusan">Surat Keputusan</option>
                            </select>
                            @error('jenisRancangan')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        {{-- Tentang --}}
                        <div class="mb-3">
                            <label for="tentang" class="form-label font-weight-bold">Tentang</label>
                            <input type="text" class="form-control" wire:model="tentang"
                                placeholder="Masukkan Judul/Tentang Rancangan" required />
                            @error('tentang')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        {{-- File Inputs --}}
                        {{-- Rancangan Inputs --}}
                        <div class="mb-3">
                            <label for="rancangan" class="form-label font-weight-bold">File Rancangan</label>
                            <input type="file" class="form-control" wire:model="rancangan"
                                accept="application/pdf" />
                            {{-- Indikator Loading untuk Rancangan --}}
                            <div wire:loading wire:target="rancangan" class="text-info">
                                <i class="spinner-border spinner-border-sm"></i> Mengunggah File Rancangan...
                            </div>
                            @error('rancangan')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        {{-- Matrik  Input --}}
                        <div class="mb-3">
                            <label for="matrik" class="form-label font-weight-bold">Matrik Rancangan</label>
                            <input type="file" class="form-control" wire:model="matrik" accept="application/pdf" />
                            {{-- Indikator Loading untuk Matrik --}}
                            <div wire:loading wire:target="matrik" class="text-info">
                                <i class="spinner-border spinner-border-sm"></i> Mengunggah Matrik Rancangan...
                            </div>
                            @error('matrik')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        {{-- Nota Dinas input --}}
                        <div class="mb-3">
                            <label for="nota_dinas_pd" class="form-label font-weight-bold">Nota Dinas</label>
                            <input type="file" class="form-control" wire:model="nota_dinas_pd"
                                accept="application/pdf" />
                            {{-- Indikator Loading untuk Nota Dinas --}}
                            <div wire:loading wire:target="nota_dinas_pd" class="text-info">
                                <i class="spinner-border spinner-border-sm"></i> Mengunggah Nota Dinas...
                            </div>
                            @error('nota_dinas_pd')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        {{-- Bahan Pendukung Input --}}
                        <div class="mb-3">
                            <label for="bahanPendukung" class="form-label font-weight-bold">Bahan Pendukung
                                (Opsional)</label>
                            <input type="file" class="form-control" wire:model="bahanPendukung"
                                accept="application/pdf" />
                            {{-- Indikator Loading untuk Bahan Pendukung --}}
                            <div wire:loading wire:target="bahanPendukung" class="text-info">
                                <i class="spinner-border spinner-border-sm"></i> Mengunggah Bahan Pendukung...
                            </div>
                            @error('bahanPendukung')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-warning" data-dismiss="modal"
                            wire:click="resetForm">
                            Batal
                        </button>
                        <button class="btn btn-outline-default" type="submit" wire:loading.attr="disabled"
                            wire:target="submit">
                            <span wire:loading.remove wire:target="submit">Ajukan</span>
                            <span wire:loading wire:target="submit">
                                <i class="spinner-border spinner-border-sm"></i> Tunggu Sebentar Lagi Memproses...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Berhasil
        window.addEventListener('swal:modal', function(event) {
            // Ambil elemen pertama dari array
            const data = event.detail[0];

            $('#ajukanRancanganModal').modal('hide'); // Tutup modal
            // $('.modal-backdrop').remove(); // Hapus backdrop modal

            // Tampilkan SweetAlert
            Swal.fire({
                icon: data.type,
                title: data.title,
                text: data.message,
                showConfirmButton: true,
            });
        });

        // Gagal
        window.addEventListener('swal:error', function(event) {
            // Ambil elemen pertama dari array
            const data = event.detail[0];

            // Tampilkan SweetAlert
            Swal.fire({
                icon: data.type,
                title: data.title,
                text: data.message,
                showConfirmButton: true,
            });
        });
    </script>

</div>
