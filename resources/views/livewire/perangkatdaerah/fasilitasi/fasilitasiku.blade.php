@section('title', 'Fasilitasiku')

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

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            function initializeToastButtons() {
                setTimeout(() => { // Tambahkan delay agar elemen sudah dirender oleh Livewire
                    document.querySelectorAll(".showToastBtn").forEach((button) => {
                        button.removeEventListener("click", handleToastClick);
                        button.addEventListener("click", handleToastClick);
                    });
                }, 300); // Beri jeda 300ms untuk memastikan elemen sudah tersedia
            }

            function handleToastClick(event) {
                let button = event.currentTarget;
                let status = button.getAttribute("data-status");
                let validasi = button.getAttribute("data-validasi");
                let nota = button.getAttribute("data-nota");


                let message =
                    "Status tidak diketahui, harap hubungin admin";

                if (status === 'Menunggu Persetujuan' && validasi === 'Belum Tahap Validasi') {
                    message = "Harap Sabar! Fasilitasi Rancangan Menunggu Persetujuan Dari Peneliti.";
                } else if (status === 'Ditolak' && validasi === 'Belum Tahap Validasi') {
                    message =
                        "Fasilitasi Rancangan Ditolak ❌! Silahkan Upload Ulang. Anda bisa ke kolom Aksi tekan tombol ⚙️ -> pilih Upload Ulang Fasilitasi! 😏";
                } else if (status === 'Ditolak' && validasi === 'Ditolak') {
                    message =
                        "Harap Periksa Fasilitasi anda sesuai dengan catatan pengajuan Rancangan anda sebelumnya , perbaiki dan upload ulang. Anda bisa ke kolom Aksi tekan tombol ⚙️ -> pilih Upload Ulang Fasilitasi! 😏";
                } else if (status === 'Disetujui' && validasi === 'Menunggu Validasi') {
                    message =
                        "Fasilitasi Rancangan Telah Disetujui ✅, Menunggu Konfirmasi dari Verifikator. Mohon Ditunggu 🙂!";
                } else if (validasi === 'Diterima' && nota === 'false') {
                    message = "Validasi Diterima ✅, Menunggu Nota Dinas Dibuat. Harap sabar ya!🤌 ";
                } else if (validasi === 'Diterima' && nota === 'true') {
                    message =
                        "Nota Dinas Telah Dibuat 🗒️. Kamu bisa cetak di Aksi ⚙️-> Cetak Nota Dinas, atau Kamu ke halaman Nota lalu cetak !🔥. Sekarang Anda dapat Mengajukan Fasilitasi secara daring. 📑⚖️";
                }

                let toastElement = document.getElementById("statusToast");
                if (toastElement) {
                    document.getElementById("toastMessage").innerText = message;
                    $(toastElement).toast("show");
                } else {
                    console.error("Elemen toast tidak ditemukan!");
                }
            }

            // Inisialisasi saat pertama kali halaman dimuat
            initializeToastButtons();

            // Inisialisasi ulang setelah setiap perubahan Livewire
            Livewire.hook("message.processed", (message, component) => {
                initializeToastButtons();
            });

            // Jika menggunakan event Livewire
            Livewire.on("refreshToastButtons", () => {
                initializeToastButtons();
            });
        });
    </script>
</div>
