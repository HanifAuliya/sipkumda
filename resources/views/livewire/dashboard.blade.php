@section('header', 'Selamat Datang, ' . Auth::user()->nama_user . '!')
@section('title', 'Dashboard')
<div>
    {{-- In work, do what you enjoy. --}}
    {{-- Profile Card --}}
    <div class="row mb-1">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex flex-column flex-md-row justify-content-between">
                    {{-- Informasi Departemen --}}
                    <div class="info-department d-flex flex-column mb-3 mb-md-0 w-100 w-md-auto">
                        <h3 class="font-weight-bolder mb-0">
                            {{ Auth::user()->getRoleNames()->first() ?? 'Tidak Ada Role' }}
                        </h3>
                        <p class="text-sm mb-0 font-weight-300">
                            {{ Auth::user()->perangkatDaerah->nama_perangkat_daerah }}
                        </p>
                    </div>
                    {{-- Informasi Individu --}}
                    <div
                        class="info-individual d-flex flex-column flex-md-row align-items-center justify-content-md-end w-100 w-md-auto">
                        <div class="d-flex flex-column text-md-end mb-3 mb-md-0 align-items-md-end mr-2">
                            <h3 class="font-weight-bolder mb-0">
                                {{ Auth::user()->nama_user }}
                            </h3>
                            <p class="mb-0">{{ Auth::user()->NIP }}</p>
                        </div>
                        <div class="d-flex justify-content-center justify-content-md-end align-items-center ms-md-3">
                            <img src="../assets/img/theme/team-4.jpg" class="avatar avatar-lg rounded-circle"
                                alt="Profil Muhammad Hanif Auliya" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Card Manual Pengguna --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex flex-column flex-md-row align-items-center justify-content-between">
                    {{-- Informasi Utama --}}
                    <div class="d-flex flex-column align-items-xs-center">
                        <h2 class="card-title mb-1">Manual Pengguna</h2>
                        <p class="text-sm mb-0 font-weight-300">
                            © Hak Cipta Bagian Hukum Sekretariat Daerah Kabupaten Hulu
                            Sungai Tengah.
                        </p>
                    </div>
                    {{-- Tombol Read More --}}
                    <a href="#" class="btn btn-outline-default mt-3 mt-md-0">Read More</a>
                </div>
            </div>
        </div>
    </div>
    {{-- Card stats --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-stats">
                {{-- Card body --}}
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title text-uppercase text-muted mb-0">
                                Rancangan Produk Hukum
                            </h5>
                            <span class="h2 font-weight-bold mb-0">350 Data</span>
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape bg-gradient-red text-white rounded-circle shadow">
                                <i class="ni ni-active-40"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-stats">
                {{-- Card body --}}
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title text-uppercase text-muted mb-0">
                                Daftar Revisi Rancangan
                            </h5>
                            <span class="h2 font-weight-bold mb-0">356 Data</span>
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape bg-gradient-orange text-white rounded-circle shadow">
                                <i class="ni ni-chart-pie-35"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-stats">
                {{-- Card body --}}
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title text-uppercase text-muted mb-0">
                                Fasilitasi Produk Hukum
                            </h5>
                            <span class="h2 font-weight-bold mb-0">924 Data</span>
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape bg-gradient-green text-white rounded-circle shadow">
                                <i class="ni ni-money-coins"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-stats">
                {{-- Card body --}}
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title text-uppercase text-muted mb-0">
                                Dokumentasi Produk Hukum
                            </h5>
                            <span class="h2 font-weight-bold mb-0">49 Data</span>
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow">
                                <i class="ni ni-chart-bar-32"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Tiga Card Sejajar --}}
    <div class="row mb-4">
        {{-- Card 1 --}}
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100">
                <img src="{{ asset('../assets/img/card-1.jpg') }}" class="card-img-top" alt="Image 1" />
                <div class="card-body d-flex flex-column">
                    <h3 class="card-title text-center">Perancangan Produk Hukum</h3>
                    <p class="card-text flex-grow-1">
                        Untuk Mengajukan Pengajuan Rancangan Produk Hukum.
                    </p>
                    <a href="" class="btn btn-outline-default mt-auto">Ajukan Rancangan</a>
                </div>
            </div>
        </div>
        {{-- Card 2 --}}
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100">
                <img src="../assets/img/card-2.jpg" class="card-img-top" alt="Image 2" />
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">Fasilitasi Produk Hukum</h5>
                    <p class="card-text flex-grow-1">
                        Mengajukan Fasilitasi Hasil setelah Rancangan Produk Hukum disetujui.
                    </p>
                    <a href="#" class="btn btn-outline-danger mt-auto">Ajukan Fasilitasi</a>
                </div>
            </div>
        </div>
        {{-- Card 3 --}}
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100">
                <img src="../assets/img/card-3.jpg" class="card-img-top" alt="Image 3" />
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">Dokumentasi Produk Hukum</h5>
                    <p class="card-text flex-grow-1">
                        Dokumentasi Produk Hukum yang telah disetujui oleh Bupati.
                    </p>
                    <button type="button" class="btn btn-outline-warning" disabled>Dokumentasi Produk Hukum</button>
                </div>
            </div>
        </div>
    </div>
    {{-- EndPage Content --}}
</div>
