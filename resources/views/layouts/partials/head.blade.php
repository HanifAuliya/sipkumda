{{-- Bagian <head> bersama untuk layout app & guest. --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="SIFKUMDA — Sistem Fasilitasi Produk Hukum Daerah Kabupaten Hulu Sungai Tengah.">
<meta name="theme-color" content="#172b4d">
<link rel="icon" href="{{ asset('assets/img/brand/favicon.ico') }}">

{{-- Pasang tema sebelum halaman dirender supaya tidak berkedip. --}}
<script>
    (() => {
        let theme = null;
        try { theme = localStorage.getItem('theme'); } catch (e) {}
        const dark = theme ? theme === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.classList.toggle('dark', dark);
    })();
</script>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
