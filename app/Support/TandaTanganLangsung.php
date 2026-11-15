<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Tanda tangan yang digambar langsung di layar (kotak <x-ttd.pad>), dikirim browser sebagai data URL PNG.
 * Diperiksa dulu (benar-benar PNG, tidak kosong, tidak terlalu besar), lalu ditulis ke file sementara
 * supaya bisa diolah seperti gambar unggahan (TandaTanganPeneliti::png).
 */
class TandaTanganLangsung
{
    private const AWALAN = 'data:image/png;base64,';

    /** Kanvas tanda tangan berukuran kecil; batas ini jauh di atas ukuran wajarnya. */
    private const MAKS_BYTE = 2 * 1024 * 1024;

    /** Folder tanda tangan per naskah (peneliti di matriks). */
    public const FOLDER_NASKAH = 'tanda_tangan/naskah';

    /** Path file PNG sementara dari data URL; galat validasi memakai nama kolom $kolom. */
    public static function fileSementara(?string $dataUrl, string $kolom): string
    {
        $gagal = fn (string $pesan) => throw ValidationException::withMessages([$kolom => $pesan]);

        if (blank($dataUrl)) {
            $gagal('Tanda tangan masih kosong. Gambar tanda tangan di kotak.');
        }
        if (! str_starts_with($dataUrl, self::AWALAN) || strlen($dataUrl) > self::MAKS_BYTE * 4 / 3 + 100) {
            $gagal('Tanda tangan tidak bisa dibaca. Hapus lalu gambar ulang.');
        }

        $isi = base64_decode(substr($dataUrl, strlen(self::AWALAN)), true);
        $info = $isi === false ? false : @getimagesizefromstring($isi);

        if (! $info || $info[2] !== IMAGETYPE_PNG || $info[0] * $info[1] > FotoProfil::PIKSEL_MAKS) {
            $gagal('Tanda tangan tidak bisa dibaca. Hapus lalu gambar ulang.');
        }

        // Kanvas yang seluruhnya satu warna (belum digambar) tidak bisa dipangkas.
        $gambar = @imagecreatefromstring($isi);
        if (! $gambar || @imagecropauto($gambar, IMG_CROP_SIDES) === false) {
            $gagal('Tanda tangan masih kosong. Gambar tanda tangan di kotak.');
        }

        $path = tempnam(sys_get_temp_dir(), 'ttd');
        file_put_contents($path, $isi);

        return $path;
    }

    /** Simpan PNG bersih untuk satu naskah dari gambar sumber; mengembalikan path di disk local. */
    public static function simpanUntukNaskah(string $sumber): string
    {
        $path = self::FOLDER_NASKAH . '/' . Str::random(40) . '.png';
        Storage::disk('local')->put($path, TandaTanganPeneliti::png($sumber));

        return $path;
    }
}
