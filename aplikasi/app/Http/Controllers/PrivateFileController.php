<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menampilkan file di storage/app/private lewat /view-private/{folder}/{subfolder}/{filename}.
 *
 * Hanya folder yang dikenal yang bisa dibuka: produk hukum (juga terbuka di JDIH) dan gambar
 * tanda tangan (khusus pengelola nota dinas). Berkas naskah memakai rute naskah.berkas.
 */
class PrivateFileController extends Controller
{
    public function __invoke(string $folder, string $subfolder, string $filename): StreamedResponse
    {
        $path = "{$folder}/{$subfolder}/{$filename}";

        abort_unless($this->bolehMelihat(auth()->user(), $folder, $subfolder), 403);
        abort_unless(Storage::disk('local')->exists($path), 404, 'File tidak ditemukan.');

        // Dikirim bertahap (stream), tidak dimuat utuh ke memori.
        return Storage::disk('local')->response($path, $filename, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=600',
        ]);
    }

    private function bolehMelihat(User $user, string $folder, string $subfolder): bool
    {
        return match ("{$folder}/{$subfolder}") {
            // Produk hukum yang sudah ditetapkan juga tersedia untuk umum (JDIH).
            'dokumentasi/file_produk_hukum' => true,

            // Gambar tanda tangan hanya untuk pengelola nota dinas.
            'tanda_tangan/ttd' => $user->hasRole(['Super Admin', 'Admin', 'Verifikator']),

            default => false,
        };
    }
}
