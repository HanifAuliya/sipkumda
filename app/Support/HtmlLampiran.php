<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Isi lampiran dari editor seperti Word (CKEditor). Sebelum disimpan, HTML-nya dibersihkan dengan daftar putih:
 * hanya tag teks, daftar, dan tabel, atribut colspan/rowspan/start, dan sebagian gaya (rata, lebar, garis,
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

    private const ATRIBUT = ['colspan', 'rowspan', 'start', 'style'];

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

        $dom = new DOMDocument();
        $sebelumnya = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="akar">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($sebelumnya);

        $akar = $dom->getElementById('akar');
        if (! $akar) {
            return null;
        }

        self::bersihkanAnak($akar);

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
     * Teks polos dari HTML: tiap paragraf dan butir daftar satu baris; baris tabel menjadi "| a | b |", bentuk
     * tabel yang dikenali TataLetakNaskah, sehingga matriks tetap mencetaknya sebagai tabel.
     */
    public static function teks(?string $html): string
    {
        $teks = preg_replace(
            ['/<tr[^>]*>/i', '/<\/(td|th)>/i', '/<(br|\/p|\/li|\/tr|\/h[2-4]|\/blockquote)[^>]*>/i'],
            ["\n|", ' |', "\n"],
            (string) $html,
        );
        $teks = html_entity_decode(strip_tags($teks), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $teks = preg_replace(['/\x{00A0}/u', '/[ \t]+/u', '/ *\n */u', '/\n{2,}/u'], [' ', ' ', "\n", "\n"], $teks);

        return trim($teks);
    }

    /** Apakah teks tersimpan berupa HTML (dipakai untuk riwayat dan matriks yang menerima teks apa saja). */
    public static function tampakHtml(?string $teks): bool
    {
        return (bool) preg_match('/^\s*<(p|table|figure|ol|ul|h[2-4]|div|blockquote)\b/i', (string) $teks);
    }

    private static function bersihkanAnak(DOMNode $induk): void
    {
        foreach (iterator_to_array($induk->childNodes) as $anak) {
            if ($anak->nodeType === XML_COMMENT_NODE || $anak->nodeType === XML_PI_NODE) {
                $induk->removeChild($anak);

                continue;
            }
            if (! $anak instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($anak->tagName);

            if (in_array($tag, self::BUANG, true)) {
                $induk->removeChild($anak);

                continue;
            }

            self::bersihkanAnak($anak);

            if (! in_array($tag, self::TAG, true)) {
                // figure (pembungkus tabel CKEditor), div, a, font, dan sejenisnya: isinya dipertahankan.
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
                } elseif (! ctype_digit(trim($atribut->value)) || (int) $atribut->value < 1 || (int) $atribut->value > 100) {
                    $anak->removeAttribute($atribut->name);
                }
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
