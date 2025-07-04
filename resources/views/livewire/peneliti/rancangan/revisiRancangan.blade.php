{{-- Header --}}
@section('title', 'Menu Penelitian')

@section('manual')
    <div class="card  mb--2">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h3 class="mb-0">Menu Penelitian</h3>
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
    <div class="card shadow-sm">
        {{-- Header --}}
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-0">Daftar Rancangan yang diteliti</h3>
                <small>Pastikan perhatikan tab di bawah!</small>
            </div>
        </div>
        {{-- Penjelasan Menu --}}
        <div class="card-body">
            {{-- Tab Navigasi --}}
            <ul class="nav nav-pills nav-fill flex-column flex-md-row" role="tablist">
                <li class="nav-item">
                    <a href="#" class="nav-link {{ $activeTab === 'menunggu-revisi' ? 'active' : '' }}"
                        wire:click.prevent="switchTab('menunggu-revisi')">
                        <i class="ni ni-time-alarm mr-2"></i> Menunggu Revisi
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link {{ $activeTab === 'riwayat-revisi' ? 'active' : '' }}"
                        wire:click.prevent="switchTab('riwayat-revisi')">
                        <i class="ni ni-archive-2 mr-2"></i> Riwayat Revisi
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link {{ $activeTab === 'daftar-revisi' ? 'active' : '' }}"
                        wire:click.prevent="switchTab('daftar-revisi')">
                        <i class="ni ni-archive-2 mr-2"></i> Daftar Revisi Selesai
                    </a>
                </li>
            </ul>

            {{-- Tab Content --}}
            <div class="tab-content mt-4">
                @if ($activeTab === 'menunggu-revisi')
                    @livewire('peneliti.rancangan.menunggu-revisi')
                @elseif ($activeTab === 'riwayat-revisi')
                    @livewire('peneliti.rancangan.riwayat-revisi')
                @elseif ($activeTab === 'daftar-revisi')
                    @livewire('peneliti.rancangan.daftar-revisi')
                @endif
            </div>
        </div>
    </div>

    <script>
        function confirmResetRevisi(id) {
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
                    Livewire.dispatch('resetRevisiConfirmed', {
                        id: id
                    }); // Kirim ID sebagai objek
                }
            });
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            window.addEventListener('swal:modal', function(event) {
                const data = event.detail[0];
                Swal.fire({
                    icon: data.type, // 'success', 'error', 'warning', etc.
                    title: data.title,
                    text: data.message,
                    timer: 7000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    position: 'top-end',
                    toast: true,
                });
            });

            window.addEventListener('swal:reset', function(event) {
                const data = event.detail[0];

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
