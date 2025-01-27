@section('title', 'Revisi Rancangan')

{{-- Header --}}
@section('header', 'Menu Revisi')
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
                text: "Revisi ini akan direset!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Reset!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch('resetRevisiConfirmed', {
                        id: id
                    }); // Dispatch event Livewire
                }
            });
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // nyoba manggil dispatch dengan
            // Listener untuk membuka modal
            Livewire.on('openModal', (modalId) => {
                $(`#${modalId}`).modal('show');
            });

            // Listener untuk menutup modal
            Livewire.on('closeModal', (modalId) => {
                $(`#${modalId}`).modal('hide');
            });

            window.Livewire.on('openUploadRevisiModal', () => {
                $('#uploadRevisiModal').modal('show');
            });

            window.Livewire.on('openDetailRevisiModal', () => {
                $('#detailRevisiModal').modal('show');
            });

            window.Livewire.on('closeUploadRevisiModal', () => {
                $('#uploadRevisiModal').modal('hide');
            });

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
