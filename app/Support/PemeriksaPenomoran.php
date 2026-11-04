<?php

namespace App\Support;

use App\Models\NaskahBaris;
use Illuminate\Support\Collection;

/**
 * Peringatan penomoran BAB dan Pasal serta rujukan antarpasal.
 * Hanya mengingatkan, tidak memblokir: keputusan akhir tetap di tangan pengguna.
 */
class PemeriksaPenomoran
{
    /**
     * @param  Collection<int, NaskahBaris>  $baris  urut menurut kolom urutan
     * @return array<int, array{baris_id: int, pesan: string}>
     */
    public static function periksa(Collection $baris): array
    {
        $hidup = $baris->where('bagian', 'batang_tubuh')->where('status', '!=', NaskahBaris::DIHAPUS);
        $pesan = [];

        self::urutan($hidup, '/^Pasal\s+(\d+)$/u', 'Pasal', fn ($n) => (int) $n, $pesan);
        self::urutan($hidup, '/^BAB\s+([IVXLC]+)$/u', 'BAB', fn ($n) => self::romawi($n), $pesan);
        self::rujukan($hidup, $pesan);

        return $pesan;
    }

    private static function urutan(Collection $baris, string $pola, string $nama, callable $nilai, array &$pesan): void
    {
        $sebelumnya = null;
        $teks = null;

        foreach ($baris as $item) {
            if (! preg_match($pola, (string) $item->label, $m)) {
                continue;
            }

            $n = $nilai($m[1]);

            if ($sebelumnya !== null && $n === $sebelumnya) {
                $pesan[] = ['baris_id' => $item->id, 'pesan' => "{$nama} {$m[1]} tertulis dua kali."];
            } elseif ($sebelumnya !== null && $n < $sebelumnya) {
                $pesan[] = ['baris_id' => $item->id, 'pesan' => "{$nama} {$m[1]} muncul setelah {$nama} {$teks}."];
            } elseif ($sebelumnya !== null && $n > $sebelumnya + 1) {
                $pesan[] = ['baris_id' => $item->id, 'pesan' => "Nomor melompat dari {$nama} {$teks} ke {$nama} {$m[1]}."];
            }

            $sebelumnya = $n;
            $teks = $m[1];
        }
    }

    /** Rujukan ke peraturan lain, misalnya "Pasal 18 ayat (6) Undang-Undang ...", dilewati. */
    private static function rujukan(Collection $baris, array &$pesan): void
    {
        $ada = [];
        foreach ($baris as $item) {
            if (preg_match('/^Pasal\s+(\d+[A-Z]?)$/u', (string) $item->label, $m)) {
                $ada[$m[1]] = true;
            }
        }

        if (! $ada) {
            return;
        }

        $pola = '/\bPasal\s+(\d+[A-Z]?)\b(?:\s+ayat\s+\(\d+\))?+(?:\s+huruf\s+[a-z]\b)?+(?!\s+(?:Undang|Peraturan|Keputusan|Permendagri|PP\b|UU\b|Perda\b|Perbup\b))/u';

        foreach ($baris as $item) {
            preg_match_all($pola, (string) $item->teksAkhir(), $temuan);

            foreach (array_unique($temuan[1]) as $nomor) {
                if (! isset($ada[$nomor])) {
                    $pesan[] = [
                        'baris_id' => $item->id,
                        'pesan' => ($item->label ?: 'Baris ini') . " merujuk ke Pasal {$nomor}, yang tidak ada di naskah ini.",
                    ];
                }
            }
        }
    }

    private static function romawi(string $romawi): int
    {
        $nilai = ['I' => 1, 'V' => 5, 'X' => 10, 'L' => 50, 'C' => 100];
        $total = 0;
        $huruf = str_split($romawi);

        foreach ($huruf as $i => $h) {
            $total += isset($huruf[$i + 1]) && $nilai[$h] < $nilai[$huruf[$i + 1]] ? -$nilai[$h] : $nilai[$h];
        }

        return $total;
    }
}
