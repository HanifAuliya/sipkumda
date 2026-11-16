<?php

/*
|--------------------------------------------------------------------------
| JDIH publik
|--------------------------------------------------------------------------
| Halaman pencarian produk hukum untuk umum (/produk-hukum). Batas request
| dihitung per IP per menit dan hanya berlaku untuk pencarian kata kunci
| (batas_halaman) serta unduhan PDF (batas_unduhan). Naikkan bila banyak
| pengunjung berbagi satu IP (mis. jaringan kantor).
*/

return [
    'batas_halaman' => (int) env('JDIH_RATE_LIMIT', 120),
    'batas_unduhan' => (int) env('JDIH_DOWNLOAD_LIMIT', 30),

    'per_halaman' => 12,

    // Lama cache halaman publik (detik): [segar, maksimal disajikan sambil diperbarui].
    'cache' => [300, 1800],

    // Header Cache-Control untuk browser/proxy (detik).
    'http_cache' => (int) env('JDIH_HTTP_CACHE', 60),
];
