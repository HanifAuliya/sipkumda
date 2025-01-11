{{-- 
=========================================================
* Argon Dashboard - v1.2.0
=========================================================
* Product Page: https://www.creative-tim.com/product/argon-dashboard

* Copyright  Creative Tim (http://www.creative-tim.com)
* Coded by www.creative-tim.com
=========================================================
* The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
--}}
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Start your development with a Dashboard for Bootstrap 4." />
    <meta name="author" content="Creative Tim" />
    <title>@yield('title', 'Default Title')</title>
    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('../assets/img/brand/favicon.ico') }}" type="image/png" />
    {{-- Fonts --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" />
    {{-- Icons --}}
    <link rel="stylesheet" href="../assets/vendor/nucleo/css/nucleo.css" type="text/css" />
    <link rel="stylesheet" href="../assets/vendor/@fortawesome/fontawesome-free/css/all.min.css" type="text/css" />
    {{-- Argon CSS --}}
    <link rel="stylesheet" href="../assets/css/argon.css?v=1.2.0" type="text/css" />

    {{-- Custom CSS --}}
    <link rel="stylesheet" href="{{ asset('../assets/css/guestauth.css') }}" />
</head>

<body class="bg-default">
    {{-- Navbar --}}
    <nav id="navbar-main" class="navbar navbar-horizontal navbar-transparent navbar-main navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.html">
                <img src="../assets/img/barabai.png" />
                <p class="text-white  font-weight-900 mb-0 ml-2">SEKDA HST</p>
            </a>

            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-collapse"
                aria-controls="navbar-collapse" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="navbar-collapse navbar-custom-collapse collapse" id="navbar-collapse">
                <div class="navbar-collapse-header">
                    <div class="row">
                        <div class="col-6 collapse-brand">
                            <a href="dashboard.html">
                                <img src="../assets/img/brand/sipkumda-header.png" />
                            </a>
                        </div>
                        <div class="col-6 collapse-close">
                            <button type="button" class="navbar-toggler" data-toggle="collapse"
                                data-target="#navbar-collapse" aria-controls="navbar-collapse" aria-expanded="false"
                                aria-label="Toggle navigation">
                            </button>
                        </div>
                    </div>
                </div>
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a href="dashboard.html" class="nav-link">
                            <span class="nav-link-inner--text">Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="login.html" class="nav-link">
                            <span class="nav-link-inner--text">Login</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="register.html" class="nav-link">
                            <span class="nav-link-inner--text">Register</span>
                        </a>
                    </li>
                </ul>
                <hr class="d-lg-none" />
                <ul class="navbar-nav align-items-lg-center ml-lg-auto">
                    <li class="nav-item">
                        <a class="nav-link nav-link-icon"
                            href="https://www.facebook.com/pages/Sekretariat-Daerah-Kabupaten-Hulu-Sungai-Tengah/167518947528767"
                            target="_blank" data-toggle="tooltip" data-original-title="Like us on Facebook">
                            <i class="fab fa-facebook-square"></i>
                            <span class="nav-link-inner--text d-lg-none">Facebook</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-icon" href="https://www.instagram.com/creativetimofficial"
                            target="_blank" data-toggle="tooltip" data-original-title="Follow us on Instagram">
                            <i class="fab fa-instagram"></i>
                            <span class="nav-link-inner--text d-lg-none">Instagram</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-icon" href="https://twitter.com/creativetim" target="_blank"
                            data-toggle="tooltip" data-original-title="Follow us on Twitter">
                            <i class="fab fa-twitter-square"></i>
                            <span class="nav-link-inner--text d-lg-none">Twitter</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-icon" href="https://github.com/creativetimofficial" target="_blank"
                            data-toggle="tooltip" data-original-title="Star us on Github">
                            <i class="fab fa-github"></i>
                            <span class="nav-link-inner--text d-lg-none">Github</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    {{-- Main content --}}
    <div class="main-content">
        {{-- Header --}}
        <div class="header py-7 py-lg-8 pt-lg-9 bg-image">
            <style>
                @keyframes fadeInBackground {
                    0% {
                        opacity: 0;
                        transform: scale(1.05);
                    }

                    100% {
                        opacity: 1;
                        transform: scale(1);
                    }
                }

                .bg-image {
                    animation: fadeInBackground 2s ease-in-out forwards;
                    background-image: url("../assets/img/anggang.webp");
                    background-size: cover;
                    background-repeat: no-repeat;
                }
            </style>
            <div class="container">
                <div class="header-body text-center mb-7">
                    <div class="row justify-content-center">
                        <div class="col-xl-5 col-lg-6 col-md-8 px-5">
                            <img src="../assets/img/brand/sipkumda.png" />
                            <h1 class="text-white">SIPKUMDA</h1>
                            <p class="text-lead text-white">
                                Elektronik Sistem Informasi Produk Hukum Daerah Hulu Sungai Tengah
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Page content --}}
        @yield('content')
    </div>
    {{-- Footer --}}
    <footer class="py-5" id="footer-main">
        <div class="container">
            <div class="row align-items-center justify-content-xl-between">
                <div class="col-xl-6">
                    <div class="copyright text-center text-xl-left text-muted">
                        &copy; 2020
                        <a href="https://www.creative-tim.com" class="font-weight-bold ml-1" target="_blank">
                            Creative Tim
                        </a>
                    </div>
                </div>
                <div class="col-xl-6">
                    <ul class="nav nav-footer justify-content-center justify-content-xl-end">
                        <li class="nav-item">
                            <a href="https://www.creative-tim.com" class="nav-link" target="_blank">
                                Creative Tim
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="https://www.creative-tim.com/presentation" class="nav-link" target="_blank">
                                About Us
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="http://blog.creative-tim.com" class="nav-link" target="_blank">
                                Blog
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="https://github.com/creativetimofficial/argon-dashboard/blob/master/LICENSE.md"
                                class="nav-link" target="_blank">
                                MIT License
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
    {{-- Scripts --}}
    <script>
        window.addEventListener('load', function() {
            const bgImage = document.querySelector('.bg-image');
            if (bgImage) {
                bgImage.classList.add('bg-image-loaded');
            }
        });
    </script>
    {{-- Argon Scripts --}}
    {{-- Core --}}
    <script src="../assets/vendor/jquery/dist/jquery.min.js"></script>
    <script src="../assets/vendor/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/vendor/js-cookie/js.cookie.js"></script>
    <script src="../assets/vendor/jquery.scrollbar/jquery.scrollbar.min.js"></script>
    <script src="../assets/vendor/jquery-scroll-lock/dist/jquery-scrollLock.min.js"></script>
    {{-- Argon JS --}}
    <script src="../assets/js/argon.js?v=1.2.0"></script>
</body>

</html>
