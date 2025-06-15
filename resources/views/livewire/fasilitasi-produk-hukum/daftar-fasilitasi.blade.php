@section('title', 'Daftar Fasilitasi')


@section('manual')
    <div class="card  mb--2">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h3 class="mb-0">Menu Daftar Fasilitasi</h3>
                <p class="description">
                    Fasilitasi Rancangan Produk Hukum
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
    <div class="card shadow border-0">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-2">Daftar Fasilitasi Produkhukum </h3>
                <small>Dibawah adalah Daftar Fasilitasi Produk Hukum</small>
            </div>
            {{--  Tombol Export PDF  --}}
            <button wire:click="exportPDF" class="btn btn-outline-danger" wire:loading.attr="disabled"
                wire:target="exportPDF">
                <i class="bi bi-file-earmark-pdf" wire:loading.remove wire:target="exportPDF"></i>
                <span wire:loading.remove wire:target="exportPDF">Export PDF</span>
                <span wire:loading wire:target="exportPDF">
                    <i class="spinner-border spinner-border-sm"></i> Loading...
                </span>
            </button>

        </div>

        <div class="card-body">
            {{-- 🔍 Pencarian --}}
            <div class="row align-items-center mt-4">


                {{-- Search Bar --}}
                <div class="col-md-4 position-relative">
                    {{-- Loading Spinner (di atas input) --}}
                    <div wire:loading wire:target="search"
                        class="text-sm text-muted position-absolute w-100  text-primary" style="top: -22px;">
                        <div class="spinner-border spinner-border-sm text-primary mr-2" role="status"></div>Mencari...
                    </div>

                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="ni ni-zoom-split-in"></i></span>
                        </div>
                        <input type="text" class="form-control" wire:model.live="search"
                            placeholder="Cari tentang, Nomor Rancangan...">
                    </div>
                </div>

                {{-- Filter Tahun --}}
                <div class="col-md-3 position-relative">
                    {{-- Loading Spinner (di atas filter) --}}
                    <div wire:loading wire:target="tahun"
                        class="text-sm text-muted position-absolute w-100  text-primary" style="top: -22px;">
                        <div class="spinner-border spinner-border-sm text-primary mr-2" role="status"></div>Memuat
                        data...
                    </div>

                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="ni ni-calendar-grid-58"></i></span>
                        </div>
                        <select class="form-control" wire:model.live="tahun">
                            <option value="">Semua Tahun</option>
                            @foreach ($tahunOptions as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Per Page Dropdown --}}
                <div class="col-md-2 position-relative">
                    {{-- Loading Spinner (di atas dropdown) --}}
                    <div wire:loading wire:target="perPage"
                        class="text-sm text-muted position-absolute w-100 text-primary" style="top: -22px;">
                        <div class="spinner-border spinner-border-sm text-primary mr-2" role="status"></div>
                        Memperbarui daftar...
                    </div>

                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="ni ni-bullet-list-67"></i></span>
                        </div>
                        <select class="form-control" wire:model.live="perPage">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>

                {{-- Filter by Jenis Rancangan --}}
                <div class="col-md-3 position-relative">
                    {{-- Loading Spinner (di atas dropdown) --}}
                    <div wire:loading wire:target="jenisRancangan"
                        class="text-sm text-muted position-absolute w-100 text-primary" style="top: -22px;">
                        <div class="spinner-border spinner-border-sm text-primary mr-2" role="status"></div>
                        Memfilter data...
                    </div>

                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="ni ni-single-02"></i></span>
                        </div>
                        <select class="form-control" wire:model.live="jenisRancangan">
                            <option value="">Semua Jenis Rancangan</option>
                            <option value="Peraturan Bupati">Peraturan Bupati</option>
                            <option value="Surat Keputusan">Surat Keputusan</option>
                        </select>
                    </div>
                </div>

            </div>


            {{-- 📝 Tabel Fasilitasi --}}
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nomor Rancangan</th>
                            <th>Tentang</th>
                            <th>Jenis Rancangan </th>
                            <th>Status Berkas</th>
                            <th>Status Validasi</th>
                            <th>Tanggal Fasilitasi</th>
                            <th>Paraf Kordinasi</th>
                            <th>Paraf Asisten</th>
                            <th>Paraf Sekda</th>
                            <th>Paraf Bupati</th>
                            @if (Auth::user()->hasRole('Admin') || Auth::user()->hasRole('Verifikator'))
                                <th>Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($fasilitasiList as $index => $fasilitasi)
                            <tr>
                                <td>{{ $fasilitasiList->firstItem() + $index }}</td>
                                <td>{{ $fasilitasi->rancangan->no_rancangan }}</td>

                                {{-- Kolom Tentang diperbesar --}}
                                <td
                                    style="min-width: 300px; max-width: 500px; white-space: normal; word-wrap: break-word;">
                                    {{ $fasilitasi->rancangan->tentang }}
                                </td>

                                <td class="still-text">
                                    <mark
                                        class="badge-{{ $fasilitasi->rancangan->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                        {{ $fasilitasi->rancangan->jenis_rancangan }}
                                    </mark>
                                </td>
                                <td
                                    style="min-width: 50px; max-width: 100px; white-space: normal; word-wrap: break-word;">
                                    <span
                                        class="badge-{{ $fasilitasi->status_berkas_fasilitasi === 'Disetujui' ? 'success' : ($fasilitasi->status_berkas_fasilitasi === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                        {{ $fasilitasi->status_berkas_fasilitasi }}
                                    </span>
                                </td>
                                <td class="text-wrap text-center">
                                    <span
                                        class="badge-{{ $fasilitasi->status_validasi_fasilitasi === 'Diterima' ? 'success' : ($fasilitasi->status_validasi_fasilitasi === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                        {{ $fasilitasi->status_validasi_fasilitasi }}
                                    </span>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($fasilitasi->tanggal_fasilitasi)->translatedFormat('d F Y') }}
                                </td>
                                <td class="text-wrap">
                                    @if ($fasilitasi->status_paraf_koordinasi === 'Selesai' && $fasilitasi->tanggal_paraf_koordinasi)
                                        Selesai
                                        ({{ \Carbon\Carbon::parse($fasilitasi->tanggal_paraf_koordinasi)->translatedFormat('d F Y') }})
                                    @else
                                        <span class="badge badge-danger">Belum Selesai</span>
                                    @endif
                                </td>

                                <td class="text-wrap">
                                    @if ($fasilitasi->status_asisten === 'Selesai' && $fasilitasi->tanggal_asisten)
                                        Selesai
                                        ({{ \Carbon\Carbon::parse($fasilitasi->tanggal_asisten)->translatedFormat('d F Y') }})
                                    @else
                                        <span class="badge badge-danger">Belum Selesai</span>
                                    @endif
                                </td>

                                <td class="text-wrap">
                                    @if ($fasilitasi->status_sekda === 'Selesai' && $fasilitasi->tanggal_sekda)
                                        Selesai
                                        ({{ \Carbon\Carbon::parse($fasilitasi->tanggal_sekda)->translatedFormat('d F Y') }})
                                    @else
                                        <span class="badge badge-danger">Belum Selesai</span>
                                    @endif
                                </td>

                                <td class="text-wrap">
                                    @if ($fasilitasi->status_bupati === 'Selesai' && $fasilitasi->tanggal_bupati)
                                        Selesai
                                        ({{ \Carbon\Carbon::parse($fasilitasi->tanggal_bupati)->translatedFormat('d F Y') }})
                                    @else
                                        <span class="badge badge-danger">Belum Selesai</span>
                                    @endif
                                </td>

                                @if (Auth::user()->hasRole('Admin') || Auth::user()->hasRole('Verifikator'))
                                    <td>
                                        <button class="btn btn-danger btn-sm"
                                            onclick="confirmDelete({{ $fasilitasi->id }})">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                @endif
                            </tr>

                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted">Tidak ada fasilitasi ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                {{-- ⏪ Pagination --}}
                <div class="mt-3">
                    {{ $fasilitasiList->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>

    {{-- 🗑 SweetAlert untuk Hapus --}}
    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch('deleteFasilitasi', {
                        id: id
                    }); // Kirim event ke Livewire hanya jika dikonfirmasi
                }
            });
        }
    </script>

</div>
