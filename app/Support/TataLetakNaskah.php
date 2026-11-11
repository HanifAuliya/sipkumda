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
 * - daftar  : Menimbang/Mengingat/Memperhatikan dengan label dan butir bernomor
 * - teks    : isi pasal, tiap baris punya tingkat dan nomor
 * - diktum  : KESATU, KEDUA pada Keputusan Bupati, tiap diktum bisa memuat butir
 * - penutup : blok Ditetapkan, Diundangkan, Berita Daerah, dan Tembusan
 *
 * Susunannya mengikuti produk hukum yang terbit di JDIH Kabupaten Hulu Sungai Tengah.
 */
class TataLetakNaskah
{
    public const KOP = 'BUPATI HULU SUNGAI TENGAH';

    /** Dicetak di bawah kop Peraturan Bupati; Keputusan Bupati tidak memakainya. */
    public const PROVINSI = 'PROVINSI KALIMANTAN SELATAN';

    /** @param Collection<int, NaskahBaris> $baris urut menurut kolom urutan */
    public static function susun(Naskah $naskah, Collection $baris): array
    {
        $sk = $naskah->jenis === 'Keputusan Bupati';
        $penanda = TemplateNaskah::penanda($naskah, $baris);
        $blok = [];
        $sebelumnya = null;
        $lampiran = self::lampiranBaru($baris);

        foreach (self::lampiranDiAkhir($baris) as $item) {
            $teks = $item->teksAkhir();

            // Baris yang dihapus atau masih kosong tidak dicetak.
            if ($item->status === NaskahBaris::DIHAPUS || (blank($teks) && blank($item->label))) {
                continue;
            }

            if ($item->bagian === 'lampiran') {
                $jumlahAwal = count($blok);
                self::bukaLampiran($blok, $lampiran, $item->label);
                $blok[] = ['tipe' => 'teks', 'baris' => self::barisTeks($teks, true)];
                self::tandaiAsal($blok, $jumlahAwal, $item->id);

                continue;
            }

            $baru = $item->bagian !== $sebelumnya;
            $sebelumnya = $item->bagian;
            $jumlahAwal = count($blok);

            match ($item->bagian) {
                'judul' => self::judul($blok, $teks, $sk),
                'menimbang' => self::butir($blok, $baru, 'Menimbang', $penanda[$item->id], $teks),
                'mengingat' => self::butir($blok, $baru, 'Mengingat', $penanda[$item->id], $teks),
                'memperhatikan' => self::butir($blok, $baru, 'Memperhatikan', $penanda[$item->id], $teks),
                'diktum' => $sk
                    ? self::diktumKeputusan($blok, $baru, $penanda[$item->id], $teks)
                    : self::diktumPeraturan($blok, $teks),
                'batang_tubuh' => self::batangTubuh($blok, $item->label, $teks),
                'penutup' => $blok[] = self::penutup($teks),
                default => $blok[] = ['tipe' => 'teks', 'baris' => self::barisTeks($teks)],
            };

            self::tandaiAsal($blok, $jumlahAwal, $item->id);
        }

        self::tutupLampiran($blok, $lampiran);

        return $blok;
    }

    /**
     * Bekal mencetak lampiran: kepalanya mengulang judul naskah, tanda tangannya mengulang
     * jabatan dan nama pada penutup (tanpa tempat dan tanggal).
     *
     * @param  Collection<int, NaskahBaris>  $baris
     * @return array{judul: list<string>, jabatan: list<string>, terbuka: bool}
     */
    public static function lampiranBaru(Collection $baris): array
    {
        $judul = $baris->firstWhere('bagian', 'judul')?->teksAkhir();
        $penutup = self::penutup($baris->firstWhere('bagian', 'penutup')?->teksAkhir())['ditetapkan'];
        $jabatan = array_values(array_filter($penutup, fn (string $b) => ! self::barisTempatTanggal($b)));

        while ($jabatan && $jabatan[0] === '') {
            array_shift($jabatan);
        }

        return [
            'judul' => self::barisIsi($judul),
            'jabatan' => $jabatan ?: [self::KOP . ','],
            'terbuka' => false,
        ];
    }

    /**
     * Lampiran selalu dicetak sesudah tanda tangan naskah, di halaman baru, walaupun barisnya
     * tersimpan di tengah (misalnya karena disisipkan sebelum penutup).
     *
     * @param  Collection<int, NaskahBaris>  $baris
     * @return Collection<int, NaskahBaris>
     */
    public static function lampiranDiAkhir(Collection $baris): Collection
    {
        [$lampiran, $lainnya] = $baris->partition(fn (NaskahBaris $b) => $b->bagian === 'lampiran');

        return $lainnya->concat($lampiran)->values();
    }

    /**
     * Baris lampiran pertama, atau yang diberi penanda (misalnya "LAMPIRAN II"), memulai halaman
     * lampiran baru. Lampiran sebelumnya ditutup dengan tanda tangan.
     */
    public static function bukaLampiran(array &$blok, array &$lampiran, ?string $label): void
    {
        if ($lampiran['terbuka'] && blank($label)) {
            return;
        }

        self::tutupLampiran($blok, $lampiran);
        $blok[] = ['tipe' => 'lampiran', 'kepala' => [filled($label) ? mb_strtoupper(trim($label)) : 'LAMPIRAN', ...$lampiran['judul']]];
        $lampiran['terbuka'] = true;
    }

    public static function tutupLampiran(array &$blok, array &$lampiran): void
    {
        if ($lampiran['terbuka']) {
            $blok[] = ['tipe' => 'ttd', 'baris' => $lampiran['jabatan']];
            $lampiran['terbuka'] = false;
        }
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
        $blok[] = ['tipe' => 'tengah', 'baris' => $sk ? self::barisIsi($teks) : [self::PROVINSI, ...self::barisIsi($teks)]];

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
            $blok[] = ['tipe' => 'diktum', 'pembuka' => 'Menetapkan', 'butir' => []];
        }

        $blok[array_key_last($blok)]['butir'][] = ['nomor' => $nomor, 'baris' => self::barisTeks($teks)];
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
        $ditetapkan = $diundangkan = $tembusan = [];
        $berita = null;
        $sedangDiundangkan = $sedangTembusan = false;

        foreach (preg_split('/\R/u', (string) $teks) as $baris) {
            $baris = trim($baris);

            if (preg_match('/^BERITA DAERAH\b|^\(?LEMBARAN DAERAH\b/iu', $baris)) {
                $berita = $baris;
                continue;
            }

            if (preg_match('/^tembusan\b/iu', $baris)) {
                $sedangTembusan = true;
            }

            if ($sedangTembusan) {
                if ($baris !== '') {
                    $tembusan[] = $baris;
                }
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
            'tembusan' => $tembusan,
        ];
    }

    /** Tempat dan tanggal rata kiri; jabatan dan nama pejabat rata tengah sesamanya. */
    public static function barisTempatTanggal(string|\Stringable $baris): bool
    {
        return preg_match('/^(?:(?:ditetapkan|diundangkan)\s+di\b|pada\s+tanggal\b)/iu', trim(strip_tags((string) $baris))) === 1;
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
     * Gaya nomor rincian dari tingkat teratas, mengikuti Lampiran II UU 12/2011:
     * ayat (1), huruf a., angka 1., huruf a), angka 1). Dipakai barisTeks dan rapikanNomor.
     */
    public const GAYA_NOMOR = [
        'ayat' => '/^(\(\d+[a-z]?\))\s*(.*)$/su',
        'huruf' => '/^([a-z]\.)(?:\s+(.*))?$/su',
        'angka' => '/^(\d{1,3}\.)(?:\s+(.*))?$/su',
        'huruf_kurung' => '/^([a-z]\))(?:\s+(.*))?$/su',
        'angka_kurung' => '/^(\d{1,3}\))(?:\s+(.*))?$/su',
    ];

    /** @return array{0: ?string, 1: ?string, 2: string} gaya, nomor seperti tertulis, dan isi tanpa nomor */
    public static function nomorBaris(string $baris): array
    {
        foreach (self::GAYA_NOMOR as $gaya => $pola) {
            if (preg_match($pola, $baris, $m)) {
                return [$gaya, $m[1], $m[2] ?? ''];
            }
        }

        return [null, null, $baris];
    }

    /**
     * Mengenali ayat dan butir pada isi pasal. Tingkatnya dihitung dari gaya yang sedang terbuka:
     * a. di bawah (1) menjorok satu tingkat, 1. di bawah a. menjorok lagi, dan seterusnya.
     * Daftar yang langsung dimulai dengan 1. (misalnya Ketentuan Umum) tetap di tingkat pertama.
     * Kalimat biasa menutup daftar yang sedang berjalan, kecuali ayatnya.
     * Tabel menjorok sejajar dengan teks butir di atasnya.
     * Lampiran: baris berhuruf kapital semua tanpa nomor (judul) diberi tanda tengah.
     *
     * @return array<int, array{tingkat: int, nomor: ?string, isi: string, tabel?: list<list<string>>, rata?: ?list<string>, judul?: bool, tengah?: bool}>
     */
    public static function barisTeks(?string $teks, bool $lampiran = false): array
    {
        $urutan = array_flip(array_keys(self::GAYA_NOMOR));
        $terbuka = [];
        $hasil = [];
        $tunda = null;

        foreach (self::barisIsi($teks) as $baris) {
            // Tabel ditulis sebagai baris "| a | b |"; baris yang berurutan menjadi satu tabel.
            if (str_starts_with($baris, '|')) {
                $sebelum = $hasil === [] ? null : $hasil[array_key_last($hasil)];
                $tingkat = $sebelum === null || $sebelum['nomor'] === null ? 0 : $sebelum['tingkat'] + 1;
                self::barisKeTabel($hasil, $baris, $tunda, ['tingkat' => $tingkat, 'nomor' => null, 'isi' => '']);

                continue;
            }

            $tunda = null;

            [$gaya, $nomor, $isi] = self::nomorBaris($baris);

            if ($gaya === null) {
                $terbuka = array_values(array_intersect($terbuka, ['ayat']));
                $item = ['tingkat' => 0, 'nomor' => null, 'isi' => $baris];

                if ($lampiran && self::judulLampiran($baris)) {
                    $item['tengah'] = true;
                }

                $hasil[] = $item;

                continue;
            }

            $terbuka = array_values(array_filter($terbuka, fn (string $g) => $urutan[$g] < $urutan[$gaya]));
            $hasil[] = ['tingkat' => count($terbuka), 'nomor' => $nomor, 'isi' => $isi];
            $terbuka[] = $gaya;
        }

        return $hasil;
    }

    /**
     * Baris tabel yang hanya berisi sel pertama, misalnya judul kelompok "SEKRETARIAT MAJELIS",
     * dicetak sebagai satu sel selebar tabel (seperti sel gabungan di Word).
     */
    public static function selBentang(array $sel): bool
    {
        $isi = array_map(fn ($s) => trim(strip_tags((string) $s)), array_values($sel));

        return $isi !== [] && $isi[0] !== '' && implode('', array_slice($isi, 1)) === '';
    }

    /** Judul di dalam lampiran: huruf kapital semua, misalnya "SUSUNAN ANGGOTA" atau "BAB I". */
    public static function judulLampiran(string $baris): bool
    {
        return preg_match('/\p{Lu}{2}/u', $baris) === 1 && preg_match('/\p{Ll}/u', $baris) === 0;
    }

    /**
     * Memisahkan teks biasa dan tabel untuk tampilan layar.
     *
     * @return list<array{teks?: string, tabel?: list<list<string>>, rata?: ?list<string>, judul?: bool}>
     */
    public static function potongan(?string $teks): array
    {
        $hasil = [];
        $tunda = null;

        foreach (preg_split('/\R/u', (string) $teks) as $baris) {
            $terakhir = array_key_last($hasil);

            if (str_starts_with(ltrim($baris), '|')) {
                self::barisKeTabel($hasil, $baris, $tunda, []);

                continue;
            }

            $tunda = null;

            if ($terakhir !== null && isset($hasil[$terakhir]['teks'])) {
                $hasil[$terakhir]['teks'] .= "\n{$baris}";
            } else {
                $hasil[] = ['teks' => $baris];
            }
        }

        return $hasil;
    }

    /** @return array<int, string>|null isi sel, atau null untuk baris pemisah "| --- |" dan baris kosong */
    public static function selTabel(string $baris): ?array
    {
        $sel = self::pecahSel($baris);

        return self::rataTabel($baris) === null && array_filter($sel, fn ($s) => $s !== '') ? $sel : null;
    }

    /**
     * Baris pemisah "| :-- | :-: | --: |" menyimpan rata tiap kolom: kiri, tengah, kanan.
     * Letaknya sesudah baris pertama menandakan baris pertama adalah judul tabel;
     * di atas baris pertama berarti tabel tanpa baris judul.
     *
     * @return list<string>|null rata CSS (left, center, right), atau null bila bukan baris pemisah
     */
    public static function rataTabel(string $baris): ?array
    {
        $sel = self::pecahSel($baris);

        foreach ($sel as $s) {
            if (! preg_match('/^(:?-+:?)?$/', $s)) {
                return null;
            }
        }

        if (! str_contains(implode('', $sel), '-')) {
            return null;
        }

        return array_map(fn (string $s) => match (true) {
            strlen($s) > 1 && str_starts_with($s, ':') && str_ends_with($s, ':') => 'center',
            str_ends_with($s, ':') => 'right',
            default => 'left',
        }, $sel);
    }

    /** @return list<string> */
    private static function pecahSel(string $baris): array
    {
        $isi = preg_replace('/^\||\|$/', '', trim($baris));

        return array_map('trim', explode('|', $isi));
    }

    /**
     * Menambahkan satu baris "| a | b |" ke tabel terakhir di $hasil, atau membuka tabel baru
     * berbentuk $baru. Baris pemisah tidak menjadi isi; rata kolomnya disimpan di tabelnya.
     *
     * @param  list<string>|null  $tunda  rata dari baris pemisah di atas baris pertama tabel
     */
    private static function barisKeTabel(array &$hasil, string $baris, ?array &$tunda, array $baru): void
    {
        $terakhir = array_key_last($hasil);
        $terbuka = $terakhir !== null && isset($hasil[$terakhir]['tabel']);
        $rata = self::rataTabel($baris);

        if ($rata !== null) {
            if ($terbuka) {
                $hasil[$terakhir]['rata'] = $rata;
                $hasil[$terakhir]['judul'] = count($hasil[$terakhir]['tabel']) === 1;
            } else {
                $tunda = $rata;
            }

            return;
        }

        $sel = self::selTabel($baris);

        if ($sel === null) {
            return;
        }

        if ($terbuka) {
            $hasil[$terakhir]['tabel'][] = $sel;
        } else {
            $hasil[] = $baru + ['tabel' => [$sel], 'rata' => $tunda, 'judul' => false];
        }

        $tunda = null;
    }

    /**
     * Baris tabel siap digambar: kolom yang kurang dilengkapi, rata kolom diterapkan, baris judul
     * rata tengah, dan baris yang hanya berisi sel pertama dibentangkan selebar tabel.
     *
     * @param  array{tabel: list<list<mixed>>, rata?: ?list<string>, judul?: bool}  $item
     * @return list<array{judul: bool, sel: list<array{isi: mixed, bentang: int, rata: string, tebal: bool}>}>
     */
    public static function susunTabel(array $item): array
    {
        $kolom = max(array_map('count', $item['tabel']));
        $rata = $item['rata'] ?? [];
        $hasil = [];

        foreach (array_values($item['tabel']) as $r => $sel) {
            $judul = ($item['judul'] ?? false) && $r === 0;
            $sel = array_values($sel);

            if ($kolom > 1 && ! $judul && self::selBentang($sel)) {
                $hasil[] = ['judul' => false, 'sel' => [['isi' => $sel[0], 'bentang' => $kolom, 'rata' => 'left', 'tebal' => true]]];

                continue;
            }

            $baris = [];

            for ($i = 0; $i < $kolom; $i++) {
                $baris[] = ['isi' => $sel[$i] ?? '', 'bentang' => 1, 'rata' => $judul ? 'center' : ($rata[$i] ?? 'left'), 'tebal' => false];
            }

            $hasil[] = ['judul' => $judul, 'sel' => $baris];
        }

        return $hasil;
    }

    /**
     * Mengurutkan ulang nomor ayat dan butir setelah ada yang disisipkan atau dihapus.
     * Nomor di bawah suatu tingkat mulai lagi dari awal tiap kali tingkat itu berganti nomor;
     * kalimat biasa mengakhiri daftar yang sedang berjalan, kecuali ayatnya. Ayat sisipan seperti "(1a)"
     * dibiarkan dan tidak ikut dihitung (sama dengan editor-rumusan.js).
     * Rujukan di dalam kalimat, misalnya "ayat (1)", tidak diubah dan tetap perlu diperiksa.
     */
    public static function rapikanNomor(?string $teks): string
    {
        $urutan = array_flip(array_keys(self::GAYA_NOMOR));
        $hitung = array_fill_keys(array_keys(self::GAYA_NOMOR), 0);
        $hasil = [];

        foreach (preg_split('/\R/u', (string) $teks) as $baris) {
            preg_match('/^(\s*)(.*)$/su', $baris, $m);
            [, $tepi, $isiBaris] = $m;
            [$gaya, , $isi] = $isiBaris === '' || str_starts_with($isiBaris, '|') ? [null, null, ''] : self::nomorBaris($isiBaris);

            // Ayat sisipan, misalnya (1a), nomornya tetap.
            $sisipan = $gaya === 'ayat' && preg_match('/^\(\d+[a-z]\)/', $isiBaris) === 1;

            if ($gaya !== null && ! $sisipan) {
                foreach ($hitung as $g => $n) {
                    if ($urutan[$g] > $urutan[$gaya]) {
                        $hitung[$g] = 0;
                    }
                }

                $ke = ++$hitung[$gaya];
                $nomor = match ($gaya) {
                    'ayat' => "({$ke})",
                    'huruf' => self::hurufKe($ke) . '.',
                    'angka' => "{$ke}.",
                    'huruf_kurung' => self::hurufKe($ke) . ')',
                    'angka_kurung' => "{$ke})",
                };
                $baris = rtrim("{$tepi}{$nomor} {$isi}");
            } elseif ($gaya === null && $isiBaris !== '' && ! str_starts_with($isiBaris, '|')) {
                $hitung = ['ayat' => $hitung['ayat']] + array_fill_keys(array_keys(self::GAYA_NOMOR), 0);
            }

            $hasil[] = $baris;
        }

        return implode("\n", $hasil);
    }

    /** a sampai z, lalu aa, ab, dan seterusnya. */
    private static function hurufKe(int $ke): string
    {
        $huruf = '';

        for (; $ke > 0; $ke = intdiv($ke - 1, 26)) {
            $huruf = chr(97 + ($ke - 1) % 26) . $huruf;
        }

        return $huruf;
    }
}
