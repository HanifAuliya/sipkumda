@section('title', 'Validasi Fasilitasi')

<div>
    .<div class=" card shadow-sm">
        {{-- Header --}}
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-0">Menu Validasi Fasilitasi</h3>
                <small>Kelola Validasi Fasilitasi yang diajukan</small>
            </div>
        </div>

        {{-- Penjelasan Menu --}}
        <div class="card-body">
            {{-- Tab Navigasi --}}
            <ul class="nav nav-pills nav-fill flex-column flex-md-row" role="tablist">
                <li class="nav-item">
                    <a href="#" class="nav-link {{ $activeTab === 'menunggu-validasi' ? 'active' : '' }}"
                        wire:click.prevent="switchTab('menunggu-validasi')">

                        {{-- Teks tombol saat loading --}}
                        <span wire:loading wire:target="switchTab('menunggu-validasi')"
                            class="spinner-border spinner-border-sm text-light"></span>
                        <span wire:loading wire:target="switchTab('menunggu-validasi')">Memuat Data...</span>
                        <span wire:loading.remove wire:target="switchTab('menunggu-validasi')">
                            <i class="ni ni-time-alarm mr-2"></i> Menunggu Validasi
                        </span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="#" class="nav-link {{ $activeTab === 'riwayat-validasi' ? 'active' : '' }}"
                        wire:click.prevent="switchTab('riwayat-validasi')">

                        {{-- Teks tombol saat loading --}}
                        <span wire:loading wire:target="switchTab('riwayat-validasi')"
                            class="spinner-border spinner-border-sm text-light"></span>
                        <span wire:loading wire:target="switchTab('riwayat-validasi')">Memuat Data...</span>
                        <span wire:loading.remove wire:target="switchTab('riwayat-validasi')">
                            <i class="ni ni-archive-2 mr-2"></i> Riwayat Validasi
                        </span>
                    </a>
                </li>
            </ul>


            {{-- Tab Content --}}
            <div class="tab-content mt-4">
                @if ($activeTab === 'menunggu-validasi')
                    @livewire('verifikator.fasilitasi.validasi-menunggu')
                @elseif ($activeTab === 'riwayat-validasi')
                    @livewire('verifikator.fasilitasi.riwayat-validasi')
                @endif
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {


            window.Livewire.on('openModalValidasiFasilitasi', () => {
                $('#modalValidasiFasilitasi').modal('show');
            });
            window.Livewire.on('closeModalValidasiFasilitasi', () => {
                $('#modalValidasiFasilitasi').modal('hide');
            });
            window.Livewire.on('openModalRiwayatFasilitasi', () => {
                $('#modalRiwayatFasilitasi').modal('show');
            });
            window.Livewire.on('closeModalRiwayatFasilitasi', () => {
                $('#modalRiwayatFasilitasi').modal('hide');
            });

            window.addEventListener('swal:modal', function(event) {
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
