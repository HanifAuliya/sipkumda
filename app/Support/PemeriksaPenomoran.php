<?php

namespace App\Support;

use App\Models\NaskahBaris;
use Illuminate\Support\Collection;

/**
 * Penomoran BAB dan Pasal serta rujukan antarpasal di batang tubuh.
 *
 * Setiap temuan berjenis:
 * - galat: pasti salah dan menghalangi pengajuan (nomor melompat, ganda, terbalik; rujukan ke pasal yang
 *   tidak ada di naskah ini);
 * - peringatan: kemungkinan salah, keputusan di tangan pengguna (rujukan ke ayat yang tidak ada).
 *
 * Rujukan ke pasal peraturan lain ("Pasal 18 ayat (6) Undang-Undang ...") tidak diperiksa;
 * "Pasal 5 Peraturan Bupati ini" tetap dianggap rujukan ke naskah ini.
 */
class PemeriksaPenomoran
{
    public const GALAT = 'galat';

    public const PERINGATAN = 'peringatan';

    /** Kata penghubung antara rujukan pasal dan nama peraturannya: "Pasal 5 ayat (1) huruf a dan Pasal 6 ...". */
    private const SAMBUNG = '(?:\s*(?:,|dan\b|atau\b|serta\b|sampai\s+dengan\b|s\.d\.?|ayat\s+\(\d+[a-z]?\)|huruf\s+[a-z]\b|angka\s+\d+\b|Pasal\s+\d+[A-Z]?\b))*';

    /** Nama peraturan lain sesudah rujukan pasal. */
    private const PERATURAN_LAIN = '\s*(?:(?:dari|pada|dalam)\s+)?(?:Undang|UU\b|Peraturan|Keputusan|Permendagri|Permen|Perpres|Perda|Perbup|Pergub|PP\b|Instruksi|Surat\s+Edaran|Kitab|KUH)';

    /** "Peraturan Bupati ini", "Peraturan ini", "Keputusan Bupati ini": rujukan ke naskah ini sendiri. */
    private const NASKAH_INI = '\s*(?:(?:dari|pada|dalam)\s+)?(?:Peraturan|Keputusan)(?:\s+Bupati)?\s+ini\b';

    /**
     * @param  Collection<int, NaskahBaris>  $baris  urut menurut kolom urutan
     * @return array<int, array{baris_id: int, pesan: string, jenis: string}>
     */
    public static function periksa(Collection $baris): array
    {
        $hidup = $baris->where('bagian', 'batang_tubuh')->whereNotIn('status', NaskahBaris::TERHAPUS);
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
            $temuan = match (true) {
                $sebelumnya === null => null,
                $n === $sebelumnya => "{$nama} {$m[1]} tertulis dua kali.",
                $n < $sebelumnya => "{$nama} {$m[1]} muncul setelah {$nama} {$teks}.",
                $n > $sebelumnya + 1 => "Nomor melompat dari {$nama} {$teks} ke {$nama} {$m[1]}.",
                default => null,
            };

            if ($temuan) {
                $pesan[] = ['baris_id' => $item->id, 'pesan' => $temuan, 'jenis' => self::GALAT];
            }

            $sebelumnya = $n;
            $teks = $m[1];
        }
    }

    private static function rujukan(Collection $baris, array &$pesan): void
    {
        // Nomor pasal yang ada di naskah ini, beserta nomor ayatnya: baris yang diawali "(1)", "(2)", ...
        $ayat = [];
        foreach ($baris as $item) {
            if (preg_match('/^Pasal\s+(\d+[A-Z]?)$/u', (string) $item->label, $m)) {
                preg_match_all('/^\s*\((\d+)\)/mu', (string) $item->teksAkhir(), $nomorAyat);
                $ayat[$m[1]] = array_map('intval', $nomorAyat[1]);
            }
        }

        if (! $ayat) {
            return;
        }

        foreach ($baris as $item) {
            $teks = (string) $item->teksAkhir();
            $nama = $item->label ?: 'Baris ini';
            $sudah = [];

            preg_match_all('/\bPasal\s+(\d+[A-Z]?)\b(?:\s+ayat\s+\((\d+)\))?/u', $teks, $temuan, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

            foreach ($temuan as $t) {
                $sisa = substr($teks, $t[0][1] + strlen($t[0][0]), 300);

                if (! preg_match('/^' . self::SAMBUNG . self::NASKAH_INI . '/u', $sisa)
                    && preg_match('/^' . self::SAMBUNG . self::PERATURAN_LAIN . '/u', $sisa)) {
                    continue;
                }
                if (self::bukanRujukanNaskahIni($teks, $t[0][1], $sisa)) {
                    continue;
                }

                $nomor = $t[1][0];
                $nomorAyat = isset($t[2]) && $t[2][1] >= 0 ? (int) $t[2][0] : null;
                $kunci = "{$nomor}/{$nomorAyat}";

                if (isset($sudah[$kunci])) {
                    continue;
                }
                $sudah[$kunci] = true;

                if (! isset($ayat[$nomor])) {
                    $pesan[] = ['baris_id' => $item->id, 'pesan' => "{$nama} merujuk ke Pasal {$nomor}, yang tidak ada di naskah ini.", 'jenis' => self::GALAT];
                } elseif ($nomorAyat !== null && $ayat[$nomor] && ! in_array($nomorAyat, $ayat[$nomor], true)) {
                    // Hanya bila pasal tujuannya memang berayat: tanpa "(1)" di awal baris, ayatnya tidak bisa dikenali.
                    $pesan[] = ['baris_id' => $item->id, 'pesan' => "{$nama} merujuk ke Pasal {$nomor} ayat ({$nomorAyat}), padahal Pasal {$nomor} tidak memuat ayat itu.", 'jenis' => self::PERINGATAN];
                }
            }

            // "sebagaimana dimaksud pada ayat (3)" di dalam pasal yang sama.
            if (preg_match('/^Pasal\s+(\d+[A-Z]?)$/u', (string) $item->label, $m) && $ayat[$m[1]]) {
                // Rujukan berantai ke pasal lain ("Pasal 5 ayat (1) dan ayat (2)") dibuang dulu, sisanya ayat pasal ini.
                $tanpaPasal = preg_replace('/\bPasal\s+\d+[A-Z]?' . self::SAMBUNG . '/u', '', $teks);
                preg_match_all('/\bayat\s+\((\d+)\)/u', $tanpaPasal, $ke);

                foreach (array_unique(array_map('intval', $ke[1])) as $n) {
                    if (! in_array($n, $ayat[$m[1]], true)) {
                        $pesan[] = ['baris_id' => $item->id, 'pesan' => "{$nama} merujuk ke ayat ({$n}), padahal {$nama} tidak memuat ayat itu.", 'jenis' => self::PERINGATAN];
                    }
                }
            }
        }
    }

    /**
     * Penyebutan "Pasal" yang bukan rujukan ke pasal naskah ini:
     * - istilah pajak "Pajak Penghasilan Pasal 21" / "PPh Pasal 21";
     * - kalimat peraturan perubahan ("Di antara Pasal 19 dan Pasal 20 disisipkan ...", "Ketentuan Pasal 5 diubah
     *   sehingga berbunyi ..."), yang merujuk ke pasal peraturan yang diubah.
     */
    private static function bukanRujukanNaskahIni(string $teks, int $posisi, string $sisa): bool
    {
        $sebelum = substr($teks, max(0, $posisi - 200), min($posisi, 200));

        if (preg_match('/(?:Pajak\s+Penghasilan|PPh)\s*$/iu', $sebelum)) {
            return true;
        }

        // Kalimat tempat rujukan berada: dari tanda baca terakhir sebelumnya sampai tanda baca berikutnya.
        $awalKalimat = preg_split('/[.;:]\s|\R/u', $sebelum);
        $akhirKalimat = preg_split('/[.;:](?:\s|$)|\R/u', $sisa, 2);
        $kalimat = end($awalKalimat) . ' ' . $akhirKalimat[0];

        return (bool) preg_match('/\b(?:disisipkan|diubah|dihapus|berbunyi\s+sebagai\s+berikut)\b/iu', $kalimat);
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
