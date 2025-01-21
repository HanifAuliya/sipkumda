{{-- Header --}}
@section('title', 'Pilih Peneliti')

<div>
    {{-- Header --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Pilih Peneliti</h3>
            {{-- Penjelasan Tab --}}
            <p class="description">
                <strong>Menunggu Peneliti </strong> Menampilkan daftar rancangan produk hukum yang masih menunggu
                peneliti untuk ditugaskan.
                <strong>Peneliti Ditugaskan </strong> Menampilkan daftar rancangan yang sudah memiliki peneliti untuk
                melanjutkan proses revisi.
                <strong>Daftar Peneliti </strong> Menampilkan daftar lengkap peneliti beserta rancangan yang sedang di
                teliti.
            </p>
        </div>
        <div class="card-body">


            {{-- Tabs --}}
            <ul class="nav nav-pills nav-fill flex-column flex-md-row" role="tablist">
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab == 'menunggu-peneliti' ? 'active' : '' }}" href="#"
                        wire:click.prevent="$set('activeTab', 'menunggu-peneliti')">
                        <i class="ni ni-send mr-2"></i> Menunggu Peneliti
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab == 'peneliti-ditugaskan' ? 'active' : '' }}" href="#"
                        wire:click.prevent="$set('activeTab', 'peneliti-ditugaskan')">
                        <i class="ni ni-check-bold mr-2"></i> Peneliti Ditugaskan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab == 'daftar-peneliti' ? 'active' : '' }}" href="#"
                        wire:click.prevent="$set('activeTab', 'daftar-peneliti')">
                        <i class="ni ni-single-02 mr-2"></i> Daftar Penelitian Berlangsung
                    </a>
                </li>
            </ul>



            {{-- Tab Content --}}
            <div class="tab-content mt-4">
                @if ($activeTab === 'menunggu-peneliti')
                    @livewire('verifikator.rancangan.menunggu-peneliti')
                @elseif ($activeTab === 'peneliti-ditugaskan')
                    @livewire('verifikator.rancangan.peneliti-ditugaskan')
                @elseif ($activeTab === 'daftar-peneliti')
                    @livewire('verifikator.rancangan.daftar-peneliti')
                @endif
            </div>
        </div>

    </div>
    {{-- Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.Livewire.on('openModalPilihPeneliti', () => {
                $('#modalPilihPeneliti').modal('show');
            });

            window.Livewire.on('closeModalPilihPeneliti', () => {
                $('#modalPilihPeneliti').modal('hide');
            });

            // Sweeet Alert 2
            window.Livewire.on('swal:modal', (data) => {

                // Jika data adalah array, akses elemen pertama
                if (Array.isArray(data)) {
                    data = data[0];
                }

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
