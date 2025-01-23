<div>
    <div class="card shadow">
        {{-- Header --}}
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-2">Daftar Berkas untuk Verifikasi</h3>
                <small>Pastikan perhatikan tab di bawah! Tekan dan pilih tab untuk navigasi ke tab tujuan Anda!</small>
            </div>
        </div>

        {{-- Tab Navigasi --}}
        <div class="card-body">
            <ul class="nav nav-pills nav-fill flex-column flex-md-row" role="tablist">
                <li class="nav-item">
                    <a href="#" class="nav-link {{ $activeTab === 'menunggu' ? 'active' : '' }}"
                        wire:click.prevent="setTab('menunggu')">
                        <i class="ni ni-send mr-2"></i>Berkas Menunggu Validasi
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link {{ $activeTab === 'riwayat' ? 'active' : '' }}"
                        wire:click.prevent="setTab('riwayat')">
                        <i class="ni ni-check-bold mr-2"></i>Riwayat Validasi
                    </a>
                </li>
            </ul>

            {{-- Konten Tab --}}
            <div class="tab-content mt-4">
                @if ($activeTab === 'menunggu')
                    @livewire('verifikator.rancangan.validasi-menunggu')
                @elseif ($activeTab === 'riwayat')
                    @livewire('verifikator.rancangan.riwayat-validasi')
                @endif
            </div>
        </div>
    </div>

</div>
