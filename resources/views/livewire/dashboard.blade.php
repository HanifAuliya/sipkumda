@section('header', 'Selamat Datang, ' . Auth::user()->nama_user . '!')
@section('title', 'Dashboard')
<div>
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
                            <img src="../assets/img/theme/profile.png" class="avatar avatar-lg rounded-circle"
                                alt="Profil" />
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
                        <p class="description">
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
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title text-uppercase text-muted mb-0">
                                Rancangan Produk Hukum
                            </h5>
                            <span class="h2 font-weight-bold mb-0" id="jumlahRancangan">0</span> Data
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape bg-gradient-red text-white rounded-circle shadow">
                                <i class="bi bi-clipboard2-data"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-stats">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title text-uppercase text-muted mb-0">
                                Daftar Revisi Rancangan
                            </h5>
                            <span class="h2 font-weight-bold mb-0" id="jumlahRevisi">0</span> Data
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape bg-gradient-orange text-white rounded-circle shadow">
                                <i class="bi bi-pencil-square"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-stats">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title text-uppercase text-muted mb-0">
                                Fasilitasi Produk Hukum
                            </h5>
                            <span class="h2 font-weight-bold mb-0" id="jumlahFasilitasi">0</span> Data
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape bg-gradient-green text-white rounded-circle shadow">
                                <i class="bi bi-file-text"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-stats">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title text-uppercase text-muted mb-0">
                                Dokumentasi Produk Hukum
                            </h5>
                            <span class="h2 font-weight-bold mb-0" id="jumlahDokumentasi">0</span> Data
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow">
                                <i class="bi bi-folder-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // Ambil data jumlah dari Blade
                let targetCounts = {
                    jumlahRancangan: {{ $jumlahRancangan }},
                    jumlahRevisi: {{ $jumlahRevisi }},
                    jumlahFasilitasi: {{ $jumlahFasilitasi }},
                    jumlahDokumentasi: {{ $jumlahDokumentasi }}
                };

                // Fungsi untuk menghitung angka naik
                function animateCounter(id, target, duration = 2000) {
                    let element = document.getElementById(id);
                    if (!element) return;

                    let start = 0;
                    let stepTime = Math.abs(Math.floor(duration / target));
                    let timer = setInterval(function() {
                        start++;
                        element.textContent = start;
                        if (start >= target) {
                            clearInterval(timer);
                        }
                    }, stepTime);
                }

                // Intersection Observer untuk menunggu sampai elemen terlihat di layar
                let observer = new IntersectionObserver(function(entries) {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            // Tambahkan delay 3 detik sesuai loading screen
                            setTimeout(() => {
                                animateCounter("jumlahRancangan", targetCounts.jumlahRancangan);
                                animateCounter("jumlahRevisi", targetCounts.jumlahRevisi);
                                animateCounter("jumlahFasilitasi", targetCounts
                                    .jumlahFasilitasi);
                                animateCounter("jumlahDokumentasi", targetCounts
                                    .jumlahDokumentasi);
                            }, 3000); // Delay 3 detik sebelum mulai animasi

                            // Hentikan observer setelah animasi pertama kali berjalan
                            observer.disconnect();
                        }
                    });
                }, {
                    threshold: 0.5
                }); // Mulai animasi saat 50% elemen terlihat

                // Observasi semua elemen dengan ID yang akan dihitung
                observer.observe(document.getElementById("jumlahRancangan"));
            });
        </script>

    </div>

    <div class="card" style=" min-height: 300px; ">
        <div class="card-body">
            <div class="chart-container">
                <canvas id="chart-rancangan" class="chart-canvas"></canvas>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var ctx = document.getElementById("chart-rancangan").getContext("2d");

            var chartData = {
                labels: {!! json_encode(array_keys($chartData)) !!}, // Nama bulan di X
                datasets: [{
                    label: "Jumlah Pengajuan Rancangan Tahun {{ \Carbon\Carbon::now()->year }}",
                    backgroundColor: "rgba(255,99,132,0.2)",
                    borderColor: "rgba(255,99,132,1)",
                    borderWidth: 2,
                    pointBackgroundColor: "rgba(255,99,132,1)",
                    pointBorderColor: "#fff",
                    pointRadius: 5, // Ukuran titik pada garis
                    fill: true,
                    tension: 0.4, // Membuat garis lebih smooth
                    data: {!! json_encode(array_values($chartData)) !!}
                }]
            };

            var chartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 2000, // Durasi animasi dalam 2 detik
                    easing: "easeInOutQuart",
                    from: 0
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: "Jumlah Pengajuan Rancangan"
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: "Bulan"
                        }
                    }
                }
            };

            var chartCreated = false;

            // Intersection Observer untuk mendeteksi saat chart muncul di viewport
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !chartCreated) {
                        chartCreated =
                            true; // Set agar chart hanya dibuat sekali saat pertama kali terlihat
                        new Chart(ctx, {
                            type: "line",
                            data: chartData,
                            options: chartOptions
                        });
                    }
                });
            }, {
                threshold: 0.5
            }); // 50% dari chart harus terlihat sebelum animasi dimulai

            // Observasi elemen chart
            observer.observe(document.getElementById("chart-rancangan"));
        });
    </script>



    {{-- EndPage Content --}}
</div>
