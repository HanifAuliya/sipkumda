@section('title', 'Fasilitasiku')

@section('manual')
    <div class="card  mb--2">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h3 class="mb-0">Menu Fasilitasiku</h3>
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
    <div class="card shadow-sm">
        {{-- Header --}}
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-0">Menu Fasilitasiku</h3>
                <small>Kelola fasilitasi produk hukum Anda di sini</small>
            </div>
        </div>

        {{-- Penjelasan Menu --}}
        <div class="card-body">
            {{-- Tab Navigasi --}}
            <ul class="nav nav-pills nav-fill flex-column flex-md-row" role="tablist">
                <li class="nav-item">
                    <a href="#" class="nav-link {{ $activeTab === 'fasilitasi-berlangsung' ? 'active' : '' }}"
                        wire:click.prevent="switchTab('fasilitasi-berlangsung')">

                        {{-- Teks tombol saat loading --}}
                        <span wire:loading wire:target="switchTab('fasilitasi-berlangsung')"
                            class="spinner-border spinner-border-sm text-light"></span>
                        <span wire:loading wire:target="switchTab('fasilitasi-berlangsung')">Memuat Data...</span>
                        <span wire:loading.remove wire:target="switchTab('fasilitasi-berlangsung')">
                            <i class="bi bi-bookmark-dash mr-2"></i> Fasilitasi Berlangsung
                        </span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="#" class="nav-link {{ $activeTab === 'riwayat-fasilitasi' ? 'active' : '' }}"
                        wire:click.prevent="switchTab('riwayat-fasilitasi')">

                        {{-- Teks tombol saat loading --}}
                        <span wire:loading wire:target="switchTab('riwayat-fasilitasi')"
                            class="spinner-border spinner-border-sm text-light"></span>
                        <span wire:loading wire:target="switchTab('riwayat-fasilitasi')">Memuat Data...</span>
                        <span wire:loading.remove wire:target="switchTab('riwayat-fasilitasi')">
                            <i class="bi bi-bookmark-star mr-2"></i> Riwayat Fasilitasi
                        </span>
                    </a>
                </li>
            </ul>

            {{-- Tab Content --}}
            <div class="tab-content mt-4">
                @if ($activeTab === 'fasilitasi-berlangsung')
                    <livewire:perangkat-daerah.fasilitasi.fasilitasi-berlangsung />
                @elseif ($activeTab === 'riwayat-fasilitasi')
                    <livewire:perangkat-daerah.fasilitasi.riwayat-fasilitasi />
                @endif
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // Menmutup Modal
            window.Livewire.on('openModalDetailFasilitasi', () => {
                $('#modalDetailFasilitasi').modal('show');
            });
            window.Livewire.on('closeModalDetailFasilitasi', () => {
                $('#modalDetailFasilitasi').modal('hide');
            });

            Livewire.on('openModalUploadUlangFasilitasi', () => {
                $('#modalUploadUlangFasilitasi').modal('show');
            });

            Livewire.on('closeModalUploadUlangFasilitasi', () => {
                $('#modalUploadUlangFasilitasi').modal('hide');
            });
            window.addEventListener('swal:modal', function(event) {
                $('#modalAjukanFasilitasi').modal('hide');
                const data = event.detail[0];
                Swal.fire({
                    icon: data.type, // success, error, warning, info
                    title: data.title,
                    text: data.message,
                    timer: 3000, // Waktu otomatis menutup (ms)
                    showConfirmButton: true,
                });
            });
        });
    </script>


</div>
