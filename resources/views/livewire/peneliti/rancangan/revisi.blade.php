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
                    <a href="#" class="nav-link {{ $activeTab === 'menunggu' ? 'active' : '' }}"
                        wire:click.prevent="switchTab('menunggu')">
                        <i class="ni ni-time-alarm mr-2"></i> Menunggu Revisi
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link {{ $activeTab === 'riwayat' ? 'active' : '' }}"
                        wire:click.prevent="switchTab('riwayat')">
                        <i class="ni ni-archive-2 mr-2"></i> Riwayat Revisi
                    </a>
                </li>
            </ul>

            {{-- Tab Content --}}
            <div class="tab-content mt-4">
                @if ($activeTab === 'menunggu')
                    @livewire('peneliti.rancangan.menunggu-revisi')
                @elseif ($activeTab === 'riwayat')
                    @livewire('peneliti.rancangan.riwayat-revisi')
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
                    timer: 3000,
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
