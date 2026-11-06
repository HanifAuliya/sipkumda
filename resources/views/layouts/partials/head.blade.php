{{-- Bagian <head> bersama untuk layout app & guest. --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="SIFKUMDA — Sistem Fasilitasi Produk Hukum Daerah Kabupaten Hulu Sungai Tengah.">
<meta name="theme-color" content="#172b4d">
{{-- Tema gelap hanya lewat tombol di aplikasi: browser tidak boleh menggelapkan halaman sendiri. --}}
<meta name="color-scheme" content="only light">
<link rel="icon" href="{{ asset('assets/img/brand/favicon.ico') }}">

{{-- Tampilan awal selalu terang, tidak mengikuti tema Windows atau HP. Dipasang sebelum halaman dirender supaya tidak berkedip. --}}
<script>
    (() => {
        let theme = null;
        try { theme = localStorage.getItem('theme'); } catch (e) {}
        document.documentElement.classList.toggle('dark', theme === 'dark');

        // Menu yang diciutkan dipasang sebelum tampil, supaya lebarnya tidak melompat.
        try { document.documentElement.toggleAttribute('data-menu-ciut', localStorage.getItem('sidebar-collapsed') === 'true'); } catch (e) {}
    })();
</script>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
