<?php

namespace App\Support;

use App\Models\User;
use GdImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Foto selalu digambar ulang menjadi JPEG persegi. File asli tidak pernah disimpan, sehingga
 * lokasi GPS di data EXIF foto HP ikut terbuang dan kode yang diselipkan di file gambar tidak terbawa.
 */
class FotoProfil
{
    private const UKURAN = 512;

    /** Membuka gambar memakai sekitar 5 byte memori per piksel; 25 MP masih aman di server biasa. */
    public const PIKSEL_MAKS = 25_000_000;

    /** Pesan penolakan, atau null bila gambar bisa diproses. */
    public static function masalah(string $sumber): ?string
    {
        $info = @getimagesize($sumber);

        return match (true) {
            ! $info => 'Foto tidak bisa dibaca. Coba pilih foto lain.',
            $info[0] * $info[1] > self::PIKSEL_MAKS => 'Ukuran gambar terlalu besar. Gunakan foto di bawah 25 megapiksel.',
            default => null,
        };
    }

    public static function simpan(User $user, string $sumber): void
    {
        $gambar = self::tegakkan(imagecreatefromstring(file_get_contents($sumber)), $sumber);

        $sisi = min(imagesx($gambar), imagesy($gambar));
        $hasil = imagecreatetruecolor(self::UKURAN, self::UKURAN);

        // PNG transparan menjadi berlatar putih, karena JPEG tidak mengenal transparansi.
        imagefill($hasil, 0, 0, imagecolorallocate($hasil, 255, 255, 255));
        imagecopyresampled(
            $hasil, $gambar, 0, 0,
            (int) ((imagesx($gambar) - $sisi) / 2), (int) ((imagesy($gambar) - $sisi) / 2),
            self::UKURAN, self::UKURAN, $sisi, $sisi,
        );

        ob_start();
        imagejpeg($hasil, null, 85);
        $isi = ob_get_clean();

        $path = 'foto_profil/' . Str::random(40) . '.jpg';
        Storage::disk('local')->put($path, $isi);

        self::buang($user->foto_profil);
        $user->forceFill(['foto_profil' => $path])->save();
    }

    public static function hapus(User $user): void
    {
        self::buang($user->foto_profil);
        $user->forceFill(['foto_profil' => null])->save();
    }

    public static function buang(?string $path): void
    {
        if ($path) {
            Storage::disk('local')->delete($path);
        }
    }

    /** Foto HP sering tersimpan miring dan hanya diberi penanda arah di data EXIF. */
    private static function tegakkan(GdImage $gambar, string $sumber): GdImage
    {
        $arah = function_exists('exif_read_data') && getimagesize($sumber)[2] === IMAGETYPE_JPEG
            ? (@exif_read_data($sumber)['Orientation'] ?? 1)
            : 1;

        return match ($arah) {
            3 => imagerotate($gambar, 180, 0),
            6 => imagerotate($gambar, -90, 0),
            8 => imagerotate($gambar, 90, 0),
            default => $gambar,
        };
    }
}
