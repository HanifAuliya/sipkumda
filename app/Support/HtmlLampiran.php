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
        'margin-left' => '/^\d+(\.\d+)?(px|pt|cm|mm|em)$/',
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

        $hasil = '';
        foreach ($akar->childNodes as $anak) {
            $hasil .= $dom->saveHTML($anak);
        }
        // Editor menyisakan paragraf kosong di bawah tabel supaya kursor bisa keluar; tidak ikut disimpan.
        $hasil = trim(preg_replace('/(?:<p[^>]*>(?:\s|\x{00A0}|&nbsp;|<br>)*<\/p>\s*)+$/u', '', $hasil));

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
            self::lebarPersen($tabel, $lebarIsi);
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
