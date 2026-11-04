<?php

namespace App\Support;

/** Menandai perbedaan dua teks per kata sebagai HTML yang aman ditampilkan. */
class DiffKata
{
    /** Di atas batas ini perbandingan per kata terlalu berat; seluruh teks dianggap berubah. */
    private const BATAS_KATA = 1500;

    public static function html(?string $lama, ?string $baru): string
    {
        $a = self::pecah((string) $lama);
        $b = self::pecah((string) $baru);

        $awal = 0;
        while ($awal < count($a) && $awal < count($b) && $a[$awal] === $b[$awal]) {
            $awal++;
        }

        $akhir = 0;
        while ($akhir < count($a) - $awal && $akhir < count($b) - $awal && $a[count($a) - 1 - $akhir] === $b[count($b) - 1 - $akhir]) {
            $akhir++;
        }

        $tengahA = array_slice($a, $awal, count($a) - $awal - $akhir);
        $tengahB = array_slice($b, $awal, count($b) - $awal - $akhir);

        $tengah = count($tengahA) > self::BATAS_KATA || count($tengahB) > self::BATAS_KATA
            ? self::tandai($tengahA, 'del') . self::tandai($tengahB, 'ins')
            : self::banding($tengahA, $tengahB);

        return e(implode('', array_slice($a, 0, $awal)))
            . $tengah
            . ($akhir ? e(implode('', array_slice($a, count($a) - $akhir))) : '');
    }

    /** @return array<int, string> kata dan spasi sebagai potongan terpisah */
    private static function pecah(string $teks): array
    {
        return preg_split('/(\s+)/u', $teks, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
    }

    private static function banding(array $a, array $b): string
    {
        $n = count($a);
        $m = count($b);
        $tabel = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));

        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $tabel[$i][$j] = $a[$i] === $b[$j]
                    ? $tabel[$i + 1][$j + 1] + 1
                    : max($tabel[$i + 1][$j], $tabel[$i][$j + 1]);
            }
        }

        $hasil = '';
        $hapus = $tambah = [];
        $i = $j = 0;

        $tutup = function () use (&$hasil, &$hapus, &$tambah) {
            $hasil .= self::tandai($hapus, 'del') . self::tandai($tambah, 'ins');
            $hapus = $tambah = [];
        };

        while ($i < $n || $j < $m) {
            if ($i < $n && $j < $m && $a[$i] === $b[$j]) {
                $tutup();
                $hasil .= e($a[$i]);
                $i++;
                $j++;
            } elseif ($j < $m && ($i === $n || $tabel[$i][$j + 1] >= $tabel[$i + 1][$j])) {
                $tambah[] = $b[$j++];
            } else {
                $hapus[] = $a[$i++];
            }
        }
        $tutup();

        return $hasil;
    }

    private static function tandai(array $potongan, string $tag): string
    {
        return $potongan ? "<{$tag}>" . e(implode('', $potongan)) . "</{$tag}>" : '';
    }
}
