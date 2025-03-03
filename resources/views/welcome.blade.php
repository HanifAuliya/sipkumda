<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPKUMDA - Sistem Pembentukan Produk Hukum Daerah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body.dark-mode {
            background-color: #121212;
            color: white;
        }

        .dark-mode .navbar,
        .dark-mode .footer {
            background-color: #1e1e1e;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">SIPKUMDA</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#tentang">Tentang</a></li>
                    <li class="nav-item"><a class="nav-link" href="#fitur">Fitur</a></li>
                    <li class="nav-item"><a class="nav-link" href="#alur">Alur Kerja</a></li>
                    <li class="nav-item"><a class="nav-link" href="#kontak">Kontak</a></li>
                    <li class="nav-item">
                        <button class="btn btn-outline-dark" id="toggleDarkMode">🌙</button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="text-center py-5 bg-primary text-white">
        <div class="container">
            <h1>Selamat Datang di SIPKUMDA</h1>
            <p>Sistem Pembentukan Produk Hukum Daerah yang efisien dan transparan</p>
        </div>
    </header>

    <section id="tentang" class="py-5 container">
        <h2 class="text-center">Tentang SIPKUMDA</h2>
        <p class="text-center">SIPKUMDA adalah sistem digital untuk membantu proses penyusunan, revisi, dan fasilitasi
            produk hukum daerah secara lebih cepat dan terstruktur.</p>
    </section>

    <section id="fitur" class="py-5 bg-light container">
        <h2 class="text-center">Fitur Utama</h2>
        <ul>
            <li>Pengajuan Rancangan Produk Hukum</li>
            <li>Revisi dan Fasilitasi</li>
            <li>Verifikasi dan Persetujuan</li>
            <li>Dokumentasi Produk Hukum</li>
        </ul>
    </section>

    <section id="alur" class="py-5 container">
        <h2 class="text-center">Alur Kerja</h2>
        <p class="text-center">SIPKUMDA mempermudah proses pembentukan hukum daerah dengan tahapan digital yang
            transparan.</p>
    </section>

    <footer class="text-center py-3 bg-dark text-white">
        <p>&copy; 2025 SIPKUMDA. Semua Hak Dilindungi.</p>
    </footer>

    <script>
        document.getElementById("toggleDarkMode").addEventListener("click", function() {
            document.body.classList.toggle("dark-mode");
        });
    </script>
</body>

</html>
