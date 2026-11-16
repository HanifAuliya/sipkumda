<?php

return [
    // Sebaiknya di disk lain atau folder bersama: cadangan di disk yang sama ikut hilang bila disknya rusak.
    'folder' => env('BACKUP_DIR') ?: storage_path('app/backup'),

    // Di Windows biasanya perlu alamat lengkap, misalnya C:\mysql\bin\mysqldump.exe.
    'mysqldump' => env('BACKUP_MYSQLDUMP', 'mysqldump'),

    'simpan_hari' => (int) env('BACKUP_SIMPAN_HARI', 14),

    // Tiga cadangan terbaru selalu disimpan, supaya jadwal yang lama terhenti tidak menghapus semuanya.
    'simpan_minimal' => 3,

    'sumber_berkas' => [
        'private' => storage_path('app/private'),
        'public' => storage_path('app/public'),
    ],
];
