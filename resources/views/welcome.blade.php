<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>SIFKUMDA</title>
    <meta name="description" content="" />
    <meta name="keywords" content="" />

    {{-- Favicons --}}
    <link href="{{ asset('assets/img/brand/favicon.ico') }}" rel="icon" />
    <link href="{{ asset('assets/img/brand/favicon.ico') }}" rel="apple-touch-icon" />

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet" />

    {{-- Vendor CSS Files --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/glightbox/3.2.0/css/glightbox.min.css" rel="stylesheet" />

    {{-- Main CSS File --}}
    <link href="{{ asset('assets/css/main.css') }}" rel="stylesheet" />
</head>

<body class="index-page">
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
            <a href="index.html" class="logo d-flex align-items-center me-auto me-lg-0">
                {{-- Uncomment the line below if you also wish to use an image logo --}}
                <img src="{{ asset('assets/img/brand/barabai.png') }}" alt="" />
                <img src="{{ asset('assets/img/brand/favicon.ico') }}" alt="" />
                <h1 class="sitename">SIFKUMDA</h1>
                <span>.</span>
            </a>

            <nav id="navmenu" class="navmenu">
                <ul>
                    <li>
                        <a href="#hero" class="active">
                            Home
                            <br />
                        </a>
                    </li>
                    <li><a href="#about">Tentang SIFKUMDA</a></li>
                    <li><a href="#services">Fasilitasi Produk Hukum</a></li>
                    <li><a href="#stats">Produk Hukum</a></li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>

            <a class="btn-getstarted" href="{{ Auth::check() ? route('dashboard') : route('login') }}">
                {{ Auth::check() ? 'Dashboard' : 'Login' }}
            </a>
        </div>
    </header>

    <main class="main">
        {{-- Hero Section --}}
        <section id="hero" class="hero section dark-background">
            <img src="{{ asset('assets/img/theme/sulangking.jpg') }}" alt="" data-aos="fade-in" />

            <div class="container">
                <div class="row justify-content-center text-center" data-aos="fade-up" data-aos-delay="100">
                    <div class="col-xl-8 col-lg-8">
                        <h2 class="fw-bold">Sistem Fasilitasi Produk Hukum Daerah</h2>
                        <h4>Kabupaten Hulu Sungai Tengah</h4>
                        <p>
                            <small>
                                Platform digital yang mempermudah fasilitasi produk hukum
                                daerah secara cepat, transparan, dan efisien.
                            </small>
                        </p>
                    </div>
                </div>

                <div class="row gy-4 mt-5 justify-content-center" data-aos="fade-up" data-aos-delay="200">
                    <div class="col-xl-4 col-md-4" data-aos="fade-up" data-aos-delay="300">
                        <a href="{{ route('login') }}" class="icon-box-link">
                            <div class="icon-box">
                                <i class="bi bi-file-earmark-text"></i> {{-- Ikon yang lebih relevan --}}
                                <h3>Pengajuan Produk Hukum</h3>
                                <p class="desc-text">
                                    Ajukan rancangan produk hukum daerah dengan mudah dan cepat melalui sistem digital.
                                </p>
                            </div>
                        </a>

                    </div>

                    <div class="col-xl-4 col-md-4" data-aos="fade-up" data-aos-delay="300">
                        <a href="{{ asset('assets/document/SIFKUMDA-PanduanLogin.pdf') }}" target="_blank"
                            class="text-decoration-none">
                            <div class="icon-box">
                                <i class="bi bi-person-lock"></i> {{-- Ikon Login --}}
                                <h3>Panduan Login</h3>
                                <p class="desc-text">
                                    Pelajari cara masuk ke sistem dan mengakses fitur SIFKUMDA.<br>
                                    <strong>Klik di sini untuk membuka panduan.</strong>
                                </p>
                            </div>
                        </a>
                    </div>

                </div>
            </div>
        </section>

        {{-- About Section --}}
        <section id="about" class="about section">
            <div class="container" data-aos="fade-up" data-aos-delay="100">
                <div class="row gy-4">
                    <div class="col-lg-6 order-1 order-lg-2">
                        <img src="{{ asset('assets/img/theme/Surat-Keputusan.jpg') }}" class="img-fluid"
                            alt="Tentang SIFKUMDA" />
                    </div>
                    <div class="col-lg-6 order-2 order-lg-1 content">
                        <h3>
                            SIFKUMDA - Sistem Informasi Fasilitasi Produk Hukum Daerah
                        </h3>
                        <p class="fst-italic">
                            SIFKUMDA adalah platform digital yang dirancang untuk
                            mempermudah proses penyusunan dan fasilitasi produk hukum daerah
                            secara efektif dan transparan.
                        </p>
                        <ul>
                            <li>
                                <i class="bi bi-check2-all"></i>
                                <span>
                                    Mengelola produk hukum daerah seperti
                                    <strong>Peraturan Bupati</strong>
                                    dan
                                    <strong>Surat Keputusan Bupati</strong>
                                    .
                                </span>
                            </li>
                            <li>
                                <i class="bi bi-check2-all"></i>
                                <span>
                                    Memfasilitasi
                                    <strong>
                                        pengajuan, pemeriksaan, penelitian, dan penyimpanan
                                    </strong>
                                    dokumen hukum secara digital.
                                </span>
                            </li>
                            <li>
                                <i class="bi bi-check2-all"></i>
                                <span>
                                    Meningkatkan efisiensi dan transparansi dalam penyusunan
                                    produk hukum dengan sistem yang terintegrasi.
                                </span>
                            </li>
                        </ul>
                        <p>
                            Dengan SIFKUMDA, setiap tahap dalam pembentukan produk hukum
                            dapat dilakukan secara lebih mudah dan cepat, memastikan bahwa
                            setiap dokumen hukum telah melalui proses yang sesuai dengan
                            standar dan regulasi yang berlaku.
                        </p>
                    </div>
                </div>
            </div>
        </section>
        {{-- /About Section --}}

        {{-- Clients Section (Tautan Penting) --}}
        <section id="clients" class="clients section">
            <div class="container" data-aos="fade-up" data-aos-delay="100">
                <h2 class="text-center mb-4">Tautan Penting</h2>
                <div class="swiper init-swiper">
                    <script type="application/json" class="swiper-config">
              {
                "loop": true,
                "speed": 600,
                "autoplay": {
                  "delay": 1000
                },
                "slidesPerView": "auto",
                "pagination": {
                  "el": ".swiper-pagination",
                  "type": "bullets",
                  "clickable": true
                },
                "breakpoints": {
                  "320": {
                    "slidesPerView": 2,
                    "spaceBetween": 40
                  },
                  "480": {
                    "slidesPerView": 3,
                    "spaceBetween": 50
                  },
                  "640": {
                    "slidesPerView": 4,
                    "spaceBetween": 60
                  },
                  "992": {
                    "slidesPerView": 6,
                    "spaceBetween": 80
                  }
                }
              }
            </script>
                    <div class="swiper-wrapper align-items-center">
                        <div class="swiper-slide">
                            <a href="https://diskominfo.hstkab.go.id" target="_blank">
                                <img src="https://ik.imagekit.io/bertawedding/ppid/logo%20lembaga/Kominfo%20(1).png?updatedAt=1728977390499"
                                    class="img-fluid" alt="Diskominfo HST" />
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="https://hstkab.go.id" target="_blank">
                                <img src="https://ik.imagekit.io/bertawedding/ppid/logo%20lembaga/Pemkab.png?updatedAt=1728977715130"
                                    class="img-fluid" alt="Pemkab HST" />
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="https://opendata.hstkab.go.id" target="_blank">
                                <img src="https://ik.imagekit.io/bertawedding/ppid/logo%20lembaga/OpenData.png?updatedAt=1728978182768"
                                    class="img-fluid" alt="Open Data HST" />
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="https://www.youtube.com/@mediacenterhst" target="_blank">
                                <img src="https://ik.imagekit.io/bertawedding/ppid/logo%20lembaga/Kominfo%20(2).png?updatedAt=1729128401636"
                                    class="img-fluid" alt="Media Center HST" />
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="https://ppidutama.kalselprov.go.id" target="_blank">
                                <img src="https://ik.imagekit.io/bertawedding/ppid/logo%20lembaga/PPID%20UTAMA.png?updatedAt=1728978540123"
                                    class="img-fluid" alt="PPID Utama Kalsel" />
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="https://lapor.go.id" target="_blank">
                                <img src="https://ik.imagekit.io/bertawedding/ppid/logo%20lembaga/LAPOR.png?updatedAt=1729127779226"
                                    class="img-fluid" alt="LAPOR" />
                            </a>
                        </div>
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
        </section>
        {{-- /Clients Section (Tautan) --}}

        {{-- Services Section --}}
        <section id="services" class="services section">
            {{-- Section Title --}}
            <div class="container section-title" data-aos="fade-up">
                <h2>Layanan Kami</h2>
                <p>Fasilitasi Produk Hukum Daerah Secara Cepat dan Transparan</p>
            </div>
            {{-- End Section Title --}}

            <div class="container">
                <div class="row gy-4">
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                        <div class="service-item position-relative">
                            <div class="icon">
                                <i class="bi bi-upload"></i>
                            </div>
                            <a href="#" class="stretched-link">
                                <h3>Pengajuan Produk Hukum</h3>
                            </a>
                            <p>
                                Ajukan rancangan produk hukum daerah secara digital dengan
                                proses yang lebih cepat dan transparan.
                            </p>
                        </div>
                    </div>
                    {{-- End Service Item --}}

                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                        <div class="service-item position-relative">
                            <div class="icon">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <a href="#" class="stretched-link">
                                <h3>Pemeriksaan dan Validasi</h3>
                            </a>
                            <p>
                                Proses verifikasi dan validasi dokumen dilakukan secara
                                sistematis untuk memastikan kelengkapan dan keabsahan.
                            </p>
                        </div>
                    </div>
                    {{-- End Service Item --}}

                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                        <div class="service-item position-relative">
                            <div class="icon">
                                <i class="bi bi-pencil-square"></i>
                            </div>
                            <a href="#" class="stretched-link">
                                <h3>Penyusunan dan Penelitian</h3>
                            </a>
                            <p>
                                Dokumen yang diajukan dapat diteleti dengan mudah,
                                memungkinkan kolaborasi yang lebih efektif.
                            </p>
                        </div>
                    </div>
                    {{-- End Service Item --}}

                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
                        <div class="service-item position-relative">
                            <div class="icon">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                            <a href="#" class="stretched-link">
                                <h3>Dokumentasi dan Arsip</h3>
                            </a>
                            <p>
                                Seluruh dokumen yang telah difasilitasi akan tersimpan dalam
                                sistem secara aman dan dapat diakses kapan saja.
                            </p>
                        </div>
                    </div>
                    {{-- End Service Item --}}

                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">
                        <div class="service-item position-relative">
                            <div class="icon">
                                <i class="bi bi-globe"></i>
                            </div>
                            <a href="#" class="stretched-link">
                                <h3>Akses Mudah dan Terintegrasi</h3>
                            </a>
                            <p>
                                Platform berbasis web yang dapat diakses oleh seluruh pihak
                                terkait secara aman dan efisien.
                            </p>
                        </div>
                    </div>
                    {{-- End Service Item --}}

                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="600">
                        <div class="service-item position-relative">
                            <div class="icon">
                                <i class="bi bi-chat-square-text"></i>
                            </div>
                            <a href="#" class="stretched-link">
                                <h3>Bantuan dan Dukungan</h3>
                            </a>
                            <p>
                                Dapatkan bantuan teknis dan informasi terkait proses
                                fasilitasi produk hukum kapan saja.
                            </p>
                        </div>
                    </div>
                    {{-- End Service Item --}}
                </div>
            </div>
        </section>
        {{-- /Services Section --}}

        {{-- Stats Section --}}

        <section id="stats" class="stats section">
            <div class="container" data-aos="fade-up" data-aos-delay="100">
                <div class="row gy-4 align-items-center justify-content-between">
                    <div class="col-lg-5">
                        <img src="{{ asset('assets/img/theme/card-1.jpg') }}" alt="" class="img-fluid" />
                    </div>
                    <div class="col-lg-6">
                        <h3 class="fw-bold fs-2 mb-3">Statistik Produk Hukum</h3>
                        <p>
                            Sistem ini mencatat berbagai jenis produk hukum daerah, termasuk
                            <strong>Peraturan Bupati</strong> dan <strong>Surat Keputusan Bupati</strong>.
                            Berikut adalah jumlah produk hukum yang telah tercatat dalam sistem:
                        </p>

                        <div class="row gy-4">
                            <!-- Surat Keputusan Rancangan Produk Hukum -->
                            <div class="col-lg-6">
                                <div class="stats-item d-flex">
                                    <i class="bi bi-file-earmark-text flex-shrink-0"></i>
                                    <div>
                                        <span data-purecounter-start="0" data-purecounter-end="{{ $sk_rancangan }}"
                                            data-purecounter-duration="1" class="purecounter"></span>
                                        <p>
                                            <span>Rancangan Produk Hukum</span>
                                            <strong>Surat Keputusan</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Keputusan Bupati Rancangan Produk Hukum -->
                            <div class="col-lg-6">
                                <div class="stats-item d-flex">
                                    <i class="bi bi-file-earmark-text flex-shrink-0"></i>
                                    <!-- Ikon yang sedikit berbeda -->
                                    <div>
                                        <span data-purecounter-start="0" data-purecounter-end="{{ $kb_rancangan }}"
                                            data-purecounter-duration="1" class="purecounter"></span>
                                        <p>
                                            <span>Rancangan Produk Hukum</span>
                                            <strong>Keputusan Bupati</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Surat Keputusan Dokumentasi Produk Hukum -->
                            <div class="col-lg-6">
                                <div class="stats-item d-flex">
                                    <i class="bi bi-folder flex-shrink-0"></i>
                                    <div>
                                        <span data-purecounter-start="0" data-purecounter-end="{{ $sk_dokumentasi }}"
                                            data-purecounter-duration="1" class="purecounter"></span>
                                        <p>
                                            <span>Dokumentasi Produk Hukum</span>
                                            <strong>Surat Keputusan</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Keputusan Bupati Dokumentasi Produk Hukum -->
                            <div class="col-lg-6">
                                <div class="stats-item d-flex">
                                    <i class="bi bi-folder-folder flex-shrink-0"></i>
                                    <!-- Ikon yang sedikit berbeda -->
                                    <div>
                                        <span data-purecounter-start="0" data-purecounter-end="{{ $kb_dokumentasi }}"
                                            data-purecounter-duration="1" class="purecounter"></span>
                                        <p>
                                            <span>Dokumentasi Produk Hukum</span>
                                            <strong>Keputusan Bupati</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </section>




        {{-- /Stats Section --}}

        {{-- Contact Section --}}
        <section id="contact" class="contact section">
            {{-- Section title --}}
            <div class="container section-title" data-aos="fade-up">
                <h2>Kontak Kami</h2>
                <p>Hubungi Kami untuk Informasi Lebih Lanjut</p>
            </div>
            {{-- End Section Title --}}

            <div class="container" data-aos="fade-up" data-aos-delay="100">
                <div class="mb-4" data-aos="fade-up" data-aos-delay="200">
                    <iframe style="border: 0; width: 100%; height: 270px"
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15934.265820741635!2d115.38191621594372!3d-2.5891951377680394!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2de420cf5f1f93b7%3A0xa81c2d6ec6b4e62d!2sJl.%20Perwira%2C%20Barabai%20Sel.%2C%20Kec.%20Barabai%2C%20Kabupaten%20Hulu%20Sungai%20Tengah%2C%20Kalimantan%20Selatan%2071315!5e0!3m2!1sen!2sid!4v1713456789012!5m2!1sen!2sid"
                        frameborder="0" allowfullscreen="" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
                {{-- End Google Maps --}}
            </div>
        </section>
        {{-- /Contact Section --}}
    </main>

    {{-- Footer Section --}}
    <footer id="footer" class="footer dark-background">
        <div class="footer-top">
            <div class="container">
                <div class="row gy-4">
                    <div class="col-lg-4 col-md-6 footer-about">
                        <a href="index.html" class="logo d-flex align-items-center">
                            <span class="sitename">SIFKUMDA</span>
                        </a>
                        <div class="footer-contact pt-3">
                            <p>
                                C98M+5R3, Jl. Perwira, Barabai Sel., Kec. Barabai, Kabupaten
                                Hulu Sungai Tengah, Kalimantan Selatan 71315
                            </p>
                            <p>Kalimantan Selatan, Indonesia</p>
                            <p class="mt-3">
                                <strong>Phone:</strong>
                                <span>-</span>
                            </p>
                            <p>
                                <strong>Email:</strong>
                                <span>-</span>
                            </p>
                        </div>
                        <div class="social-links d-flex mt-4">
                            <a href="https://www.facebook.com/SetdaHSTMenyala" target="_blank">
                                <i class="bi bi-facebook"></i>
                            </a>
                            <a href="https://www.instagram.com/setdahstmenyala/" target="_blank">
                                <i class="bi bi-instagram"></i>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-3 footer-links">
                        <h4>Informasi Publik</h4>
                        <ul>
                            <li>
                                <i class="bi bi-chevron-right"></i>
                                <a href="#">Beranda</a>
                            </li>
                            <li>
                                <i class="bi bi-chevron-right"></i>
                                <a href="#">Tentang Aplikasi</a>
                            </li>
                            <li>
                                <i class="bi bi-chevron-right"></i>
                                <a href="#">Peraturan dan Kebijakan</a>
                            </li>
                            <li>
                                <i class="bi bi-chevron-right"></i>
                                <a href="#">Hubungi Kami</a>
                            </li>
                        </ul>
                    </div>

                    <div class="col-lg-2 col-md-3 footer-links">
                        <h4>Layanan</h4>
                        <ul>
                            <li>
                                <i class="bi bi-chevron-right"></i>
                                <a href="#">Pengajuan Produk Hukum</a>
                            </li>
                            <li>
                                <i class="bi bi-chevron-right"></i>
                                <a href="#">Pemeriksaan dan Validasi</a>
                            </li>
                            <li>
                                <i class="bi bi-chevron-right"></i>
                                <a href="#">Penyusunan dan Revisi</a>
                            </li>
                            <li>
                                <i class="bi bi-chevron-right"></i>
                                <a href="#">Dokumentasi dan Arsip</a>
                            </li>
                            <li>
                                <i class="bi bi-chevron-right"></i>
                                <a href="#">Dukungan dan Bantuan</a>
                            </li>
                        </ul>
                    </div>

                    <div class="col-lg-4 col-md-12 footer-links">
                        <h4>Unit Terkait</h4>
                        <ul>
                            <li>
                                <i class="bi bi-chevron-right"></i>
                                <a href="https://hstkab.go.id" target="_blank">
                                    Pemerintah Kabupaten HST
                                </a>
                            </li>
                            <li>
                                <i class="bi bi-chevron-right"></i>
                                <a href="https://diskominfo.hstkab.go.id" target="_blank">
                                    Dinas Kominfo HST
                                </a>
                            </li>
                            <li>
                                <i class="bi bi-chevron-right"></i>
                                <a href="https://opendata.hstkab.go.id" target="_blank">
                                    Open Data HST
                                </a>
                            </li>
                            <li>
                                <i class="bi bi-chevron-right"></i>
                                <a href="https://lapor.go.id" target="_blank">
                                    Layanan Aspirasi Publik
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="copyright">
            <div class="container text-center">
                <p>
                    ©
                    <span>2024</span>
                    <strong class="px-1 sitename">SIFKUMDA</strong>
                    <span>Semua Hak Dilindungi</span>
                </p>
                <div class="credits">
                    Dikembangkan oleh
                    <a href="#">Bagian Hukum Sekretariat Daerah Kabupaten Hulu Sungai Tengah</a>
                </div>
            </div>
        </div>
    </footer>
    {{-- /Footer Section --}}

    {{-- Scroll Top --}}
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    {{-- Preloader --}}
    <div id="preloader"></div>

    {{-- Vendor JS Files --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/bootstrapmade/php-email-form/validate.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/glightbox/3.2.0/js/glightbox.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/imagesloaded/5.0.0/imagesloaded.pkgd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/isotope-layout/3.0.6/isotope.pkgd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@srexi/purecounterjs@1.5.0/dist/purecounter_vanilla.js"></script>

    {{-- Main JS File --}}
    <script src="{{ asset('assets/js/main.js') }}"></script>
</body>

</html>
