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
    <meta property="og:image" content="{{ asset('assets/img/brand/logo.png') }}">
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
</head>


<body>
    <div class="container-fluid vh-100 d-flex align-items-center justify-content-center bg-light">
        {{ $slot }}
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
