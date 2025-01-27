<div>
    <div class="card shadow">
        {{-- Header --}}
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-2">Daftar Validasi Menunggu</h3>
                <small>Pastikan perhatikan tab di bawah! Tekan dan pilih tab untuk navigasi ke tab tujuan Anda!</small>
            </div>
        </div>

        {{-- Tab Navigasi --}}
        <div class="card-body">
            <ul class="nav nav-pills nav-fill flex-column flex-md-row" role="tablist">
                <li class="nav-item">
                    <a href="#" class="nav-link {{ $activeTab === 'menunggu-validasi' ? 'active' : '' }}"
                        wire:click.prevent="switchTab('menunggu-validasi')">
                        <i class="ni ni-send mr-2"></i>Berkas Menunggu Validasi
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link {{ $activeTab === 'riwayat-validasi' ? 'active' : '' }}"
                        wire:click.prevent="switchTab('riwayat-validasi')">
                        <i class="ni ni-check-bold mr-2"></i>Riwayat Validasi
                    </a>
                </li>
            </ul>

            {{-- Konten Tab --}}
            <div class="tab-content mt-4">
                @if ($activeTab === 'menunggu-validasi')
                    @livewire('verifikator.rancangan.validasi-menunggu')
                @elseif ($activeTab === 'riwayat-validasi')
                    @livewire('verifikator.rancangan.riwayat-validasi')
                @endif
            </div>
        </div>
    </div>
    {{-- comnfirm reset --}}
    <script>
        function confirmResetValidasi(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Validasi Rancangan ini akan direset!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Reset!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch('resetValidasiConfirmed', {
                        id: id
                    }); // Dispatch event Livewire
                }
            });
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            Livewire.on('openModal', (modalId) => {
                $(`#${modalId}`).modal('show');
            });

            // Listener untuk menutup modal
            Livewire.on('closeModal', (modalId) => {
                $(`#${modalId}`).modal('hide');
            });

            window.Livewire.on('openModalValidasiRancangan', () => {
                $('#modalValidasiRancangan').modal('show');
            });

            window.Livewire.on('closeModalValidasiRancangan', () => {
                $('#modalValidasiRancangan').modal('hide');
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
