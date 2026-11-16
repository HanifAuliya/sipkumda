<?php

/*
|--------------------------------------------------------------------------
| Notifikasi WhatsApp
|--------------------------------------------------------------------------
| driver:
|   log  : pesan hanya dicatat di storage/logs (bawaan, untuk pengembangan).
|   gowa : dikirim lewat GOWA (go-whatsapp-web-multidevice) yang berjalan di server sendiri;
|          nomor kantor disambungkan dengan scan QR dari menu WhatsApp di akun Admin.
| Lihat docs/DEPLOY.md bagian WhatsApp.
*/

return [
    'driver' => env('WA_DRIVER', 'log'),

    'gowa' => [
        'url' => rtrim((string) env('WA_GOWA_URL', 'http://127.0.0.1:3000'), '/'),
        'user' => env('WA_GOWA_USER'),
        'password' => env('WA_GOWA_PASSWORD'),
        // Nama perangkat di GOWA (v8 ke atas). Dibuat otomatis bila belum ada.
        'device' => env('WA_GOWA_DEVICE', 'sifkumda'),
    ],

    // Jeda acak antarpesan (detik), supaya pengiriman tidak terlihat seperti spam.
    'jeda_min' => (int) env('WA_JEDA_MIN', 2),
    'jeda_max' => (int) env('WA_JEDA_MAX', 5),

    // Pesan yang tertunda lebih lama dari ini tidak dikirim lagi (tetap ada di lonceng).
    'kedaluwarsa_jam' => (int) env('WA_KEDALUWARSA_JAM', 48),

    // Galat selain sambungan putus (misalnya nomor tidak terdaftar di WhatsApp) dicoba sebanyak ini.
    'maks_percobaan' => 3,
];
