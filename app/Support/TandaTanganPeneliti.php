<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Gambar tanda tangan peneliti untuk matriks. Seperti foto profil, file asli tidak disimpan:
 * gambar digambar ulang menjadi PNG, sehingga data tersembunyi di dalam file ikut terbuang.
 * Latar transparan tetap transparan; tepi kosong dipangkas supaya tanda tangannya tidak mengecil di matriks.
 */
class TandaTanganPeneliti
{
    private const LEBAR_MAKS = 800;
    private const TINGGI_MAKS = 400;

    public const FOLDER = 'tanda_tangan/peneliti';

    /** Pesan penolakan, atau null bila gambar bisa diproses. */
    public static function masalah(string $sumber): ?string
    {
        $info = @getimagesize($sumber);

        return match (true) {
            ! $info => 'Gambar tidak bisa dibaca. Coba pilih gambar lain.',
            $info[0] * $info[1] > FotoProfil::PIKSEL_MAKS => 'Ukuran gambar terlalu besar. Gunakan gambar di bawah 25 megapiksel.',
            default => null,
        };
    }

    public static function simpan(User $user, string $sumber): void
    {
        $gambar = imagecreatefromstring(file_get_contents($sumber));

        // imagecropauto memangkas tepi yang warnanya sama dengan sudut gambar, transparan maupun putih.
        $pangkas = @imagecropauto($gambar, IMG_CROP_SIDES);
        if ($pangkas !== false) {
            $gambar = $pangkas;
        }

        $skala = min(1, self::LEBAR_MAKS / imagesx($gambar), self::TINGGI_MAKS / imagesy($gambar));
        $lebar = max(1, (int) round(imagesx($gambar) * $skala));
        $tinggi = max(1, (int) round(imagesy($gambar) * $skala));

        $hasil = imagecreatetruecolor($lebar, $tinggi);
        imagealphablending($hasil, false);
        imagesavealpha($hasil, true);
        imagefill($hasil, 0, 0, imagecolorallocatealpha($hasil, 255, 255, 255, 127));
        imagecopyresampled($hasil, $gambar, 0, 0, 0, 0, $lebar, $tinggi, imagesx($gambar), imagesy($gambar));

        ob_start();
        imagepng($hasil, null, 9);
        $isi = ob_get_clean();

        $path = self::FOLDER . '/' . Str::random(40) . '.png';
        Storage::disk('local')->put($path, $isi);

        self::buang($user->file_ttd);
        $user->forceFill(['file_ttd' => $path])->save();
    }

    public static function hapus(User $user): void
    {
        self::buang($user->file_ttd);
        $user->forceFill(['file_ttd' => null])->save();
    }

    public static function buang(?string $path): void
    {
        if ($path) {
            Storage::disk('local')->delete($path);
        }
    }

    /** Alamat file di disk untuk Dompdf, atau null bila belum diunggah atau filenya hilang. */
    public static function path(?User $user): ?string
    {
        $file = $user?->file_ttd;

        return $file && Storage::disk('local')->exists($file) ? Storage::disk('local')->path($file) : null;
    }
}
