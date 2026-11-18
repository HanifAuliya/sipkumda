<?php

namespace App\Support;

use App\Models\NaskahBaris;
use Illuminate\Support\Collection;

/**
 * Penomoran BAB dan Pasal serta rujukan antarpasal di batang tubuh.
 *
 * Semua temuan berupa peringatan dan tidak menghalangi pengajuan: bentuk naskah (baru atau perubahan) dan
 * rujukan ke peraturan lain dikenali dari isinya, jadi bisa keliru. Keputusan akhir di tangan pengguna.
 *
 * Rujukan ke pasal peraturan lain ("Pasal 18 ayat (6) Undang-Undang ...") tidak diperiksa;
 * "Pasal 5 Peraturan Bupati ini" tetap dianggap rujukan ke naskah ini.
 */
class PemeriksaPenomoran
{
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

        if (self::perubahan($baris)) {
            self::periksaPerubahan($hidup, $pesan);

            return $pesan;
        }

        self::urutan($hidup, '/^Pasal\s+(\d+)$/u', 'Pasal', fn ($n) => (int) $n, $pesan);
        self::urutan($hidup, '/^BAB\s+([IVXLC]+)$/u', 'BAB', fn ($n) => self::romawi($n), $pesan);
        self::rujukan($hidup, $pesan);

        return $pesan;
    }

    /**
     * Peraturan perubahan (Lampiran II UU 12/2011): batang tubuhnya Pasal I (butir-butir perubahan) dan Pasal II
     * (mulai berlaku), ditulis dengan angka Romawi. Dikenali dari pasal bernomor Romawi, atau judul
     * "PERUBAHAN ... ATAS" selama Pasal I belum ditulis.
     */
    public static function perubahan(Collection $baris): bool
    {
        $hidup = $baris->whereNotIn('status', NaskahBaris::TERHAPUS);

        if ($hidup->contains(fn (NaskahBaris $b) => $b->bagian === 'batang_tubuh' && preg_match('/^Pasal\s+[IVXLC]+$/u', (string) $b->label))) {
            return true;
        }

        $judul = (string) $hidup->firstWhere('bagian', 'judul')?->teksAkhir();

        return (bool) preg_match('/\bPERUBAHAN\b(?:\s+\w+)?\s+ATAS\b/iu', $judul);
    }

    /**
     * Pada peraturan perubahan, nomor pasal Arab adalah nomor pasal peraturan yang diubah, jadi wajar melompat
     * (Pasal 1, 9, 11, 19A, ...) dan rujukannya ke pasal peraturan itu. Yang diperiksa:
     * - Pasal Romawi (I, II, ...) berurutan;
     * - pasal yang diubah urut naik dan tidak ganda: peringatan;
     * - nomor butir perubahan (1., 2., ...) berurutan, dan pasal yang disebut butir sama dengan kepala pasal
     *   sesudahnya ("Ketentuan Pasal 9 diubah ..." diikuti Pasal 9): peringatan.
     */
    private static function periksaPerubahan(Collection $baris, array &$pesan): void
    {
        self::urutan($baris, '/^Pasal\s+([IVXLC]+)$/u', 'Pasal', fn ($n) => self::romawi($n), $pesan);

        $sebelumnya = null;
        foreach ($baris as $item) {
            if (! preg_match('/^Pasal\s+(\d+)([A-Z]?)$/u', (string) $item->label, $m)) {
                continue;
            }
            // 19A sesudah 19 dan sebelum 20.
            $nilai = (int) $m[1] + ($m[2] !== '' ? (ord($m[2]) - 64) / 100 : 0);

            if ($sebelumnya !== null && $nilai <= $sebelumnya[0]) {
                $pesan[] = ['baris_id' => $item->id, 'pesan' => $nilai === $sebelumnya[0]
                    ? "{$item->label} diubah dua kali di Pasal I. Satukan perubahannya."
                    : "{$item->label} ditulis sesudah {$sebelumnya[1]}. Butir perubahan biasanya urut menurut nomor pasal.", 'jenis' => self::PERINGATAN];
            }
            $sebelumnya = [$nilai, $item->label];
        }

        // Butir perubahan: "2. Ketentuan ayat (3) Pasal 9 diubah ...", "6. Di antara Pasal 19 dan Pasal 20 disisipkan ... Pasal 19A".
        $nomorButir = 0;
        $menunggu = null;
        foreach ($baris as $item) {
            if ($menunggu && preg_match('/^Pasal\s+\d+[A-Z]?$/u', (string) $item->label) && $item->label !== $menunggu[0]) {
                $pesan[] = ['baris_id' => $item->id, 'pesan' => "Butir perubahan angka {$menunggu[1]} menyebut {$menunggu[0]}, tetapi pasal sesudahnya {$item->label}.", 'jenis' => self::PERINGATAN];
            }
            if (preg_match('/^Pasal\s+\d+[A-Z]?$/u', (string) $item->label)) {
                $menunggu = null;
            }

            preg_match_all('/^\s*(\d+)\.\s+(.*(?:diubah|disisipkan|dihapus|ditambah).*)$/mu', (string) $item->teksAkhir(), $butir, PREG_SET_ORDER);

            foreach ($butir as [, $nomor, $kalimat]) {
                if (! preg_match('/^(?:Ketentuan|Di\s*antara|Pasal|BAB|Bagian|Paragraf|Lampiran|Penjelasan|Judul|Konsiderans)\b/iu', $kalimat)) {
                    continue;
                }
                if ((int) $nomor !== $nomorButir + 1) {
                    $pesan[] = ['baris_id' => $item->id, 'pesan' => "Nomor butir perubahan melompat dari angka {$nomorButir} ke angka {$nomor}.", 'jenis' => self::PERINGATAN];
                }
                $nomorButir = (int) $nomor;

                // Pasal yang akan ditulis sesudah butir ini: yang disisipkan ("yakni Pasal 19A") atau yang diubah.
                $tujuan = preg_match('/yakni\s+Pasal\s+(\d+[A-Z]?)/iu', $kalimat, $t) || preg_match('/^Ketentuan\b.*?\bPasal\s+(\d+[A-Z]?)\s+diubah/iu', $kalimat, $t)
                    ? 'Pasal ' . $t[1]
                    : null;
                $menunggu = $tujuan ? [$tujuan, $nomor] : null;
            }
        }
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
                $pesan[] = ['baris_id' => $item->id, 'pesan' => $temuan, 'jenis' => self::PERINGATAN];
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
                    $pesan[] = ['baris_id' => $item->id, 'pesan' => "{$nama} merujuk ke Pasal {$nomor}, yang tidak ada di naskah ini.", 'jenis' => self::PERINGATAN];
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
