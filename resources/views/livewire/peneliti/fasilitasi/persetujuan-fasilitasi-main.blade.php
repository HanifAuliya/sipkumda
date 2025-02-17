@section('title', 'Persetujuan Fasilitasi')

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
    {{-- Header --}}
    <div class="card shadow-sm">
        <div class="card-header">
            <h3 class="mb-0">Menu Persetujuan Fasilitasi</h3>
            <small>Tab di bawah untuk navigasi.</small>
        </div>

        <div class="card-body">
            {{-- Tab Navigasi --}}
            <ul class="nav nav-pills nav-fill flex-column flex-md-row" role="tablist">
                <li class="nav-item">
                    <a href="#" class="nav-link {{ $activeTab === 'menunggu-persetujuan' ? 'active' : '' }}"
                        wire:click.prevent="switchTab('menunggu-persetujuan')">
                        <i class="bi bi-question-circle"></i> Menunggu Persetujuan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link {{ $activeTab === 'riwayat-persetujuan' ? 'active' : '' }}"
                        wire:click.prevent="switchTab('riwayat-persetujuan')">
                        <i class="bi bi-clock-history"></i> Riwayat Fasilitasi
                        Riwayat Persetujuan
                    </a>
                </li>
            </ul>

            {{-- Tab Content --}}
            <div class="tab-content mt-4">
                @if ($activeTab === 'menunggu-persetujuan')
                    @livewire('peneliti.fasilitasi.persetujuan-fasilitasi-menunggu')
                @elseif ($activeTab === 'riwayat-persetujuan')
                    @livewire('peneliti.fasilitasi.riwayat-fasilitasi')
                @endif
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {


            window.Livewire.on('openModalDetailFasilitasi', () => {
                $('#modalDetailFasilitasi').modal('show');
            });
            window.Livewire.on('closeModalDetailFasilitasi', () => {
                $('#modalDetailFasilitasi').modal('hide');
            });
            window.Livewire.on('openModalDetailRiwayatFasilitasi', () => {
                $('#modalDetailRiwayatFasilitasi').modal('show');
            });
            window.Livewire.on('closeModalDetailFasilitasi', () => {
                $('#modalDetailFasilitasi').modal('hide');
            });
        });
    </script>
</div>
