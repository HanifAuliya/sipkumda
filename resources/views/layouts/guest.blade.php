<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Title Dinamis --}}
    <title>@yield('title', 'Login - SIPKUMDA')</title>

    {{-- Meta Description --}}
    <meta name="description"
        content="Login ke SIPKUMDA untuk mengelola rancangan produk hukum dengan lebih mudah dan efisien.">

    {{-- Meta Keywords --}}
    <meta name="keywords"
        content="SIPKUMDA, produk hukum, peraturan bupati, surat keputusan, sistem hukum digital, login SIPKUMDA">

    {{-- Meta Robots --}}
    <meta name="robots" content="index, follow"> {{-- atau 'noindex, nofollow' jika ingin halaman login tidak terindeks --}}

    {{-- Open Graph untuk SEO Sosial Media --}}
    <meta property="og:title" content="Login - SIPKUMDA">
    <meta property="og:description" content="Akses SIPKUMDA untuk mengelola produk hukum dengan lebih mudah.">
    <meta property="og:image" content="{{ asset('assets/img/brand/favicon.ico') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Login - SIPKUMDA">
    <meta name="twitter:description" content="Akses SIPKUMDA untuk mengelola produk hukum dengan lebih mudah.">
    <meta name="twitter:image" content="{{ asset('assets/img/brand/logo.png') }}">

    {{-- Canonical URL untuk menghindari duplikasi konten --}}
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('assets/img/brand/favicon.ico') }}" type="image/png" />

    {{-- Bootstrap CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="{{ asset('assets/css/guest.css') }}" />

</head>


<body>
    <div class="container-fluid vh-100 d-flex align-items-center justify-content-center bg-light">
        <div class="row w-100 h-100">
            {{-- Kiri - Gambar dan Penjelasan --}}
            <div class="col-md-7 d-flex flex-column justify-content-center align-items-center px-4 px-md-5">
                {{-- Gambar (Logo SIPKUMDA) --}}
                <div class="mb-4 animated-logo">
                    <img src="{{ asset('assets/img/brand/loading_screen.png') }}" alt="SIPKUMDA Logo" class="img-fluid"
                        style="max-width: 200px;">
                </div>

                {{-- Judul Besar SIPKUMDA dengan Animasi --}}
                <div class="text-center animated-title">
                    <h1 class="fw-bold text-warning display-5 display-md-3 mb-2">SIFKUMDA</h1>
                    <h5 class="text-muted fs-6 fs-md-5">Sistem Fasilitasi Produk Hukum Daerah</h5>
                </div>

                {{-- Penjelasan dengan Animasi --}}
                <div class="text-center mt-4 animated-description">
                    <p class="text-muted fs-6 fs-md-5">
                        Platform digital yang mempermudah
                        <span class="text-primary fw-semibold">pengajuan</span>,
                        <span class="text-primary fw-semibold">fasilitasi</span>, dan
                        <span class="text-primary fw-semibold">dokumentasi</span> produk hukum daerah secara elektronik.
                        Dengan sistem ini, setiap perangkat daerah dapat mengelola dan memantau rancangan produk hukum
                        secara lebih <span class="text-primary fw-semibold">transparan</span>,
                        <span class="text-primary fw-semibold">efisien</span>, dan
                        <span class="text-primary fw-semibold">terintegrasi</span>.
                    </p>
                </div>


            </div>
            {{ $slot }}
        </div>
    </div>
    {{-- Bootstrap JS Bundle  --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
