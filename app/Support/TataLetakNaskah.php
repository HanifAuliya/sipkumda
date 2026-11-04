<?php

namespace App\Support;

use App\Models\Naskah;
use App\Models\NaskahBaris;
use Illuminate\Support\Collection;

/**
 * Menyusun baris matriks menjadi blok naskah. Pratinjau, Word, dan PDF memakai susunan
 * yang sama, jadi isinya selalu serupa dan hanya cara menggambarnya yang berbeda.
 *
 * Bentuk blok:
 * - tengah  : baris rata tengah
 * - daftar  : Menimbang/Mengingat/Menetapkan dengan label dan butir bernomor
 * - teks    : isi pasal, tiap baris punya tingkat dan nomor
 * - diktum  : KESATU, KEDUA pada Surat Keputusan
 * - penutup : blok Ditetapkan, Diundangkan, dan Berita Daerah
 */
class TataLetakNaskah
{
    public const KOP = 'BUPATI HULU SUNGAI TENGAH';

    /** @param Collection<int, NaskahBaris> $baris urut menurut kolom urutan */
    public static function susun(Naskah $naskah, Collection $baris): array
    {
        $sk = $naskah->jenis === 'Surat Keputusan';
        $penanda = TemplateNaskah::penanda($naskah, $baris);
        $blok = [];
        $sebelumnya = null;

        foreach ($baris as $item) {
            $teks = $item->teksAkhir();

            // Lampiran berupa file terpisah; yang dihapus atau masih kosong tidak dicetak.
            if ($item->bagian === 'lampiran' || $item->status === NaskahBaris::DIHAPUS || (blank($teks) && blank($item->label))) {
                continue;
            }

            $baru = $item->bagian !== $sebelumnya;
            $sebelumnya = $item->bagian;
            $jumlahAwal = count($blok);

            match ($item->bagian) {
                'judul' => self::judul($blok, $teks, $sk),
                'menimbang' => self::butir($blok, $baru, 'Menimbang', $penanda[$item->id], $teks),
                'mengingat' => self::butir($blok, $baru, 'Mengingat', $penanda[$item->id], $teks),
                'diktum' => $sk
                    ? self::diktumKeputusan($blok, $baru, $penanda[$item->id], $teks)
                    : self::diktumPeraturan($blok, $teks),
                'batang_tubuh' => self::batangTubuh($blok, $item->label, $teks),
                'penutup' => $blok[] = self::penutup($teks),
                default => $blok[] = ['tipe' => 'teks', 'baris' => self::barisTeks($teks)],
            };

            self::tandaiAsal($blok, $jumlahAwal, $item->id);
        }

        return $blok;
    }

    /** Mencatat baris asal tiap blok, supaya pratinjau bisa menyorot baris yang sedang dikerjakan. */
    public static function tandaiAsal(array &$blok, int $jumlahAwal, int $id): void
    {
        foreach (array_slice(array_keys($blok), $jumlahAwal) as $i) {
            $blok[$i]['id'] = $id;
        }

        $terakhir = array_key_last($blok);

        // Menimbang, Mengingat, dan diktum SK menampung banyak baris dalam satu blok.
        if ($terakhir !== null && isset($blok[$terakhir]['butir']) && in_array($blok[$terakhir]['tipe'], ['daftar', 'diktum'], true)) {
            $blok[$terakhir]['ids'][array_key_last($blok[$terakhir]['butir'])] = $id;
        }
    }

    private static function judul(array &$blok, ?string $teks, bool $sk): void
    {
        $blok[] = ['tipe' => 'tengah', 'baris' => self::barisIsi($teks)];

        if (! $sk) {
            $blok[] = ['tipe' => 'tengah', 'baris' => ['DENGAN RAHMAT TUHAN YANG MAHA ESA']];
        }

        $blok[] = ['tipe' => 'tengah', 'baris' => [self::KOP . ',']];
    }

    private static function butir(array &$blok, bool $baru, string $label, ?string $nomor, ?string $teks): void
    {
        if ($baru) {
            $blok[] = ['tipe' => 'daftar', 'label' => $label, 'butir' => []];
        }

        $blok[array_key_last($blok)]['butir'][] = ['nomor' => $nomor, 'isi' => (string) $teks];
    }

    private static function diktumKeputusan(array &$blok, bool $baru, ?string $nomor, ?string $teks): void
    {
        if ($baru) {
            $blok[] = ['tipe' => 'tengah', 'baris' => ['MEMUTUSKAN :']];
            $blok[] = ['tipe' => 'teks', 'baris' => [['tingkat' => 0, 'nomor' => null, 'isi' => 'Menetapkan :']]];
            $blok[] = ['tipe' => 'diktum', 'butir' => []];
        }

        $blok[array_key_last($blok)]['butir'][] = ['nomor' => $nomor, 'isi' => (string) $teks];
    }

    /** "MEMUTUSKAN:" rata tengah, "Menetapkan: ..." menjadi baris berlabel. */
    private static function diktumPeraturan(array &$blok, ?string $teks): void
    {
        foreach (self::barisIsi($teks) as $baris) {
            if (preg_match('/^menetapkan\s*:\s*(.*)$/isu', $baris, $m)) {
                $blok[] = ['tipe' => 'daftar', 'label' => 'Menetapkan', 'butir' => [['nomor' => null, 'isi' => trim($m[1])]]];
            } else {
                $blok[] = ['tipe' => 'tengah', 'baris' => [$baris]];
            }
        }
    }

    private static function batangTubuh(array &$blok, ?string $label, ?string $teks): void
    {
        if (filled($label) && ! str_starts_with($label, 'Pasal')) {
            // BAB, Bagian, dan Paragraf: label dan judulnya sama-sama rata tengah.
            $blok[] = ['tipe' => 'tengah', 'baris' => array_merge([$label], self::barisIsi($teks))];

            return;
        }

        if (filled($label)) {
            $blok[] = ['tipe' => 'tengah', 'baris' => [$label]];
        }

        $blok[] = ['tipe' => 'teks', 'baris' => self::barisTeks($teks)];
    }

    public static function penutup(?string $teks): array
    {
        $ditetapkan = $diundangkan = [];
        $berita = null;
        $sedangDiundangkan = false;

        foreach (preg_split('/\R/u', (string) $teks) as $baris) {
            $baris = trim($baris);

            if (preg_match('/^BERITA DAERAH\b|^\(?LEMBARAN DAERAH\b/iu', $baris)) {
                $berita = $baris;
                continue;
            }

            if (preg_match('/^diundangkan\s+di\b/iu', $baris)) {
                $sedangDiundangkan = true;
            }

            // Baris kosong di antara jabatan dan nama adalah ruang tanda tangan basah.
            if ($sedangDiundangkan) {
                $diundangkan[] = $baris;
            } else {
                $ditetapkan[] = $baris;
            }
        }

        return [
            'tipe' => 'penutup',
            'ditetapkan' => self::rapikanTepi($ditetapkan),
            'diundangkan' => self::rapikanTepi($diundangkan),
            'berita' => $berita,
        ];
    }

    private static function rapikanTepi(array $baris): array
    {
        while ($baris && $baris[array_key_last($baris)] === '') {
            array_pop($baris);
        }

        return $baris;
    }

    /** @return array<int, string> baris berisi saja */
    public static function barisIsi(?string $teks): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $teks)), 'strlen'));
    }

    /**
     * Mengenali ayat dan butir pada isi pasal: (1) di tingkat pertama, a. di bawah ayat,
     * 1. di bawah huruf. Tanpa ayat, a. dan 1. naik satu tingkat.
     *
     * @return array<int, array{tingkat: int, nomor: ?string, isi: string}>
     */
    public static function barisTeks(?string $teks): array
    {
        $adaAyat = $adaHuruf = false;
        $hasil = [];

        foreach (self::barisIsi($teks) as $baris) {
            // Tabel ditulis sebagai baris "| a | b |"; baris yang berurutan menjadi satu tabel.
            if (str_starts_with($baris, '|')) {
                $terakhir = array_key_last($hasil);
                $sel = self::selTabel($baris);

                if ($sel === null) {
                    continue;
                }
                if ($terakhir !== null && isset($hasil[$terakhir]['tabel'])) {
                    $hasil[$terakhir]['tabel'][] = $sel;
                } else {
                    $hasil[] = ['tingkat' => 0, 'nomor' => null, 'isi' => '', 'tabel' => [$sel]];
                }

                continue;
            }

            $nomor = null;
            $tingkat = 0;

            if (preg_match('/^(\(\d+[a-z]?\))\s*(.*)$/su', $baris, $m)) {
                [$nomor, $baris] = [$m[1], $m[2]];
                $adaAyat = true;
                $adaHuruf = false;
            } elseif (preg_match('/^([a-z][.)])\s+(.*)$/su', $baris, $m)) {
                [$nomor, $baris] = [$m[1], $m[2]];
                $tingkat = $adaAyat ? 1 : 0;
                $adaHuruf = true;
            } elseif (preg_match('/^(\d{1,3}[.)])\s+(.*)$/su', $baris, $m)) {
                [$nomor, $baris] = [$m[1], $m[2]];
                $tingkat = ($adaAyat ? 1 : 0) + ($adaHuruf ? 1 : 0);
            }

            $hasil[] = ['tingkat' => $tingkat, 'nomor' => $nomor, 'isi' => $baris];
        }

        return $hasil;
    }

    /**
     * Memisahkan teks biasa dan tabel untuk tampilan layar.
     *
     * @return list<array{teks?: string, tabel?: list<list<string>>}>
     */
    public static function potongan(?string $teks): array
    {
        $hasil = [];

        foreach (preg_split('/\R/u', (string) $teks) as $baris) {
            $terakhir = array_key_last($hasil);

            if (str_starts_with(ltrim($baris), '|')) {
                $sel = self::selTabel($baris);

                if ($sel === null) {
                    continue;
                }
                if ($terakhir !== null && isset($hasil[$terakhir]['tabel'])) {
                    $hasil[$terakhir]['tabel'][] = $sel;
                } else {
                    $hasil[] = ['tabel' => [$sel]];
                }
            } elseif ($terakhir !== null && isset($hasil[$terakhir]['teks'])) {
                $hasil[$terakhir]['teks'] .= "\n{$baris}";
            } else {
                $hasil[] = ['teks' => $baris];
            }
        }

        return $hasil;
    }

    /** @return array<int, string>|null isi sel, atau null untuk baris pemisah "| --- |" */
    public static function selTabel(string $baris): ?array
    {
        $isi = preg_replace('/^\||\|$/', '', trim($baris));
        $sel = array_map('trim', explode('|', $isi));

        return array_filter($sel, fn ($s) => ! preg_match('/^-*$/', $s)) ? $sel : null;
    }

    /**
     * Mengurutkan ulang nomor ayat dan butir setelah ada yang disisipkan atau dihapus.
     * Rujukan di dalam kalimat, misalnya "ayat (1)", tidak diubah dan tetap perlu diperiksa.
     */
    public static function rapikanNomor(?string $teks): string
    {
        $ayat = $huruf = $angka = 0;
        $hasil = [];

        foreach (preg_split('/\R/u', (string) $teks) as $baris) {
            if (preg_match('/^(\s*)\(\d+[a-z]?\)\s*(.*)$/su', $baris, $m)) {
                $baris = $m[1] . '(' . ++$ayat . ') ' . $m[2];
                $huruf = $angka = 0;
            } elseif (preg_match('/^(\s*)[a-z]([.)])\s+(.*)$/su', $baris, $m)) {
                $baris = $m[1] . chr(97 + $huruf++ % 26) . $m[2] . ' ' . $m[3];
                $angka = 0;
            } elseif (preg_match('/^(\s*)\d{1,3}([.)])\s+(.*)$/su', $baris, $m)) {
                $baris = $m[1] . ++$angka . $m[2] . ' ' . $m[3];
            } elseif (trim($baris) !== '' && ! str_starts_with(ltrim($baris), '|')) {
                // Kalimat biasa mengakhiri daftar yang sedang berjalan.
                $huruf = $angka = 0;
            }

            $hasil[] = $baris;
        }

        return implode("\n", $hasil);
    }
}
