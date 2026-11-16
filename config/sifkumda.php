<?php

return [
    // Naskah yang lebih lama dari ini di satu tahap tampil sebagai "tertahan" di Laporan dan
    // pemegang gilirannya diingatkan setiap kelipatan hari ini.
    'hari_tertahan' => (int) env('SIFKUMDA_HARI_TERTAHAN', 7),

    // Super Admin pertama, dibuat sifkumda:siapkan bila belum ada Super Admin (hosting tanpa SSH).
    'superadmin' => [
        'nip' => env('SIFKUMDA_SUPERADMIN_NIP'),
        'nama' => env('SIFKUMDA_SUPERADMIN_NAMA'),
        'email' => env('SIFKUMDA_SUPERADMIN_EMAIL'),
    ],
];
