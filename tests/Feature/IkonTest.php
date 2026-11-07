<?php

use Illuminate\Support\Facades\File;

/** Ikon tanpa aturan di ikon.css tampil sebagai ruang kosong, tanpa pesan error apa pun. */
it('setiap ikon yang disebut di tampilan punya gambar', function () {
    $css = File::get(resource_path('css/ikon.css'));
    $kosong = [];

    $berkas = array_merge(File::allFiles(resource_path('views')), File::allFiles(app_path('Livewire')));

    foreach ($berkas as $file) {
        $isi = $file->getContents();
        preg_match_all('/\bbi-([a-z0-9][a-z0-9-]*)|\bicon="([a-z0-9-]+)"/', $isi, $hasil, PREG_SET_ORDER);

        foreach ($hasil as $h) {
            $nama = $h[2] ?? $h[1];

            if (! str_contains($css, ".bi-{$nama}::before")) {
                $kosong[$nama] = $file->getRelativePathname();
            }
        }
    }

    expect($kosong)->toBe([]);
});
