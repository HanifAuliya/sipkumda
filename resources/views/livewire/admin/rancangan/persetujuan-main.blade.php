@section('title', 'Verifikasi Berkas')

@section('manual')
    <div class="card  mb--2">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h3 class="mb-0">Panduan Verifikasi Berkas</h3>
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

@section('actions')
    <a href="#" class="btn btn-sm btn-neutral">New</a>
    <a href="#" class="btn btn-sm btn-neutral">Filters</a>
@endsection

<div class="card shadow-sm">
    {{-- Header --}}
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-2">Daftar Berkas untuk Verifikasi</h3>
            <small>Pastikan perhatikan tab di bawah! Tekan dan pilih tab untuk navigasi ke tab tujuan anda !</small>
        </div>
    </div>

    {{-- Penjelasan Menu --}}
    <div class="card-body">
        {{-- Tab --}}
        <ul class="nav nav-pills nav-fill flex-column flex-md-row" role="tablist">
            <li class="nav-item">
                <a href="#" class="nav-link {{ $activeTab === 'menunggu' ? 'active' : '' }}"
                    wire:click.prevent="set('activeTab','menunggu')">
                    <i class="ni ni-send mr-2"></i>Berkas Menunggu Persetujuan
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link {{ $activeTab === 'ditolak' ? 'active' : '' }}"
                    wire:click.prevent="set('activeTab','ditolak')">
                    <i class="ni ni-fat-remove mr-2"></i>Berkas Rancangan Ditolak
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link {{ $activeTab === 'riwayat' ? 'active' : '' }}"
                    wire:click.prevent="set('activeTab','riwayat')">
                    <i class="ni ni-check-bold mr-2"></i>Berkas Rancangan Disetujui
                </a>
            </li>
        </ul>

        {{-- Content --}}
        <div class="tab-content mt-4">
            @if ($activeTab === 'menunggu')
                @livewire('admin.rancangan.persetujuan-menunggu')
            @elseif ($activeTab === 'ditolak')
                @livewire('admin.rancangan.persetujuan-ditolak')
            @elseif ($activeTab === 'riwayat')
                @livewire('admin.rancangan.persetujuan-riwayat')
            @endif
        </div>
    </div>

    {{-- Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Membuka Modal
            window.Livewire.on('openModalPersetujuan', () => {
                $('#modalPersetujuan').modal('show');
            });
            // Menmutup Modal
            window.Livewire.on('closeModalPersetujuan', () => {
                $('#modalPersetujuan').modal('hide');
            });

            // Sweeet Alert 2
            window.addEventListener('swal:modal', function(event) {

                const data = event.detail[0];
                $('#modalPersetujuan').modal('hide');

                // Tampilkan SweetAlert
                Swal.fire({
                    icon: data.type, // Bisa 'success', 'error', 'warning', 'info', atau 'question'
                    title: data.title, // Judul dari toast
                    text: data.message, // Pesan tambahan (opsional)
                    toast: true, // Mengaktifkan toast
                    position: 'top-end', // Posisi toast ('top', 'top-start', 'top-end', 'center', 'bottom', dll.)
                    showConfirmButton: false, // Tidak menampilkan tombol konfirmasi
                    timer: 3000, // Waktu toast tampil (dalam milidetik)
                    timerProgressBar: true, // Menampilkan progress bar pada timer
                });
            });

            window.addEventListener('swal:reset', function(event) {
                const data = event.detail[0];

                $('#resetStatusModal').modal('hide');
                Swal.fire({
                    icon: data.type || 'info',
                    title: data.title || 'Informasi',
                    text: data.message || 'Tidak ada pesan yang diterima.',
                    showConfirmButton: true,
                });
            });
        });
    </script>
</div>
