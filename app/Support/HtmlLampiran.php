<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Isi lampiran dari editor seperti Word (Tiptap). Sebelum disimpan, HTML-nya dibersihkan dengan daftar putih:
 * hanya tag teks, daftar, dan tabel, atribut colspan/rowspan/colwidth/start, dan sebagian gaya (rata, lebar, garis,
 * jorok, tebal). Selain itu dibuang, termasuk skrip, tautan, gambar, dan gaya yang bisa memuat alamat (url()),
 * supaya aman ditampilkan di layar dan aman dirender dompdf.
 */
class HtmlLampiran
{
    /** Tag yang dipertahankan; tag lain dibuka (isinya tetap) kecuali yang ada di BUANG. */
    private const TAG = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup', 'span',
        'ol', 'ul', 'li', 'h2', 'h3', 'h4', 'blockquote', 'hr',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'colgroup', 'col', 'caption',
    ];

    /** Dibuang beserta isinya. */
    private const BUANG = ['script', 'style', 'iframe', 'object', 'embed', 'link', 'meta', 'title', 'head', 'img', 'svg', 'math', 'form', 'input', 'button', 'select', 'textarea', 'template', 'noscript'];

    private const ATRIBUT = ['colspan', 'rowspan', 'colwidth', 'start', 'style'];

    /** Wadah yang hanya berisi blok. */
    private const WADAH = ['div', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'ol', 'ul', 'colgroup'];

    /** Properti gaya yang boleh, dengan pola nilainya. */
    private const GAYA = [
        'text-align' => '/^(left|right|center|justify)$/',
        'vertical-align' => '/^(top|middle|bottom)$/',
        'font-weight' => '/^(bold|normal|[1-9]00)$/',
        'font-style' => '/^(italic|normal)$/',
        'text-decoration' => '/^(underline|line-through|none)$/',
        'font-size' => '/^\d+(\.\d+)?(px|pt|em|rem|%)$/',
        'width' => '/^\d+(\.\d+)?(px|pt|cm|mm|em|%)$/',
        'height' => '/^\d+(\.\d+)?(px|pt|cm|mm|em)$/',
        'margin-left' => '/^(auto|\d+(\.\d+)?(px|pt|cm|mm|em))$/',
        'margin-right' => '/^(auto|0(px)?)$/',
        'padding-left' => '/^\d+(\.\d+)?(px|pt|cm|mm|em)$/',
        'text-indent' => '/^-?\d+(\.\d+)?(px|pt|cm|mm|em)$/',
        'list-style-type' => '/^(decimal|lower-alpha|upper-alpha|lower-latin|upper-latin|lower-roman|upper-roman|disc|circle|square|none)$/',
        'border' => self::POLA_GARIS,
        'border-top' => self::POLA_GARIS,
        'border-right' => self::POLA_GARIS,
        'border-bottom' => self::POLA_GARIS,
        'border-left' => self::POLA_GARIS,
        'border-style' => '/^(none|solid|dashed|dotted|double)( (none|solid|dashed|dotted|double)){0,3}$/',
        'border-width' => '/^\d+(\.\d+)?(px|pt)( \d+(\.\d+)?(px|pt)){0,3}$/',
        'border-color' => '/^(#[0-9a-f]{3,8}|rgb\([\d ,.%]+\)|[a-z]+)( (#[0-9a-f]{3,8}|rgb\([\d ,.%]+\)|[a-z]+)){0,3}$/',
        'background-color' => '/^(#[0-9a-f]{3,8}|rgba?\([\d ,.%]+\)|[a-z]+)$/',
        'color' => '/^(#[0-9a-f]{3,8}|rgba?\([\d ,.%]+\)|[a-z]+)$/',
    ];

    private const POLA_GARIS = '/^(none|0|(\d+(\.\d+)?(px|pt) )?(solid|dashed|dotted|double)( (#[0-9a-f]{3,8}|rgb\([\d ,.%]+\)|[a-z]+))?)$/';

    /** HTML bersih, atau null bila tidak ada isinya (hanya paragraf kosong). */
    public static function bersihkan(?string $html): ?string
    {
        if (blank($html)) {
            return null;
        }

        $dom = self::dom($html);
        $akar = $dom->getElementById('akar');
        if (! $akar) {
            return null;
        }

        self::bersihkanAnak($akar);

        // Editor menyisakan paragraf kosong di bawah tabel supaya kursor bisa keluar; tidak ikut disimpan.
        // Diperiksa per elemen, bukan dengan regex: pada lampiran panjang regex-nya gagal dan isinya hilang.
        while (($akhir = $akar->lastChild) && preg_replace('/[\s\x{00A0}]+/u', '', $akhir->textContent) === ''
            && ($akhir->nodeType === XML_TEXT_NODE || $akhir->nodeName === 'p')) {
            $akar->removeChild($akhir);
        }

        $hasil = '';
        foreach ($akar->childNodes as $anak) {
            $hasil .= $dom->saveHTML($anak);
        }
        $hasil = trim($hasil);

        return self::kosong($hasil) ? null : $hasil;
    }

    /** Tidak ada teks maupun tabel. */
    public static function kosong(?string $html): bool
    {
        return blank(self::teks($html)) && ! str_contains((string) $html, '<table');
    }

    /**
     * Teks polos dari HTML: tiap paragraf satu baris, butir daftar dengan nomornya, dan baris tabel menjadi
     * "| a | b |", bentuk tabel yang dikenali TataLetakNaskah, sehingga matriks tetap mencetaknya sebagai tabel.
     */
    public static function teks(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        return implode("\n", self::barisTeks(self::dom($html)->getElementById('akar')));
    }

    /** @return list<string> */
    private static function barisTeks(DOMNode $node): array
    {
        $hasil = [''];
        $rapikan = fn (string $t) => preg_replace('/[\s\x{00A0}]+/u', ' ', $t);

        foreach ($node->childNodes as $anak) {
            if ($anak->nodeType === XML_TEXT_NODE) {
                $hasil[array_key_last($hasil)] .= $rapikan($anak->nodeValue);

                continue;
            }
            if (! $anak instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($anak->tagName);

            if ($tag === 'br') {
                $hasil[] = '';
            } elseif ($tag === 'table') {
                array_push($hasil, ...self::barisTabel($anak), ...['']);
            } elseif (in_array($tag, ['ol', 'ul'], true)) {
                array_push($hasil, ...self::barisDaftar($anak), ...['']);
            } elseif (in_array($tag, ['p', 'h2', 'h3', 'h4', 'blockquote', 'div', 'li', 'hr'], true)) {
                array_push($hasil, ...self::barisTeks($anak), ...['']);
            } else {
                // Tag di dalam kalimat: menyambung baris yang sedang berjalan.
                $isi = self::barisTeks($anak);
                $hasil[array_key_last($hasil)] .= array_shift($isi) ?? '';
                array_push($hasil, ...$isi);
            }
        }

        // Di dalam kalimat spasi pinggir tetap berarti ("<b>a</b> b"); baris kosong dibuang di tingkat blok.
        if (! in_array(strtolower($node->nodeName), ['div', 'p', 'h2', 'h3', 'h4', 'blockquote', 'li', 'td', 'th', 'caption'], true)) {
            return $hasil;
        }

        return array_values(array_filter(array_map(fn (string $t) => trim($rapikan($t)), $hasil), fn (string $t) => $t !== ''));
    }

    /** @return list<string> */
    private static function barisDaftar(DOMElement $daftar): array
    {
        $hasil = [];
        $nomor = max(1, (int) $daftar->getAttribute('start'));
        preg_match('/list-style-type:\s*([a-z-]+)/', $daftar->getAttribute('style'), $m);

        foreach ($daftar->childNodes as $li) {
            if (! $li instanceof DOMElement || $li->tagName !== 'li') {
                continue;
            }

            $penanda = $daftar->tagName === 'ul' ? '-' : self::penanda($nomor++, $m[1] ?? 'decimal') . '.';
            $baris = self::barisTeks($li) ?: [''];
            $baris[0] = trim("{$penanda} {$baris[0]}");
            array_push($hasil, ...$baris);
        }

        return $hasil;
    }

    private static function penanda(int $n, string $gaya): string
    {
        $huruf = fn () => $n <= 26 ? chr(96 + $n) : chr(96 + intdiv($n - 1, 26)) . chr(97 + ($n - 1) % 26);
        $romawi = function () use ($n) {
            $hasil = '';
            foreach (['x' => 10, 'ix' => 9, 'v' => 5, 'iv' => 4, 'i' => 1] as $tanda => $nilai) {
                for (; $n >= $nilai; $n -= $nilai) {
                    $hasil .= $tanda;
                }
            }

            return $hasil;
        };

        return match ($gaya) {
            'lower-alpha', 'lower-latin' => $huruf(),
            'upper-alpha', 'upper-latin' => strtoupper($huruf()),
            'lower-roman' => $romawi(),
            'upper-roman' => strtoupper($romawi()),
            default => (string) $n,
        };
    }

    /** Baris tabel sebagai "| a | b |"; sel lanjutan dari gabungan dikosongkan supaya kolomnya tidak bergeser. */
    private static function barisTabel(DOMElement $tabel): array
    {
        return array_map(function (array $baris) {
            $sel = [];
            foreach ($baris as $s) {
                $sel[] = $s['el'] && ! $s['lanjutan'] ? str_replace('|', '/', implode(' ', self::barisTeks($s['el']))) : '';
                array_push($sel, ...array_fill(0, $s['rentang'] - 1, ''));
            }

            return '| ' . implode(' | ', $sel) . ' |';
        }, self::kisi($tabel));
    }

    /**
     * Susunan sel tabel per baris. Sel yang digabung ke bawah muncul lagi di baris berikutnya sebagai lanjutan,
     * jadi nomor kolom tiap sel selalu tepat.
     *
     * @return list<list<array{el: DOMElement, kolom: int, rentang: int, turun: int, lanjutan: bool}>>
     */
    public static function kisi(DOMElement $tabel): array
    {
        $hasil = [];
        $gantung = []; // kolom => [sel, sisa baris]

        foreach ($tabel->getElementsByTagName('tr') as $tr) {
            // Baris milik tabel di dalam sel tidak ikut.
            for ($induk = $tr->parentNode; $induk instanceof DOMElement && $induk->tagName !== 'table'; $induk = $induk->parentNode);
            if ($induk !== $tabel) {
                continue;
            }

            $baris = [];
            $kolom = 0;
            $lanjut = function () use (&$gantung, &$kolom, &$baris) {
                while (isset($gantung[$kolom])) {
                    [$sel] = $gantung[$kolom];
                    $baris[] = ['lanjutan' => true] + $sel;
                    if (--$gantung[$kolom][1] <= 0) {
                        unset($gantung[$kolom]);
                    }
                    $kolom += $sel['rentang'];
                }
            };

            $baru = [];
            foreach ($tr->childNodes as $td) {
                if (! $td instanceof DOMElement || ! in_array($td->tagName, ['td', 'th'], true)) {
                    continue;
                }
                $lanjut();
                $sel = [
                    'el' => $td,
                    'kolom' => $kolom,
                    'rentang' => max(1, (int) $td->getAttribute('colspan')),
                    'turun' => max(1, (int) $td->getAttribute('rowspan')),
                    'lanjutan' => false,
                ];
                $baris[] = $sel;
                if ($sel['turun'] > 1) {
                    $baru[$kolom] = [$sel, $sel['turun'] - 1];
                }
                $kolom += $sel['rentang'];
            }
            $lanjut();

            $gantung += $baru;
            $hasil[] = $baris;
        }

        return $hasil;
    }

    /** Apakah teks tersimpan berupa HTML (dipakai untuk riwayat dan matriks yang menerima teks apa saja). */
    public static function tampakHtml(?string $teks): bool
    {
        return (bool) preg_match('/^\s*<(p|table|figure|ol|ul|h[2-4]|div|blockquote)\b/i', (string) $teks);
    }

    /**
     * HTML untuk ditampilkan dan dicetak. Lebar kolom dari editor (colwidth, piksel) dijadikan persen dari lebar isi
     * kertas ($lebarIsi, lihat FormatDokumen::lebarIsiPx), supaya sama di layar, PDF, dan Word; paragraf kosong diberi
     * spasi supaya jaraknya tidak hilang.
     */
    public static function untukTampil(?string $html, float $lebarIsi = 627): string
    {
        if (blank($html)) {
            return '';
        }

        $dom = self::dom($html);
        $akar = $dom->getElementById('akar');

        foreach (iterator_to_array($akar->getElementsByTagName('table')) as $tabel) {
            $letak = preg_match('/margin-left:\s*auto/', $gaya = $tabel->getAttribute('style'))
                ? (preg_match('/margin-right:\s*auto/', $gaya) ? 'center' : 'right') : null;
            self::lebarPersen($tabel, $lebarIsi);
            self::letakTabel($tabel, $letak);
            self::kepalaTabel($tabel);
            self::tinggiBaris($tabel);
        }
        foreach (iterator_to_array($akar->getElementsByTagName('p')) as $p) {
            if (! $p->hasChildNodes()) {
                $p->appendChild($dom->createTextNode("\u{00A0}"));
            }
        }

        $hasil = '';
        foreach ($akar->childNodes as $anak) {
            $hasil .= $dom->saveHTML($anak);
        }

        return $hasil;
    }

    private static function lebarPersen(DOMElement $tabel, float $lebarIsi): void
    {
        $kisi = self::kisi($tabel);
        $lebar = [];
        $kolomMaks = 0;

        foreach ($kisi as $baris) {
            foreach ($baris as $sel) {
                $kolomMaks = max($kolomMaks, $sel['kolom'] + $sel['rentang']);

                foreach ($sel['el']->getAttribute('colwidth') === '' ? [] : explode(',', $sel['el']->getAttribute('colwidth')) as $i => $px) {
                    if ((int) $px > 0 && $i < $sel['rentang']) {
                        $lebar[$sel['kolom'] + $i] ??= (int) $px;
                    }
                }
            }
        }

        if (! $lebar) {
            return;
        }

        // Kolom yang belum diatur berbagi sisa lebar kertas, paling sempit 60 px. Tabel yang semua kolomnya diatur
        // selebar jumlahnya, paling lebar selebar isi kertas.
        $belum = $kolomMaks - count($lebar);
        $sisa = $belum > 0 ? max(60, ($lebarIsi - array_sum($lebar)) / $belum) : 0;
        $total = array_sum($lebar) + $belum * $sisa;

        foreach (array_merge(...$kisi) as $sel) {
            if ($sel['lanjutan']) {
                continue;
            }

            $px = 0;
            for ($i = $sel['kolom']; $i < $sel['kolom'] + $sel['rentang']; $i++) {
                $px += $lebar[$i] ?? $sisa;
            }
            $gaya = preg_replace('/(^|;)\s*width:[^;]*/', '', $sel['el']->getAttribute('style'));
            $sel['el']->setAttribute('style', trim($gaya . ';width:' . round($px / $total * 100, 2) . '%', ';'));
        }

        foreach (iterator_to_array($tabel->childNodes) as $anak) {
            if ($anak instanceof DOMElement && $anak->tagName === 'colgroup') {
                $tabel->removeChild($anak);
            }
        }
        $tabel->setAttribute('style', 'width:' . min(100, round($total / $lebarIsi * 100, 2)) . '%');
    }

    /** Letak tabel (tengah, kanan) sebagai jarak kiri: dompdf tidak mengenal margin auto pada tabel. */
    private static function letakTabel(DOMElement $tabel, ?string $letak): void
    {
        if ($letak === null) {
            return;
        }

        $lebar = preg_match('/width:\s*([\d.]+)%/', $tabel->getAttribute('style'), $m) ? (float) $m[1] : 100.0;
        $sisa = (100 - $lebar) / ($letak === 'center' ? 2 : 1);
        $tabel->setAttribute('style', "width:{$lebar}%" . ($sisa > 0 ? ';margin-left:' . round($sisa, 2) . '%' : ''));
    }

    /** Baris judul (seluruh selnya th) di awal tabel menjadi thead, supaya diulang di tiap halaman. */
    private static function kepalaTabel(DOMElement $tabel): void
    {
        $badan = null;
        foreach ($tabel->childNodes as $anak) {
            if ($anak instanceof DOMElement && $anak->tagName === 'thead') {
                return;
            }
            $badan ??= $anak instanceof DOMElement && $anak->tagName === 'tbody' ? $anak : null;
        }
        if (! $badan) {
            return;
        }

        $baris = array_values(array_filter(iterator_to_array($badan->childNodes), fn ($n) => $n instanceof DOMElement && $n->tagName === 'tr'));
        $kisi = self::kisi($tabel);
        $judul = 0;
        while (isset($kisi[$judul]) && $kisi[$judul] !== [] && self::semua($kisi[$judul], fn (array $sel) => $sel['el']->tagName === 'th')) {
            $judul++;
        }
        // Sel judul yang digabung sampai ke baris isi tidak bisa dipisah.
        if ($judul === 0 || $judul >= count($baris) || in_array(true, array_column($kisi[$judul], 'lanjutan'), true)) {
            return;
        }

        $kepala = $tabel->ownerDocument->createElement('thead');
        $tabel->insertBefore($kepala, $badan);
        foreach (array_slice($baris, 0, $judul) as $tr) {
            $kepala->appendChild($tr);
        }
    }

    private static function semua(array $isi, callable $uji): bool
    {
        return count(array_filter($isi, $uji)) === count($isi);
    }

    /** Tinggi baris ditulis juga di selnya, karena dompdf hanya membaca tinggi sel (di luar jarak tepinya, 4 pt). */
    private static function tinggiBaris(DOMElement $tabel): void
    {
        foreach ($tabel->getElementsByTagName('tr') as $tr) {
            if (! preg_match('/height:\s*([\d.]+)pt/', $tr->getAttribute('style'), $m)) {
                continue;
            }
            foreach ($tr->childNodes as $sel) {
                if ($sel instanceof DOMElement && (int) $sel->getAttribute('rowspan') < 2) {
                    $sel->setAttribute('style', trim($sel->getAttribute('style') . ';height:' . max(1, (float) $m[1] - 4) . 'pt', ';'));
                }
            }
        }
    }

    /** Tinggi baris dalam pt, atau null bila mengikuti isinya. */
    public static function tinggiDari(DOMElement $tr): ?float
    {
        return preg_match('/height:\s*([\d.]+)pt/', $tr->getAttribute('style'), $m) ? (float) $m[1] : null;
    }

    /**
     * Garis tiap sisi sel menurut gayanya: [jenis, tebal pt]. Tanpa gaya berarti garis tunggal ½ pt.
     *
     * @return array{top: array{0: string, 1: float}, right: array{0: string, 1: float}, bottom: array{0: string, 1: float}, left: array{0: string, 1: float}}
     */
    public static function garisSel(string $gaya): array
    {
        $sisi = ['top', 'right', 'bottom', 'left'];
        $garis = array_fill_keys($sisi, ['solid', 0.5]);
        $tebal = fn (string $n) => preg_match('/^([\d.]+)(px|pt)$/', $n, $m) ? round((float) $m[1] * ($m[2] === 'px' ? 0.75 : 1), 2) : null;
        $jenis = fn (string $n) => in_array($n, ['none', 'solid', 'dashed', 'dotted', 'double'], true) ? $n : null;
        // Nilai 1 sampai 4 bagian, urutannya atas, kanan, bawah, kiri.
        $empat = fn (array $n) => [$n[0], $n[1] ?? $n[0], $n[2] ?? $n[0], $n[3] ?? $n[1] ?? $n[0]];

        foreach (explode(';', strtolower($gaya)) as $deklarasi) {
            [$properti, $nilai] = array_map('trim', array_pad(explode(':', $deklarasi, 2), 2, ''));
            $bagian = preg_split('/\s+/', $nilai, -1, PREG_SPLIT_NO_EMPTY);

            if (! $bagian) {
                continue;
            }
            if ($properti === 'border-style' || $properti === 'border-width') {
                foreach ($empat($bagian) as $i => $n) {
                    $properti === 'border-style' ? $garis[$sisi[$i]][0] = $jenis($n) ?? $garis[$sisi[$i]][0] : $garis[$sisi[$i]][1] = $tebal($n) ?? $garis[$sisi[$i]][1];
                }
            } elseif ($properti === 'border' || preg_match('/^border-(top|right|bottom|left)$/', $properti, $m)) {
                foreach ($properti === 'border' ? $sisi : [$m[1]] as $s) {
                    $garis[$s] = [$nilai === '0' ? 'none' : 'solid', 0.5];
                    foreach ($bagian as $n) {
                        $garis[$s] = [$jenis($n) ?? $garis[$s][0], $tebal($n) ?? $garis[$s][1]];
                    }
                }
            }
        }

        return $garis;
    }

    /**
     * Membuang kepala lampiran ("LAMPIRAN I, PERATURAN BUPATI ..., TENTANG ...") dan tanda tangan Bupati dari isi yang
     * dibaca dari file, karena keduanya dibuat otomatis. $tentang: judul naskah, batas akhir kepalanya.
     */
    public static function tanpaKepalaTtd(?string $html, string $tentang): string
    {
        if (blank($html)) {
            return '';
        }

        $dom = self::dom($html);
        $akar = $dom->getElementById('akar');
        $padat = fn (string $t) => preg_replace('/[^A-Z0-9]+/', '', mb_strtoupper($t));
        $blok = array_values(array_filter(iterator_to_array($akar->childNodes), fn ($n) => $n instanceof DOMElement));
        $isi = array_map(fn (DOMElement $el) => $padat($el->textContent), $blok);

        $buang = self::akhirKepala($blok, $isi, $padat($tentang));
        while ($buang < count($blok) - 1 && $isi[$buang] === '' && $blok[$buang]->tagName === 'p') {
            $buang++;
        }
        for ($i = 0; $i < $buang; $i++) {
            $akar->removeChild($blok[$i]);
        }
        for ($i = self::awalTtd($isi, $buang); $i < count($blok); $i++) {
            $akar->removeChild($blok[$i]);
        }

        $hasil = '';
        foreach ($akar->childNodes as $anak) {
            $hasil .= $dom->saveHTML($anak);
        }

        return $hasil;
    }

    /** Banyaknya blok di awal yang merupakan kepala lampiran; 0 bila tidak ada. */
    private static function akhirKepala(array $blok, array $isi, string $tentang): int
    {
        $awal = 0;
        while (($isi[$awal] ?? null) === '') {
            $awal++;
        }
        if (! str_starts_with($isi[$awal] ?? '', 'LAMPIRAN') || mb_strlen($isi[$awal]) > 900) {
            return 0;
        }

        $gabung = '';
        $m = [];
        for ($i = $awal; $i < min(count($isi), $awal + 14); $i++) {
            $gabung .= $isi[$i];
            if (preg_match('/(?:BUPATI|NOMOR|TANGGAL).*?TENTANG(.*)$/', $gabung, $m)) {
                break;
            }
        }
        if (! isset($m[1])) {
            // Hanya tulisan "LAMPIRAN" atau "LAMPIRAN II" tanpa keterangan peraturannya.
            return preg_match('/^LAMPIRAN([IVXLC]+|\d+)?$/', $isi[$awal]) ? $awal + 1 : 0;
        }

        // Judul naskah bisa terpecah beberapa baris; diikuti selama masih awalan dari judulnya.
        $sisa = $m[1];
        $cocok = $i + 1;
        for ($j = $i; $j < count($isi); $j++) {
            $sisa .= $j > $i ? $isi[$j] : '';
            if ($sisa === $tentang) {
                return $j + 1;
            }
            if (! str_starts_with($tentang, $sisa)) {
                break;
            }
        }

        // Judulnya berbeda dari judul naskah: baris kepala dikenali dari letaknya di sisi kanan kertas.
        $jorok = fn (DOMElement $el, string $sifat) => preg_match("/{$sifat}:\s*(-?[\d.]+)pt/", $el->getAttribute('style'), $k) ? (float) $k[1] : 0.0;
        while (isset($blok[$cocok]) && $isi[$cocok] !== '' && $jorok($blok[$cocok], 'margin-left') + $jorok($blok[$cocok], 'text-indent') >= 120) {
            $cocok++;
        }

        return $cocok;
    }

    /** Letak blok pertama tanda tangan di akhir; sama dengan banyaknya blok bila tidak ada. */
    private static function awalTtd(array $isi, int $mulai): int
    {
        $n = count($isi);
        $akhir = $n;
        while ($akhir > $mulai && $isi[$akhir - 1] === '') {
            $akhir--;
        }

        for ($i = max($mulai, $akhir - 14); $i < $n; $i++) {
            if (! preg_match('/^(PJ|PLT|PLH|PENJABAT|PELAKSANATUGAS|PELAKSANAHARIAN)?BUPATIHULUSUNGAITENGAH/', $isi[$i]) || mb_strlen(implode('', array_slice($isi, $i))) > 160) {
                continue;
            }
            while ($i > $mulai && preg_match('/^(DITETAPKANDI|PADATANGGAL|$)/', $isi[$i - 1])) {
                $i--;
            }

            return $i;
        }

        return $n;
    }

    public static function dom(string $html): DOMDocument
    {
        $dom = new DOMDocument();
        $sebelumnya = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="akar">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($sebelumnya);

        return $dom;
    }

    private static function bersihkanAnak(DOMNode $induk): void
    {
        foreach (iterator_to_array($induk->childNodes) as $anak) {
            if ($anak->nodeType === XML_COMMENT_NODE || $anak->nodeType === XML_PI_NODE) {
                $induk->removeChild($anak);

                continue;
            }
            if (! $anak instanceof DOMElement) {
                // Spasi antartag di tempat yang hanya berisi blok bukan isi; editor akan membacanya sebagai paragraf.
                if ($anak->nodeType === XML_TEXT_NODE && trim($anak->nodeValue) === '' && in_array(strtolower($induk->nodeName), self::WADAH, true)) {
                    $induk->removeChild($anak);
                }

                continue;
            }

            $tag = strtolower($anak->tagName);

            if (in_array($tag, self::BUANG, true)) {
                $induk->removeChild($anak);

                continue;
            }

            self::bersihkanAnak($anak);

            if (! in_array($tag, self::TAG, true)) {
                // figure, div, a, font, dan sejenisnya: isinya dipertahankan.
                while ($anak->firstChild) {
                    $induk->insertBefore($anak->firstChild, $anak);
                }
                $induk->removeChild($anak);

                continue;
            }

            foreach (iterator_to_array($anak->attributes) as $atribut) {
                $nama = strtolower($atribut->name);

                if (! in_array($nama, self::ATRIBUT, true)) {
                    $anak->removeAttribute($atribut->name);
                } elseif ($nama === 'style') {
                    $gaya = self::gaya($atribut->value);
                    $gaya === '' ? $anak->removeAttribute('style') : $anak->setAttribute('style', $gaya);
                } elseif ($nama === 'colwidth') {
                    if (! preg_match('/^\d{1,4}(,\d{1,4}){0,49}$/', $atribut->value)) {
                        $anak->removeAttribute('colwidth');
                    }
                } elseif (! ctype_digit(trim($atribut->value)) || (int) $atribut->value < 1 || (int) $atribut->value > 100) {
                    $anak->removeAttribute($atribut->name);
                }
            }

            // Pembungkus tanpa gaya tidak ada gunanya.
            if ($tag === 'span' && $anak->attributes->length === 0) {
                while ($anak->firstChild) {
                    $induk->insertBefore($anak->firstChild, $anak);
                }
                $induk->removeChild($anak);
            }
        }
    }

    private static function gaya(string $gaya): string
    {
        $hasil = [];

        foreach (explode(';', $gaya) as $deklarasi) {
            [$properti, $nilai] = array_pad(explode(':', $deklarasi, 2), 2, '');
            $properti = strtolower(trim($properti));
            $nilai = strtolower(trim(preg_replace('/\s+/', ' ', $nilai)));
            $nilai = preg_replace('/\s*!important$/', '', $nilai);

            if (isset(self::GAYA[$properti]) && $nilai !== '' && ! preg_match('/url|expression|\\\\|javascript|@/', $nilai) && preg_match(self::GAYA[$properti], $nilai)) {
                $hasil[] = "{$properti}:{$nilai}";
            }
        }

        return implode(';', $hasil);
    }
}
