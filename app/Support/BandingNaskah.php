<?php

namespace App\Support;

use App\Models\Naskah;
use App\Models\NaskahBaris;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

/**
 * Menyusun naskah dalam bentuk cetak dengan perubahan ditandai: yang dihapus <del>, yang ditambah <ins>.
 * Bentuk bloknya sama dengan TataLetakNaskah, tetapi teksnya berupa HtmlString yang sudah aman ditampilkan,
 * jadi tampilan yang sama bisa dipakai untuk keduanya. Baris yang dihapus tetap muncul di sini.
 */
class BandingNaskah
{
    /** Dua baris teks dianggap versi dari baris yang sama bila setidaknya separuh katanya sama. */
    private const AMBANG_MIRIP = 0.5;

    /** @param Collection<int, NaskahBaris> $baris urut menurut kolom urutan */
    public static function susun(Naskah $naskah, Collection $baris): array
    {
        $sk = $naskah->jenis === 'Keputusan Bupati';
        $penanda = TemplateNaskah::penanda($naskah, $baris);
        $penandaAwal = self::penandaAwal($naskah, $baris);
        $blok = [];
        $sebelumnya = null;
        $lampiran = TataLetakNaskah::lampiranBaru($baris);

        foreach (TataLetakNaskah::lampiranDiAkhir($baris) as $item) {
            $lama = (string) $item->rumusan_awal;
            $baru = (string) $item->teksAkhir();

            if ($lama === '' && $baru === '' && blank($item->label)) {
                continue;
            }

            if ($item->bagian === 'lampiran') {
                $jumlahAwal = count($blok);
                TataLetakNaskah::bukaLampiran($blok, $lampiran, $item->status === NaskahBaris::DIHAPUS ? null : $item->label);
                $blok[] = ['tipe' => 'teks', 'baris' => self::teks($lama, $baru, true)];
                TataLetakNaskah::tandaiAsal($blok, $jumlahAwal, $item->id);
                self::tandaiUbahan($blok, $jumlahAwal, in_array($item->status, NaskahBaris::BERUBAH, true));

                continue;
            }

            $awalBagian = $item->bagian !== $sebelumnya;
            $sebelumnya = $item->bagian;
            $jumlahAwal = count($blok);
            $nomor = self::nomorButir($item, $penanda[$item->id] ?? null, $penandaAwal[$item->id] ?? null);

            match ($item->bagian) {
                'judul' => self::judul($blok, $lama, $baru, $sk),
                'menimbang' => self::butir($blok, $awalBagian, 'Menimbang', $nomor, $lama, $baru),
                'mengingat' => self::butir($blok, $awalBagian, 'Mengingat', $nomor, $lama, $baru),
                'memperhatikan' => self::butir($blok, $awalBagian, 'Memperhatikan', $nomor, $lama, $baru),
                'diktum' => $sk
                    ? self::diktumKeputusan($blok, $awalBagian, $nomor, $lama, $baru)
                    : self::diktumPeraturan($blok, $lama, $baru),
                'batang_tubuh' => self::batangTubuh($blok, $item, $lama, $baru),
                'penutup' => $blok[] = self::penutup($lama, $baru),
                default => $blok[] = ['tipe' => 'teks', 'baris' => self::teks($lama, $baru)],
            };

            TataLetakNaskah::tandaiAsal($blok, $jumlahAwal, $item->id);
            self::tandaiUbahan($blok, $jumlahAwal, in_array($item->status, NaskahBaris::BERUBAH, true));
        }

        TataLetakNaskah::tutupLampiran($blok, $lampiran);

        return $blok;
    }

    private static function tandaiUbahan(array &$blok, int $jumlahAwal, bool $diubah): void
    {
        foreach (array_slice(array_keys($blok), $jumlahAwal) as $i) {
            $blok[$i]['diubah'] = $diubah;
        }

        $terakhir = array_key_last($blok);

        if ($terakhir !== null && isset($blok[$terakhir]['butir'])) {
            $blok[$terakhir]['diubahButir'][array_key_last($blok[$terakhir]['butir'])] = $diubah;
        }
    }

    /** Nomor pada usulan awal, untuk baris yang dihapus: nomor sekarang sudah tidak menghitungnya. */
    private static function penandaAwal(Naskah $naskah, Collection $baris): array
    {
        $usulan = $baris
            ->filter(fn (NaskahBaris $b) => filled($b->rumusan_awal))
            ->map(fn (NaskahBaris $b) => (clone $b)->forceFill(['status' => NaskahBaris::BELUM]));

        return TemplateNaskah::penanda($naskah, $usulan);
    }

    /** Baris yang masih hidup memakai nomor sekarang; hanya baris yang hilang atau baru yang bernomor berwarna. */
    private static function nomorButir(NaskahBaris $item, ?string $sekarang, ?string $awal): ?HtmlString
    {
        return match (true) {
            $item->status === NaskahBaris::DIHAPUS => filled($awal) ? self::beda($awal, null) : null,
            blank($sekarang) => null,
            blank($item->rumusan_awal) => self::beda(null, $sekarang),
            default => self::beda($sekarang, $sekarang),
        };
    }

    private static function judul(array &$blok, string $lama, string $baru, bool $sk): void
    {
        $isi = self::barisTeksBiasa($lama, $baru);
        $blok[] = ['tipe' => 'tengah', 'baris' => $sk ? $isi : [TataLetakNaskah::PROVINSI, ...$isi]];

        if (! $sk) {
            $blok[] = ['tipe' => 'tengah', 'baris' => ['DENGAN RAHMAT TUHAN YANG MAHA ESA']];
        }

        $blok[] = ['tipe' => 'tengah', 'baris' => [TataLetakNaskah::KOP . ',']];
    }

    private static function butir(array &$blok, bool $awalBagian, string $label, ?HtmlString $nomor, string $lama, string $baru): void
    {
        if ($awalBagian) {
            $blok[] = ['tipe' => 'daftar', 'label' => $label, 'butir' => []];
        }

        $blok[array_key_last($blok)]['butir'][] = ['nomor' => $nomor, 'isi' => self::gabungBaris($lama, $baru)];
    }

    private static function diktumKeputusan(array &$blok, bool $awalBagian, ?HtmlString $nomor, string $lama, string $baru): void
    {
        if ($awalBagian) {
            $blok[] = ['tipe' => 'tengah', 'baris' => ['MEMUTUSKAN :']];
            $blok[] = ['tipe' => 'diktum', 'pembuka' => 'Menetapkan', 'butir' => []];
        }

        $blok[array_key_last($blok)]['butir'][] = ['nomor' => $nomor, 'baris' => self::teks($lama, $baru)];
    }

    private static function diktumPeraturan(array &$blok, string $lama, string $baru): void
    {
        $polaPenetapan = fn (?string $teks) => $teks !== null && preg_match('/^menetapkan\s*:\s*(.*)$/isu', $teks, $m) ? trim($m[1]) : null;

        foreach (self::selaras(TataLetakNaskah::barisIsi($lama), TataLetakNaskah::barisIsi($baru), 'strval') as [$x, $y]) {
            $penetapanX = $polaPenetapan($x);
            $penetapanY = $polaPenetapan($y);

            if ($penetapanX !== null || $penetapanY !== null) {
                $blok[] = ['tipe' => 'daftar', 'label' => 'Menetapkan', 'butir' => [['nomor' => null, 'isi' => self::beda($penetapanX, $penetapanY)]]];
            } else {
                $blok[] = ['tipe' => 'tengah', 'baris' => [self::beda($x, $y)]];
            }
        }
    }

    private static function batangTubuh(array &$blok, NaskahBaris $item, string $lama, string $baru): void
    {
        $label = (string) $item->label;
        $penanda = match (true) {
            $label === '' => null,
            $item->status === NaskahBaris::DIHAPUS => self::beda($label, null),
            $lama === '' => self::beda(null, $label),
            default => self::beda($label, $label),
        };

        if ($label !== '' && ! str_starts_with($label, 'Pasal')) {
            $blok[] = ['tipe' => 'tengah', 'baris' => array_merge([$penanda], self::barisTeksBiasa($lama, $baru))];

            return;
        }

        if ($penanda !== null) {
            $blok[] = ['tipe' => 'tengah', 'baris' => [$penanda]];
        }

        $blok[] = ['tipe' => 'teks', 'baris' => self::teks($lama, $baru)];
    }

    private static function penutup(string $lama, string $baru): array
    {
        $a = TataLetakNaskah::penutup($lama);
        $b = TataLetakNaskah::penutup($baru);

        return [
            'tipe' => 'penutup',
            'ditetapkan' => self::gabungDaftar($a['ditetapkan'], $b['ditetapkan']),
            'diundangkan' => self::gabungDaftar($a['diundangkan'], $b['diundangkan']),
            'berita' => $a['berita'] === null && $b['berita'] === null ? null : self::beda($a['berita'], $b['berita']),
            'tembusan' => self::gabungDaftar($a['tembusan'], $b['tembusan']),
        ];
    }

    /** Isi pasal: tiap baris tetap punya tingkat dan nomor seperti bentuk cetaknya. */
    public static function teks(string $lama, string $baru, bool $lampiran = false): array
    {
        $kunci = fn (array $b) => isset($b['tabel'])
            ? 'tabel ' . implode(' ', array_merge(...$b['tabel']))
            : trim($b['nomor'] . ' ' . $b['isi']);

        $hasil = [];

        foreach (self::selaras(TataLetakNaskah::barisTeks($lama, $lampiran), TataLetakNaskah::barisTeks($baru, $lampiran), $kunci) as [$x, $y]) {
            // Tabel yang berhadapan dengan teks biasa bukan versi dari baris yang sama.
            if ($x !== null && $y !== null && isset($x['tabel']) !== isset($y['tabel'])) {
                $hasil[] = self::pasangTeks($x, null);
                $hasil[] = self::pasangTeks(null, $y);
            } else {
                $hasil[] = self::pasangTeks($x, $y);
            }
        }

        return $hasil;
    }

    private static function pasangTeks(?array $x, ?array $y): array
    {
        if (isset($x['tabel']) || isset($y['tabel'])) {
            return [
                'tingkat' => ($y ?? $x)['tingkat'],
                'nomor' => null,
                'isi' => '',
                'tabel' => self::gabungTabel($x['tabel'] ?? [], $y['tabel'] ?? []),
                'rata' => ($y ?? $x)['rata'] ?? null,
                'judul' => ($y ?? $x)['judul'] ?? false,
            ];
        }

        $nomorX = $x['nomor'] ?? null;
        $nomorY = $y['nomor'] ?? null;

        return [
            'tingkat' => ($y ?? $x)['tingkat'],
            'nomor' => $nomorX === null && $nomorY === null ? null : self::beda($nomorX, $nomorY),
            'isi' => self::beda($x['isi'] ?? null, $y['isi'] ?? null),
            'tengah' => ($y ?? $x)['tengah'] ?? false,
        ];
    }

    /** Baris tabel yang berubah ditandai per sel; yang hilang atau baru ditandai seluruh barisnya. */
    private static function gabungTabel(array $lama, array $baru): array
    {
        $hasil = [];

        foreach (self::selaras($lama, $baru, fn (array $sel) => implode(' ', $sel)) as [$x, $y]) {
            $jumlah = max(count($x ?? []), count($y ?? []));
            $sel = [];

            for ($i = 0; $i < $jumlah; $i++) {
                $sel[] = self::beda($x === null ? null : ($x[$i] ?? ''), $y === null ? null : ($y[$i] ?? ''));
            }

            $hasil[] = $sel;
        }

        return $hasil;
    }

    private static function barisTeksBiasa(string $lama, string $baru): array
    {
        return self::gabungDaftar(TataLetakNaskah::barisIsi($lama), TataLetakNaskah::barisIsi($baru));
    }

    /** @return list<HtmlString> */
    private static function gabungDaftar(array $lama, array $baru): array
    {
        return array_map(fn (array $pasangan) => self::beda(...$pasangan), self::selaras($lama, $baru, 'strval'));
    }

    /** Teks berbaris banyak menjadi satu teks, untuk Menimbang, Mengingat, dan diktum. */
    private static function gabungBaris(string $lama, string $baru): HtmlString
    {
        // Teks kosong bukan satu baris kosong: baris yang dihapus atau disisipkan hanya punya satu sisi.
        $pecah = fn (string $teks) => $teks === '' ? [] : preg_split('/\R/u', $teks);
        $baris = self::selaras($pecah($lama), $pecah($baru), 'strval');

        return new HtmlString(implode("\n", array_map(fn (array $pasangan) => (string) self::beda(...$pasangan), $baris)));
    }

    private static function beda(?string $lama, ?string $baru): HtmlString
    {
        return new HtmlString(match (true) {
            (string) $lama === (string) $baru => e((string) $baru),
            (string) $lama === '' => '<ins>' . e((string) $baru) . '</ins>',
            (string) $baru === '' => '<del>' . e((string) $lama) . '</del>',
            default => DiffKata::html($lama, $baru),
        });
    }

    /**
     * Menjajarkan dua daftar baris: baris yang persis sama menjadi patokan, di antaranya
     * baris yang mirip dipasangkan sebagai versi dari baris yang sama.
     *
     * @return list<array{0: mixed, 1: mixed}> pasangan [lama, baru]; salah satunya null bila baris itu hilang atau baru
     */
    private static function selaras(array $lama, array $baru, callable $kunci): array
    {
        $kunciLama = array_map($kunci, $lama);
        $kunciBaru = array_map($kunci, $baru);

        if ($kunciLama === $kunciBaru) {
            return array_map(null, $lama, $baru);
        }

        $n = count($lama);
        $m = count($baru);
        $tabel = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));

        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $tabel[$i][$j] = $kunciLama[$i] === $kunciBaru[$j]
                    ? $tabel[$i + 1][$j + 1] + 1
                    : max($tabel[$i + 1][$j], $tabel[$i][$j + 1]);
            }
        }

        $hasil = [];
        $sisaLama = $sisaBaru = [];
        $i = $j = 0;

        while ($i < $n || $j < $m) {
            if ($i < $n && $j < $m && $kunciLama[$i] === $kunciBaru[$j]) {
                array_push($hasil, ...self::pasangkan($sisaLama, $sisaBaru, $kunci));
                $sisaLama = $sisaBaru = [];
                $hasil[] = [$lama[$i++], $baru[$j++]];
            } elseif ($j < $m && ($i === $n || $tabel[$i][$j + 1] >= $tabel[$i + 1][$j])) {
                $sisaBaru[] = $baru[$j++];
            } else {
                $sisaLama[] = $lama[$i++];
            }
        }

        array_push($hasil, ...self::pasangkan($sisaLama, $sisaBaru, $kunci));

        return $hasil;
    }

    private static function pasangkan(array $lama, array $baru, callable $kunci): array
    {
        $hasil = [];
        $j = 0;

        foreach ($lama as $x) {
            $cocok = null;

            for ($k = $j; $k < count($baru); $k++) {
                if (self::mirip($kunci($x), $kunci($baru[$k]))) {
                    $cocok = $k;
                    break;
                }
            }

            if ($cocok === null) {
                $hasil[] = [$x, null];

                continue;
            }

            for (; $j < $cocok; $j++) {
                $hasil[] = [null, $baru[$j]];
            }

            $hasil[] = [$x, $baru[$j++]];
        }

        for (; $j < count($baru); $j++) {
            $hasil[] = [null, $baru[$j]];
        }

        return $hasil;
    }

    private static function mirip(string $a, string $b): bool
    {
        $kataA = preg_split('/\s+/u', mb_strtolower(trim($a)), -1, PREG_SPLIT_NO_EMPTY);
        $kataB = preg_split('/\s+/u', mb_strtolower(trim($b)), -1, PREG_SPLIT_NO_EMPTY);

        if (! $kataA || ! $kataB) {
            return false;
        }

        return count(array_intersect($kataA, $kataB)) / max(count($kataA), count($kataB)) >= self::AMBANG_MIRIP;
    }
}
