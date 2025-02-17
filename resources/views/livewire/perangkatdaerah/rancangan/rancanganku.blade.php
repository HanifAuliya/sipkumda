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
                <small>Pastikan perhatikan tab di bawah!</small>x
            </div>
            @livewire('perangkat-daerah.rancangan.ajukan-rancangan')
        </div>

        {{-- Tabs --}}
        <div class="card-body">
            <ul class="nav nav-pills nav-fill flex-column flex-md-row" role="tablist">
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            Livewire.on('openModal', (modalId) => {
                $(`#${modalId}`).modal('show');
            });

            // Listener untuk menutup modal
            Livewire.on('closeModal', (modalId) => {
                $(`#${modalId}`).modal('hide');
            });

            // Membuka Modal
            window.Livewire.on('openUploadUlangBerkasModal', () => {
                $('#uploadUlangBerkasModal').modal('show');
            });

            // Menmutup Modal
            window.Livewire.on('closeUploadUlangBerkasModal', () => {
                $('#uploadUlangBerkasModal').modal('hide');
            });

        });
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
