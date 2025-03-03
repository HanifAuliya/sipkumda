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
                                Arsip Produk Hukum
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


    <div class="row">
        {{-- Pie Chart - Jenis Rancangan --}}
        <div class="col-md-6">
            <div class="card shadow-sm border-0 chart-card ">
                <div class="card-header">
                    <h3 class="mb-0">Total Rancangan</h3>
                </div>
                <div class="card-body d-flex justify-content-center align-items-center">
                    <canvas id="pieChart" style="max-width: 280px; max-height: 280px;"></canvas>
                </div>
            </div>
        </div>


        {{-- Bar Chart - Jumlah Pengajuan per Bulan --}}
        <div class="col-md-6">
            <div class="card shadow-sm border-0 chart-card " style="max-height: 300px;">
                <div class="card-header">
                    <h3 class="mb-0">Pengajuan Rancangan (Tahun ini)</h3>
                </div>
                <div class="card-body">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
        </div>


        <div class="col-md-12">
            <div class="card shadow-sm border-0 chart-card">
                <div class="card-header d-flex justify-content-between align-items-center" style=" max-height: 300px;">
                    <h3 class="mb-0">📈 Lama Pembuatan Rancangan (Hari)</h3>
                </div>
                <div class="card-body">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>
        </div>

    </div>


    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let pieCtx = document.getElementById('pieChart').getContext('2d');
            let barCtx = document.getElementById('barChart').getContext('2d');

            let pieChart, barChart;

            function updateCharts(chartData) {
                console.log("📊 Data Chart.js diterima:", chartData); // Debugging

                if (pieChart) pieChart.destroy();
                if (barChart) barChart.destroy();

                // Pie Chart
                pieChart = new Chart(pieCtx, {
                    type: 'pie',
                    data: {
                        labels: chartData.pie.labels,
                        datasets: [{
                            data: chartData.pie.data,
                            backgroundColor: ['rgba(54, 162, 235, 0.8)', 'rgba(255, 206, 86, 0.8)'],
                            hoverOffset: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 1500, // Memperlambat animasi agar lebih smooth
                            easing: 'easeOutQuart' // Efek animasi lebih smooth
                        }
                    }
                });

                // Warna untuk Bar Chart
                const colors = {
                    rancanganBupati: 'rgba(54, 162, 235, 0.8)', // Biru untuk Peraturan Bupati
                    keputusanBupati: 'rgba(255, 99, 132, 0.8)' // Merah untuk Surat Keputusan
                };

                // Bar Chart (Stacked)
                barChart = new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                                label: 'Peraturan Bupati',
                                data: chartData.rancangan_bupati,
                                backgroundColor: colors.rancanganBupati,
                                borderColor: colors.rancanganBupati.replace('0.8', '1'),
                                borderWidth: 1
                            },
                            {
                                label: 'Surat Keputusan',
                                data: chartData.keputusan_bupati,
                                backgroundColor: colors.keputusanBupati,
                                borderColor: colors.keputusanBupati.replace('0.8', '1'),
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 1200,
                            easing: 'easeOutBack'
                        },
                        scales: {
                            x: {
                                stacked: true // Mengaktifkan tampilan Stacked untuk sumbu X
                            },
                            y: {
                                stacked: true, // Mengaktifkan tampilan Stacked untuk sumbu Y
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 5, // Mengatur skala Y agar kelipatan 5
                                }
                            }
                        }
                    }
                });

                // Tambahkan efek fade-in setelah chart muncul
                document.querySelector('.chart-container').classList.add('show-chart');
            }

            // Efek animasi saat discroll menggunakan Intersection Observer API
            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        updateCharts(@json($chartData));
                        observer.unobserve(entry.target); // Hentikan observer setelah muncul
                    }
                });
            }, {
                threshold: 0.5
            });

            observer.observe(document.querySelector('.chart-container'));

            // Event dari Livewire untuk memperbarui data saat tahun diubah
            window.livewire.on('refreshChart', (chartData) => {
                updateCharts(chartData);
            });
        });
    </script>

    <style>
        /* Tambahkan efek fade-in saat chart muncul */
        .chart-container {
            opacity: 0;
            transform: translateY(50px);
            transition: opacity 1s ease-out, transform 1s ease-out;
        }

        .chart-container.show-chart {
            opacity: 1;
            transform: translateY(0);
        }
    </style>

    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let lineCtx = document.getElementById('lineChart').getContext('2d');
            let lineChart;

            function updateLineChart(chartData) {

                if (lineChart) lineChart.destroy();

                // Warna untuk setiap tahap
                const colors = {
                    rancangan: 'rgba(54, 162, 235, 0.8)', // Biru
                    revisi: 'rgba(255, 159, 64, 0.8)', // Orange
                    fasilitasi: 'rgba(75, 192, 192, 0.8)', // Hijau
                    dokumentasi: 'rgba(153, 102, 255, 0.8)' // Ungu
                };

                // Line Chart
                lineChart = new Chart(lineCtx, {
                    type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                                label: 'Rancangan Disetujui',
                                data: chartData.rancangan,
                                backgroundColor: colors.rancangan,
                                borderColor: colors.rancangan.replace('0.8', '1'),
                                fill: false,
                                tension: 0.4
                            },
                            {
                                label: 'Revisi Selesai',
                                data: chartData.revisi,
                                backgroundColor: colors.revisi,
                                borderColor: colors.revisi.replace('0.8', '1'),
                                fill: false,
                                tension: 0.4
                            },
                            {
                                label: 'Fasilitasi Divalidasi',
                                data: chartData.fasilitasi,
                                backgroundColor: colors.fasilitasi,
                                borderColor: colors.fasilitasi.replace('0.8', '1'),
                                fill: false,
                                tension: 0.4
                            },
                            {
                                label: 'Dokumentasi Arsip',
                                data: chartData.dokumentasi,
                                backgroundColor: colors.dokumentasi,
                                borderColor: colors.dokumentasi.replace('0.8', '1'),
                                fill: false,
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 1000,
                            easing: 'easeInOutCubic'
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 5, // Sesuaikan dengan data (misalnya kelipatan 5 atau 10)
                                    maxTicksLimit: 10 // Batasi jumlah label agar tidak terlalu ramai
                                }
                            }
                        }

                    }
                });
            }

            // Load pertama kali
            updateLineChart(@json($lineChartData));

            // Event dari Livewire
            window.livewire.on('refreshLineChart', (chartData) => {
                updateLineChart(chartData);
            });
        });
    </script>
    <style>
        .chart-card {
            min-height: 400px;
            display: flex;
            flex-direction: column;
        }

        .chart-card .card-body {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>

</div>
