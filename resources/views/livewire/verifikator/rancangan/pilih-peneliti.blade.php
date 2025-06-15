{{-- Header --}}
@section('title', 'Pilih Peneliti')
@section('manual')
    <div class="card  mb--2">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h3 class="mb-0">Menu Pilih Peneliti</h3>
                <p class="description">
                    Pengajuan Rancangan Produk Hukum
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
    {{-- Header --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-0">Pilih Peneliti Rancangan</h3>
                <small>Pastikan perhatikan tab di bawah!</small>
            </div>
        </div>
        <div class="card-body">

            <ul class="nav nav-pills nav-fill flex-column flex-md-row" role="tablist">
                <li class="nav-item">
                    <a href="#" class="nav-link {{ $activeTab === 'menunggu-peneliti' ? 'active' : '' }}"
                        wire:click.prevent="switchTab('menunggu-peneliti')">

                        {{-- Teks saat loading --}}
                        <span wire:loading wire:target="switchTab('menunggu-peneliti')"
                            class="spinner-border spinner-border-sm text-light"></span>
                        <span wire:loading wire:target="switchTab('menunggu-peneliti')">Memuat Data...</span>
                        <span wire:loading.remove wire:target="switchTab('menunggu-peneliti')">
                            <i class="ni ni-send mr-2"></i> Menunggu Peneliti
                        </span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="#" class="nav-link {{ $activeTab === 'peneliti-ditugaskan' ? 'active' : '' }}"
                        wire:click.prevent="switchTab('peneliti-ditugaskan')">

                        {{-- Teks saat loading --}}
                        <span wire:loading wire:target="switchTab('peneliti-ditugaskan')"
                            class="spinner-border spinner-border-sm text-light"></span>
                        <span wire:loading wire:target="switchTab('peneliti-ditugaskan')">Memuat Data...</span>
                        <span wire:loading.remove wire:target="switchTab('peneliti-ditugaskan')">
                            <i class="ni ni-check-bold mr-2"></i> Peneliti Ditugaskan
                        </span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="#" class="nav-link {{ $activeTab === 'daftar-peneliti' ? 'active' : '' }}"
                        wire:click.prevent="switchTab('daftar-peneliti')">

                        {{-- Teks saat loading --}}
                        <span wire:loading wire:target="switchTab('daftar-peneliti')"
                            class="spinner-border spinner-border-sm text-light"></span>
                        <span wire:loading wire:target="switchTab('daftar-peneliti')">Memuat Data...</span>
                        <span wire:loading.remove wire:target="switchTab('daftar-peneliti')">
                            <i class="ni ni-single-02 mr-2"></i> Daftar Penelitian Berlangsung
                        </span>
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
                    @livewire('verifikator.rancangan.daftarpeneliti')
                @endif
            </div>
        </div>
    </div>

    {{-- Pilih Peneliti --}}
    <script>
        async function pilihUlangPeneliti(idRancangan) {
            // Daftar peneliti dari backend (hanya nama yang akan tampil)
            const inputOptions = @json($listPeneliti); // Format: { id: 'Nama Peneliti' }

            const {
                value: selectedPeneliti
            } = await Swal.fire({
                title: 'Pilih Peneliti',
                input: 'select',
                inputLabel: "Pilih Peneliti yang akan diperbarui, dibawah ini!  ",
                inputOptions: inputOptions, // Menampilkan nama peneliti
                inputPlaceholder: 'Pilih Peneliti',
                showCancelButton: true,
                confirmButtonText: 'Pilih',
                cancelButtonText: 'Batal',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Anda harus memilih peneliti!';
                    }
                },
            });

            if (selectedPeneliti) {
                // Kirim data ke Livewire
                Livewire.dispatch('pilihUlangPenelitiConfirmed', {
                    idRancangan: idRancangan,
                    idPeneliti: selectedPeneliti, // Kirim ID peneliti ke backend
                });
            }
        }
    </script>
    {{-- Reset Confirm --}}
    <script>
        async function confirmResetPeneliti(id) {

            const result = await Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Peneliti akan direset, dan status revisi akan kembali ke 'Menunggu Peneliti'.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Reset!',
                cancelButtonText: 'Batal',
            });

            if (result.isConfirmed) {
                Livewire.dispatch('resetPenelitiConfirmed', {
                    id: id
                });
            }
        }
    </script>
</div>
