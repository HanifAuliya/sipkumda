{{-- Header --}}
@section('title', 'Rancanganku')

@section('manual')
    <div class="card  mb--2">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h3 class="mb-0">Panduan Rancanganku</h3>
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

<div>
    <div class="card shadow-sm">
        {{-- Header --}}
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-0">Daftar Rancanganku</h3>
                <small>Pastikan perhatikan tab di bawah!</small>
            </div>
            <div class="d-flex align-items-center">
                {{-- Livewire Component --}}
                <livewire:rancangan.bahan-penting-dropdown />
                {{-- Menggunakan sintaks <livewire> --}}
                <livewire:perangkat-daerah.rancangan.ajukan-rancangan />
            </div>
        </div>

        {{-- Tabs Navigasi --}}
        <div class="card-body">
            <ul class="nav nav-pills nav-fill flex-column flex-md-row" role="tablist">
                <li class="nav-item">
                    <a href="#" class="nav-link {{ $activeTab === 'sedang_diajukan' ? 'active' : '' }}"
                        wire:click.prevent="switchTab('sedang_diajukan')">

                        {{-- Teks tombol saat loading --}}
                        <span wire:loading wire:target="switchTab('sedang_diajukan')"
                            class="spinner-border spinner-border-sm text-light"></span>
                        <span wire:loading wire:target="switchTab('sedang_diajukan')">Memuat Data...</span>
                        <span wire:loading.remove wire:target="switchTab('sedang_diajukan')">
                            <i class="ni ni-send mr-2"></i> Sedang Diajukan
                        </span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="#" class="nav-link {{ $activeTab === 'riwayat_pengajuan' ? 'active' : '' }}"
                        wire:click.prevent="switchTab('riwayat_pengajuan')">

                        {{-- Teks tombol saat loading --}}
                        <span wire:loading wire:target="switchTab('riwayat_pengajuan')"
                            class="spinner-border spinner-border-sm text-light"></span>
                        <span wire:loading wire:target="switchTab('riwayat_pengajuan')">Memuat Data...</span>
                        <span wire:loading.remove wire:target="switchTab('riwayat_pengajuan')">
                            <i class="ni ni-check-bold mr-2"></i> Pengajuan Diterima
                        </span>
                    </a>
                </li>
            </ul>

            {{-- Tab Content --}}
            <div class="tab-content mt-3">
                @if ($activeTab === 'sedang_diajukan')
                    <livewire:perangkat-daerah.rancangan.sedang-diajukan />
                @elseif ($activeTab === 'riwayat_pengajuan')
                    <livewire:perangkat-daerah.rancangan.riwayat-pengajuan />
                @endif
            </div>
        </div>

    </div>

    {{-- Script untuk Zoom dan Fullscreen --}}
    <script>
        // Fungsi Toggle Fullscreen
        function toggleFullscreen() {
            const wrapper = document.getElementById('zoomable-wrapper');
            const content = document.getElementById('zoomable-content');

            if (!document.fullscreenElement) {
                wrapper.requestFullscreen().then(() => {
                    // Sesuaikan tampilan saat fullscreen
                    wrapper.style.background = "#fff";
                    wrapper.style.overflow = "hidden"; // Hilangkan scroll
                    wrapper.style.display = "flex";
                    wrapper.style.justifyContent = "center";
                    wrapper.style.alignItems = "center";
                    wrapper.style.width = "100vw"; // Gunakan seluruh lebar layar
                    wrapper.style.height = "100vh"; // Gunakan seluruh tinggi layar
                    content.style.maxHeight = "none"; // Pastikan konten mengambil seluruh area
                }).catch(err => {
                    console.error(`Error attempting to enable fullscreen: ${err.message}`);
                });
            } else {
                document.exitFullscreen().then(() => {
                    resetFullscreenStyles(wrapper, content); // Kembalikan ke tampilan awal
                });
            }
        }

        // Reset gaya saat keluar dari fullscreen
        document.addEventListener('fullscreenchange', () => {
            if (!document.fullscreenElement) {
                const wrapper = document.getElementById('zoomable-wrapper');
                const content = document.getElementById('zoomable-content');
                resetFullscreenStyles(wrapper, content);
            }
        });

        // Fungsi Reset Gaya Fullscreen
        function resetFullscreenStyles(wrapper, content) {
            wrapper.style.background = "none";
            wrapper.style.overflow = "auto";
            wrapper.style.display = "block";
            wrapper.style.justifyContent = "unset";
            wrapper.style.alignItems = "unset";
            wrapper.style.width = "auto";
            wrapper.style.height = "auto";
            content.style.maxHeight = "200px";
            content.style.transform = "none";
            content.style.transformOrigin = "unset";
        }
    </script>
</div>
