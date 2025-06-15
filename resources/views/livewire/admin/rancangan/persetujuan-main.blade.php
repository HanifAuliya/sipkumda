@section('title', 'Pengecekan Berkas')

@section('manual')
    <div class="card  mb--2">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h3 class="mb-0">Menu Pengecekan Berkas Rancangan</h3>
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

<div class="card shadow-sm">
    {{-- Header --}}
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-2">Daftar Berkas untuk Verifikasi</h3>
            <small>Pastikan perhatikan tab di bawah! Tekan dan pilih tab untuk navigasi ke tab tujuan anda !</small>
        </div>
    </div>

    {{-- Penjelasan Menu --}}
    <div class="card-body">
        {{-- Tab --}}
        <ul class="nav nav-pills nav-fill flex-column flex-md-row" role="tablist">
            <li class="nav-item">
                <a href="#" class="nav-link {{ $activeTab === 'menunggu' ? 'active' : '' }}"
                    wire:click.prevent="switchTab('menunggu')">

                    {{-- Teks tombol saat loading --}}
                    <span wire:loading wire:target="switchTab('menunggu')"
                        class="spinner-border spinner-border-sm text-light"></span>
                    <span wire:loading wire:target="switchTab('menunggu')">Memuat Data...</span>
                    <span wire:loading.remove wire:target="switchTab('menunggu')">
                        <i class="ni ni-send mr-2"></i> Berkas Menunggu Persetujuan
                    </span>
                </a>
            </li>

            <li class="nav-item">
                <a href="#" class="nav-link {{ $activeTab === 'ditolak' ? 'active' : '' }}"
                    wire:click.prevent="switchTab('ditolak')">

                    {{-- Teks tombol saat loading --}}
                    <span wire:loading wire:target="switchTab('ditolak')"
                        class="spinner-border spinner-border-sm text-light"></span>
                    <span wire:loading wire:target="switchTab('ditolak')">Memuat Data...</span>
                    <span wire:loading.remove wire:target="switchTab('ditolak')">
                        <i class="ni ni-fat-remove mr-2"></i> Berkas Rancangan Ditolak
                    </span>
                </a>
            </li>

            <li class="nav-item">
                <a href="#" class="nav-link {{ $activeTab === 'riwayat' ? 'active' : '' }}"
                    wire:click.prevent="switchTab('riwayat')">

                    {{-- Teks tombol saat loading --}}
                    <span wire:loading wire:target="switchTab('riwayat')"
                        class="spinner-border spinner-border-sm text-light"></span>
                    <span wire:loading wire:target="switchTab('riwayat')">Memuat Data...</span>
                    <span wire:loading.remove wire:target="switchTab('riwayat')">
                        <i class="ni ni-check-bold mr-2"></i> Berkas Rancangan Disetujui
                    </span>
                </a>
            </li>
        </ul>

        {{-- Content --}}
        <div class="tab-content mt-4">
            @if ($activeTab === 'menunggu')
                @livewire('admin.rancangan.persetujuan-menunggu')
            @elseif ($activeTab === 'ditolak')
                @livewire('admin.rancangan.persetujuan-ditolak')
            @elseif ($activeTab === 'riwayat')
                @livewire('admin.rancangan.persetujuan-riwayat')
            @endif
        </div>
    </div>

    {{-- Script --}}
    <script>
        async function confirmSetujui(id) {
            // Tampilkan SweetAlert dengan textarea input
            const {
                value: catatan
            } = await Swal.fire({
                title: "Kamu Yakin ? Untuk Menyetujui Rancangan",
                input: "textarea",
                icon: 'question',
                inputLabel: "Tambahkan Catatan",
                inputPlaceholder: "Tambahkan catatan terkait persetujuan ini...",
                inputAttributes: {
                    "aria-label": "Tambahkan catatan terkait persetujuan ini"
                },
                showCancelButton: true,
                confirmButtonText: "Setujui",
                cancelButtonText: "Batal",
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                inputValidator: (value) => {
                    if (!value) {
                        return "Catatan tidak boleh kosong!";
                    }
                }
            });

            // Jika tombol konfirmasi ditekan dan catatan diisi
            if (catatan) {
                // Kirim data ke Livewire
                Livewire.dispatch("setujuiConfirmed", {
                    id: id,
                    catatan: catatan
                });
            }
        }
    </script>

</div>
