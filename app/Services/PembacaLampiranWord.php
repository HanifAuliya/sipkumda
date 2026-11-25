<?php

namespace App\Services;

use App\Support\HtmlLampiran;
use App\Support\PenomoranWord;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;
use ZipArchive;

/**
 * Isi dokumen Word (.docx) menjadi HTML editor lampiran: paragraf dengan rata dan jorok, huruf tebal/miring/garis bawah
 * dan ukurannya, daftar bernomor, dan tabel lengkap dengan sel gabungan, lebar kolom, tinggi baris, garis, dan arsiran.
 * Yang tidak dikenal editor (gambar, kotak teks, warna huruf) dilewati; jumlah gambarnya dilaporkan.
 */
class PembacaLampiranWord
{
    private const W = PenomoranWord::W;

    /** Melindungi server dari file kecil yang mengembang sangat besar saat dibuka. */
    private const BATAS_XML = 30 * 1024 * 1024;

    private const TWIP_PER_PX = 15;

    private DOMXPath $xpath;
    private ?DOMXPath $gayaXpath = null;
    private ?PenomoranWord $penomoran = null;
    /** @var array<string, array{induk: ?string, p: array, r: array}> */
    private array $gaya = [];
    private array $bawaan = ['p' => [], 'r' => []];
    private float $lebarIsi = 627;
    /** Ukuran huruf (pt) terbanyak di luar tabel: dianggap ukuran naskah, jadi tidak ditulis. */
    private ?float $ukuranPokok = null;

    public int $gambar = 0;

    /** Seluruh isi dokumen. $lebarIsi: lebar isi kertas dalam px, batas lebar tabel. */
    public function baca(string $path, float $lebarIsi): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File bukan dokumen Word (.docx) yang valid.');
        }

        try {
            $utama = self::ambilXml($zip, 'word/document.xml') ?? throw new RuntimeException('Isi dokumen Word tidak ditemukan.');
            $gaya = self::ambilXml($zip, 'word/styles.xml');
            $penomoran = new PenomoranWord(self::ambilXml($zip, 'word/numbering.xml'), $gaya);
        } finally {
            $zip->close();
        }

        $xpath = new DOMXPath($utama);
        $xpath->registerNamespace('w', self::W);
        $this->siapkan($xpath, $gaya, $penomoran, $lebarIsi);

        return $this->html(iterator_to_array($xpath->query('/w:document/w:body/*')));
    }

    /** Dipakai juga oleh PembacaNaskahWord untuk lampiran di dalam naskah. */
    public function siapkan(DOMXPath $xpath, ?DOMDocument $gaya, PenomoranWord $penomoran, float $lebarIsi): void
    {
        $this->xpath = $xpath;
        $this->penomoran = $penomoran;
        $this->lebarIsi = $lebarIsi;
        $this->gambar = 0;
        $this->muatGaya($gaya);
    }

    public static function ambilXml(ZipArchive $zip, string $nama): ?DOMDocument
    {
        $info = $zip->statName($nama);
        if ($info === false) {
            return null;
        }
        if ($info['size'] > self::BATAS_XML) {
            throw new RuntimeException('Dokumen terlalu besar untuk dibaca.');
        }

        $dom = new DOMDocument();
        if (! @$dom->loadXML((string) $zip->getFromName($nama), LIBXML_NONET | LIBXML_COMPACT)) {
            throw new RuntimeException('Dokumen Word rusak dan tidak bisa dibaca.');
        }

        return $dom;
    }

    /** @param  list<DOMNode>  $nodes paragraf dan tabel tingkat teratas */
    public function html(array $nodes): string
    {
        $this->ukuranPokok = $this->ukuranTerbanyak($nodes);

        return (string) HtmlLampiran::bersihkan($this->blok($nodes, false, $this->lebarIsi));
    }

    /* Blok: paragraf, daftar, tabel */

    private function blok(array $nodes, bool $diTabel, float $lebar): string
    {
        $html = '';
        // Daftar yang sedang terbuka, dari tingkat terluar: [tag, fmt, level].
        $daftar = [];
        $tutup = function (int $sampai = 0) use (&$daftar, &$html) {
            while (count($daftar) > $sampai) {
                $html .= '</li></' . array_pop($daftar)['tag'] . '>';
            }
        };

        foreach ($this->rata($nodes) as $node) {
            if ($node->localName === 'tbl') {
                $tutup();
                $html .= $this->tabel($node, $lebar);

                continue;
            }
            if ($node->localName !== 'p') {
                continue;
            }

            $isi = $this->isiParagraf($node, $diTabel);
            $kosong = trim(strip_tags($isi), " \t\n\u{00A0}") === '';
            // Paragraf kosong tidak memakai nomor, walaupun gayanya bernomor.
            $nomor = $kosong ? null : $this->penomoran?->nomor($node, $this->xpath);

            if ($nomor && $nomor['polos']) {
                while ($daftar && end($daftar)['level'] > $nomor['level']) {
                    $tutup(count($daftar) - 1);
                }
                $atas = end($daftar) ?: null;
                $tag = $nomor['fmt'] === 'bullet' ? 'ul' : 'ol';

                if ($atas && $atas['level'] === $nomor['level'] && $atas['fmt'] !== $nomor['fmt']) {
                    $tutup(count($daftar) - 1);
                    $atas = end($daftar) ?: null;
                }
                if ($atas && $atas['level'] === $nomor['level']) {
                    $html .= '</li><li>';
                } else {
                    $daftar[] = ['tag' => $tag, 'fmt' => $nomor['fmt'], 'level' => $nomor['level']];
                    $html .= $this->bukaDaftar($tag, $nomor) . '<li>';
                }
                $html .= '<p' . $this->gayaParagraf($node, $diTabel, null, true) . ">{$isi}</p>";

                continue;
            }

            $tutup();
            $awalan = $nomor ? e($nomor['teks']) . ' ' : '';
            $html .= '<p' . $this->gayaParagraf($node, $diTabel, $nomor, false) . ">{$awalan}{$isi}</p>";
        }

        $tutup();

        return $html;
    }

    private function bukaDaftar(string $tag, array $nomor): string
    {
        $jenis = ['lowerLetter' => 'lower-alpha', 'upperLetter' => 'upper-alpha', 'lowerRoman' => 'lower-roman', 'upperRoman' => 'upper-roman'][$nomor['fmt']] ?? null;

        return "<{$tag}" . ($tag === 'ol' && $nomor['nilai'] > 1 && $nomor['nilai'] <= 100 ? " start=\"{$nomor['nilai']}\"" : '')
            . ($jenis ? " style=\"list-style-type:{$jenis}\"" : '') . '>';
    }

    /** Pembungkus (kontrol isi, sisipan terlacak) dibuka; isinya diperlakukan seperti biasa. */
    private function rata(iterable $nodes): array
    {
        $hasil = [];
        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            if (in_array($node->localName, ['sdt', 'sdtContent', 'ins', 'smartTag', 'customXml', 'hyperlink', 'moveTo'], true)) {
                array_push($hasil, ...$this->rata($node->childNodes));
            } else {
                $hasil[] = $node;
            }
        }

        return $hasil;
    }

    private function gayaParagraf(DOMElement $p, bool $diTabel, ?array $nomor, bool $diDaftar): string
    {
        $sifat = $this->sifatParagraf($p);
        $gaya = [];

        $rata = ['center' => 'center', 'right' => 'right', 'end' => 'right', 'both' => 'justify', 'distribute' => 'justify', 'left' => 'left', 'start' => 'left'][$sifat['jc'] ?? ''] ?? null;
        // Kiri adalah bawaan, kecuali di sel judul yang bawaannya tengah.
        if ($rata && ($rata !== 'left' || $diTabel)) {
            $gaya[] = "text-align:{$rata}";
        }
        if (! $diDaftar) {
            $kiri = (int) ($sifat['kiri'] ?? $nomor['kiri'] ?? 0);
            $awal = (int) ($sifat['awal'] ?? 0);
            if ($kiri >= 60) {
                $gaya[] = 'margin-left:' . min(360, (int) round($kiri / 20)) . 'pt';
            }
            if (abs($awal) >= 60 && ! ($awal < 0 && $kiri < -$awal)) {
                $gaya[] = 'text-indent:' . max(-200, min(200, (int) round($awal / 20))) . 'pt';
            }
        }

        return $gaya ? ' style="' . implode(';', $gaya) . '"' : '';
    }

    /* Huruf */

    private function isiParagraf(DOMElement $p, bool $diTabel): string
    {
        $dasar = $this->sifatHurufGaya($this->xpath->query('./w:pPr/w:pStyle/@w:val', $p)->item(0)?->value);
        $potongan = []; // [html pembuka, html penutup, teks]
        $this->kumpulkan($p, $dasar, $diTabel, $potongan);

        $hasil = '';
        for ($i = 0; $i < count($potongan); $i++) {
            [$buka, $tutup, $teks] = $potongan[$i];
            // Run Word sering terpotong di tengah kata: yang gayanya sama disatukan.
            while (isset($potongan[$i + 1]) && $potongan[$i + 1][0] === $buka) {
                $teks .= $potongan[++$i][2];
            }
            $hasil .= $teks === '' ? '' : "{$buka}{$teks}{$tutup}";
        }

        return $hasil;
    }

    private function kumpulkan(DOMElement $wadah, array $dasar, bool $diTabel, array &$potongan): void
    {
        foreach ($this->rata($wadah->childNodes) as $node) {
            if ($node->localName === 'fldSimple') {
                $this->kumpulkan($node, $dasar, $diTabel, $potongan);
            }
            if ($node->localName !== 'r') {
                continue;
            }

            $sifat = array_replace($dasar, $this->sifatHuruf($this->xpath->query('./w:rPr', $node)->item(0)));
            if (! empty($sifat['sembunyi'])) {
                continue;
            }

            $teks = '';
            foreach ($node->childNodes as $anak) {
                $teks .= match ($anak->localName) {
                    't' => e(! empty($sifat['kapital']) ? mb_strtoupper($anak->textContent) : $anak->textContent),
                    'tab' => str_repeat("\u{00A0}", 4),
                    'br' => in_array($anak->getAttributeNS(self::W, 'type'), ['page', 'column'], true) ? '' : '<br>',
                    'cr' => '<br>',
                    'noBreakHyphen' => '-',
                    default => '',
                };
                if (in_array($anak->localName, ['drawing', 'pict', 'object', 'AlternateContent'], true)) {
                    $this->gambar++;
                }
            }
            // Kalimat biasa tidak perlu spasi ganda hasil perataan; tab di atas memakai spasi tak-putus.
            $potongan[] = [...$this->tagHuruf($sifat, $diTabel), $teks];
        }
    }

    /** @return array{0: string, 1: string} tag pembuka dan penutup */
    private function tagHuruf(array $sifat, bool $diTabel): array
    {
        $tag = array_keys(array_filter([
            'strong' => ! empty($sifat['tebal']), 'em' => ! empty($sifat['miring']), 'u' => ! empty($sifat['garis']), 's' => ! empty($sifat['coret']),
            'sup' => ($sifat['letak'] ?? '') === 'superscript', 'sub' => ($sifat['letak'] ?? '') === 'subscript',
        ]));
        $buka = implode('', array_map(fn ($t) => "<{$t}>", $tag));
        $tutup = implode('', array_map(fn ($t) => "</{$t}>", array_reverse($tag)));

        $ukuran = $this->ukuran($sifat);
        if ($ukuran && $ukuran != 12 && ($diTabel || $ukuran != $this->ukuranPokok)) {
            $buka = "<span style=\"font-size:{$ukuran}pt\">{$buka}";
            $tutup .= '</span>';
        }

        return [$buka, $tutup];
    }

    private function ukuran(array $sifat): ?float
    {
        return isset($sifat['ukuran']) ? $sifat['ukuran'] / 2 : null;
    }

    private function ukuranTerbanyak(array $nodes): ?float
    {
        $hitung = [];
        foreach ($this->rata($nodes) as $node) {
            if ($node->localName !== 'p') {
                continue;
            }
            $dasar = $this->sifatHurufGaya($this->xpath->query('./w:pPr/w:pStyle/@w:val', $node)->item(0)?->value);
            foreach ($this->xpath->query('./w:r', $node) as $r) {
                $ukuran = $this->ukuran(array_replace($dasar, $this->sifatHuruf($this->xpath->query('./w:rPr', $r)->item(0))));
                $hitung[(string) $ukuran] = ($hitung[(string) $ukuran] ?? 0) + mb_strlen($r->textContent);
            }
        }
        arsort($hitung);
        $pokok = array_key_first($hitung);

        return $pokok === null || $pokok === '' ? null : (float) $pokok;
    }

    /* Gaya */

    private function muatGaya(?DOMDocument $dom): void
    {
        $this->gaya = [];
        $this->bawaan = ['p' => [], 'r' => []];
        $this->gayaXpath = null;

        if (! $dom) {
            return;
        }

        $xp = $this->gayaXpath = new DOMXPath($dom);
        $xp->registerNamespace('w', self::W);
        $this->bawaan = [
            'p' => $this->bacaSifatParagraf($xp->query('/w:styles/w:docDefaults/w:pPrDefault/w:pPr')->item(0), $xp),
            'r' => $this->bacaSifatHuruf($xp->query('/w:styles/w:docDefaults/w:rPrDefault/w:rPr')->item(0), $xp),
        ];

        foreach ($xp->query('/w:styles/w:style') as $node) {
            $id = $node->getAttributeNS(self::W, 'styleId');
            $this->gaya[$id] = [
                'induk' => $xp->query('./w:basedOn/@w:val', $node)->item(0)?->value,
                'p' => $this->bacaSifatParagraf($xp->query('./w:pPr', $node)->item(0), $xp),
                'r' => $this->bacaSifatHuruf($xp->query('./w:rPr', $node)->item(0), $xp),
                'node' => $node,
            ];
            if ($node->getAttributeNS(self::W, 'default') === '1' && $node->getAttributeNS(self::W, 'type') === 'paragraph') {
                $this->gaya[''] = $this->gaya[$id];
            }
        }
    }

    /** Gaya beserta induknya, dari yang terdalam ke yang paling khusus. */
    private function rantai(?string $id): array
    {
        $rantai = [];
        for ($kini = isset($this->gaya[$id ?? '']) ? $id ?? '' : '', $i = 0; isset($this->gaya[$kini]) && $i < 12; $i++) {
            array_unshift($rantai, $this->gaya[$kini]);
            $kini = $this->gaya[$kini]['induk'];
            if ($kini === null) {
                break;
            }
        }

        return $rantai;
    }

    private function sifatHurufGaya(?string $id): array
    {
        return array_replace($this->bawaan['r'], ...array_column($this->rantai($id), 'r'));
    }

    private function sifatParagraf(DOMElement $p): array
    {
        $id = $this->xpath->query('./w:pPr/w:pStyle/@w:val', $p)->item(0)?->value;

        return array_replace($this->bawaan['p'], ...[...array_column($this->rantai($id), 'p'), $this->bacaSifatParagraf($this->xpath->query('./w:pPr', $p)->item(0), $this->xpath)]);
    }

    private function sifatHuruf(?DOMElement $rPr): array
    {
        $gaya = $rPr ? $this->xpath->query('./w:rStyle/@w:val', $rPr)->item(0)?->value : null;

        return array_replace([], ...[...($gaya && isset($this->gaya[$gaya]) ? array_column($this->rantai($gaya), 'r') : []), $this->bacaSifatHuruf($rPr, $this->xpath)]);
    }

    private function bacaSifatParagraf(?DOMElement $pPr, DOMXPath $xp): array
    {
        if (! $pPr) {
            return [];
        }

        $nilai = fn (string $jalur) => $xp->query($jalur, $pPr)->item(0)?->value;
        $awal = $nilai('./w:ind/@w:firstLine');
        $gantung = $nilai('./w:ind/@w:hanging');

        return array_filter([
            'jc' => $nilai('./w:jc/@w:val'),
            'kiri' => $nilai('./w:ind/@w:left | ./w:ind/@w:start'),
            'awal' => $gantung !== null ? -(int) $gantung : ($awal === null ? null : (int) $awal),
        ], fn ($v) => $v !== null);
    }

    private function bacaSifatHuruf(?DOMElement $rPr, DOMXPath $xp): array
    {
        if (! $rPr) {
            return [];
        }

        $nyala = function (string $tag) use ($rPr, $xp) {
            $el = $xp->query("./w:{$tag}", $rPr)->item(0);

            return $el ? ! in_array($el->getAttributeNS(self::W, 'val'), ['0', 'false', 'off', 'none'], true) : null;
        };
        $ukuran = $xp->query('./w:sz/@w:val', $rPr)->item(0)?->value;

        return array_filter([
            'tebal' => $nyala('b'), 'miring' => $nyala('i'), 'garis' => $nyala('u'), 'coret' => $nyala('strike'),
            'kapital' => $nyala('caps'), 'sembunyi' => $nyala('vanish'),
            'letak' => $xp->query('./w:vertAlign/@w:val', $rPr)->item(0)?->value,
            'ukuran' => $ukuran === null ? null : (int) $ukuran,
        ], fn ($v) => $v !== null);
    }

    /* Tabel */

    private function tabel(DOMElement $tbl, float $lebarMaks): string
    {
        $q = fn (string $jalur, DOMNode $di) => $this->xpath->query($jalur, $di);
        $baris = array_values(array_filter($this->rata($tbl->childNodes), fn (DOMElement $n) => $n->localName === 'tr'));
        $sel = []; // sel[baris][] = ['kolom', 'rentang', 'turun', 'el', 'garis', ...]
        $bawahan = []; // kolom => [baris, indeks] sel vMerge yang masih terbuka

        foreach ($baris as $b => $tr) {
            $sel[$b] = [];
            $kolom = 0;
            if ($lewat = (int) ($q('./w:trPr/w:gridBefore/@w:val', $tr)->item(0)?->value ?? 0)) {
                $sel[$b][] = ['kolom' => 0, 'rentang' => $lewat, 'turun' => 1, 'el' => null];
                $kolom = $lewat;
            }

            foreach ($this->rata($tr->childNodes) as $tc) {
                if ($tc->localName !== 'tc') {
                    continue;
                }
                $rentang = max(1, (int) ($q('./w:tcPr/w:gridSpan/@w:val', $tc)->item(0)?->value ?? 1));
                $gabung = $q('./w:tcPr/w:vMerge', $tc)->item(0);

                if ($gabung && $gabung->getAttributeNS(self::W, 'val') !== 'restart' && isset($bawahan[$kolom])) {
                    [$bi, $si] = $bawahan[$kolom];
                    $sel[$bi][$si]['turun']++;
                    $sel[$bi][$si]['akhir'] = $tc;
                } else {
                    $sel[$b][] = ['kolom' => $kolom, 'rentang' => $rentang, 'turun' => 1, 'el' => $tc, 'akhir' => $tc];
                    unset($bawahan[$kolom]);
                    if ($gabung) {
                        $bawahan[$kolom] = [$b, array_key_last($sel[$b])];
                    }
                }
                $kolom += $rentang;
            }

            if ($sisa = (int) ($q('./w:trPr/w:gridAfter/@w:val', $tr)->item(0)?->value ?? 0)) {
                $sel[$b][] = ['kolom' => $kolom, 'rentang' => $sisa, 'turun' => 1, 'el' => null];
            }
        }

        $jumlahKolom = 0;
        foreach (array_merge(...$sel ?: [[]]) as $s) {
            $jumlahKolom = max($jumlahKolom, $s['kolom'] + $s['rentang']);
        }
        // Baris tanpa sel sendiri (seluruhnya lanjutan gabungan) tidak bisa ditulis di HTML; gabungannya dipendekkan.
        if ($jumlahKolom === 0) {
            return '';
        }

        $lebar = $this->lebarKolom($tbl, $sel, $jumlahKolom, $lebarMaks);
        $garisTabel = $this->garisTabel($tbl);
        $this->isiGaris($sel, $garisTabel, count($baris), $jumlahKolom);

        $jc = $q('./w:tblPr/w:jc/@w:val', $tbl)->item(0)?->value;
        $html = '<table' . (['center' => ' style="margin-left:auto;margin-right:auto"', 'right' => ' style="margin-left:auto;margin-right:0"', 'end' => ' style="margin-left:auto;margin-right:0"'][$jc] ?? '') . '><tbody>';
        $judul = true;

        foreach ($baris as $b => $tr) {
            $judul = $judul && $this->menyala($q('./w:trPr/w:tblHeader', $tr)->item(0));
            $tinggi = (int) ($q('./w:trPr/w:trHeight/@w:val', $tr)->item(0)?->value ?? 0);
            $html .= '<tr' . ($tinggi >= 100 ? ' style="height:' . min(800, round($tinggi / 20, 1)) . 'pt"' : '') . '>';

            foreach ($sel[$b] as $s) {
                $tag = $judul ? 'th' : 'td';
                $px = array_slice($lebar, $s['kolom'], $s['rentang']);
                $html .= "<{$tag}" . ($s['rentang'] > 1 ? " colspan=\"{$s['rentang']}\"" : '') . ($s['turun'] > 1 ? " rowspan=\"{$s['turun']}\"" : '')
                    . ' colwidth="' . implode(',', $px) . '"' . $this->gayaSel($s) . '>'
                    . ($s['el'] ? $this->blok(iterator_to_array($s['el']->childNodes), true, max(60, array_sum($px) - 12)) : '<p></p>') . "</{$tag}>";
            }
            $html .= '</tr>';
        }

        return $html . '</tbody></table>';
    }

    private function menyala(?DOMElement $el): bool
    {
        return $el !== null && ! in_array($el->getAttributeNS(self::W, 'val'), ['0', 'false', 'off'], true);
    }

    /** Lebar kolom (px), paling lebar selebar kertas. @return list<int> */
    private function lebarKolom(DOMElement $tbl, array $sel, int $jumlah, float $lebarMaks): array
    {
        $grid = [];
        foreach ($this->xpath->query('./w:tblGrid/w:gridCol/@w:w', $tbl) as $w) {
            $grid[] = (int) $w->value / self::TWIP_PER_PX;
        }

        // Lebar sel lebih dipercaya daripada grid, yang sering tidak diperbarui pembuat dokumen selain Word.
        if (count($grid) !== $jumlah || min($grid) < 5) {
            $grid = array_fill(0, $jumlah, 0);
            foreach (array_merge(...$sel) as $s) {
                $w = $s['el'] ? $this->xpath->query('./w:tcPr/w:tcW[@w:type="dxa"]/@w:w', $s['el'])->item(0)?->value : null;
                for ($i = 0; $w && $i < $s['rentang']; $i++) {
                    $grid[$s['kolom'] + $i] = $grid[$s['kolom'] + $i] ?: (int) $w / $s['rentang'] / self::TWIP_PER_PX;
                }
            }
            $kosong = count(array_filter($grid, fn ($px) => $px < 5));
            $bagi = $kosong ? max(60, ($lebarMaks - array_sum($grid)) / $kosong) : 0;
            $grid = array_map(fn ($px) => $px < 5 ? $bagi : $px, $grid);
        }

        $total = array_sum($grid);
        // Tabel selebar halaman Word (tepinya sering berbeda) diciutkan supaya muat di kertas naskah.
        $skala = $total > $lebarMaks ? $lebarMaks / $total : 1;

        return array_map(fn ($px) => max(20, (int) floor($px * $skala)), array_values($grid));
    }

    /** Garis tabel dari tabelnya sendiri atau gayanya: sisi => [jenis, tebal pt] atau null bila tidak ada. */
    private function garisTabel(DOMElement $tbl): array
    {
        $sisi = array_fill_keys(['top', 'left', 'bottom', 'right', 'insideH', 'insideV'], null);
        $gaya = $this->xpath->query('./w:tblPr/w:tblStyle/@w:val', $tbl)->item(0)?->value;

        foreach ($gaya && isset($this->gaya[$gaya]) ? $this->rantai($gaya) : [] as $g) {
            $sisi = array_replace($sisi, $this->bacaGaris($this->gayaXpath->query('./w:tblPr/w:tblBorders', $g['node'])->item(0)));
        }

        return array_replace($sisi, $this->bacaGaris($this->xpath->query('./w:tblPr/w:tblBorders', $tbl)->item(0)));
    }

    private function bacaGaris(?DOMElement $el): array
    {
        $hasil = [];
        foreach ($el?->childNodes ?? [] as $anak) {
            if (! $anak instanceof DOMElement) {
                continue;
            }
            $nama = ['start' => 'left', 'end' => 'right'][$anak->localName] ?? $anak->localName;
            $jenis = $anak->getAttributeNS(self::W, 'val');
            $hasil[$nama] = in_array($jenis, ['nil', 'none', ''], true) ? null : [
                match (true) {
                    str_starts_with($jenis, 'double'), in_array($jenis, ['triple'], true) => 'double',
                    $jenis === 'dotted' => 'dotted',
                    str_starts_with($jenis, 'dash'), str_starts_with($jenis, 'dot') => 'dashed',
                    default => 'solid',
                },
                max(0.25, (int) $anak->getAttributeNS(self::W, 'sz') / 8),
            ];
        }

        return $hasil;
    }

    /** Garis tiap sel; garis bersama disamakan (yang ada mengalahkan yang tidak ada). */
    private function isiGaris(array &$sel, array $tabel, int $jumlahBaris, int $jumlahKolom): void
    {
        $peta = [];

        foreach ($sel as $b => &$barisnya) {
            foreach ($barisnya as $i => &$s) {
                $bawah = $b + $s['turun'];
                $kanan = $s['kolom'] + $s['rentang'];
                $s['garis'] = [
                    'top' => $b === 0 ? $tabel['top'] : $tabel['insideH'],
                    'bottom' => $bawah >= $jumlahBaris ? $tabel['bottom'] : $tabel['insideH'],
                    'left' => $s['kolom'] === 0 ? $tabel['left'] : $tabel['insideV'],
                    'right' => $kanan >= $jumlahKolom ? $tabel['right'] : $tabel['insideV'],
                ];
                if ($s['el'] === null) {
                    $s['garis'] = array_fill_keys(['top', 'bottom', 'left', 'right'], null);
                } else {
                    $sendiri = $this->bacaGaris($this->xpath->query('./w:tcPr/w:tcBorders', $s['el'])->item(0));
                    $akhir = $this->bacaGaris($this->xpath->query('./w:tcPr/w:tcBorders', $s['akhir'])->item(0));
                    if (array_key_exists('bottom', $akhir)) {
                        $sendiri['bottom'] = $akhir['bottom'];
                    }
                    $s['garis'] = array_replace($s['garis'], array_intersect_key($sendiri, $s['garis']));
                }
                for ($r = $b; $r < $bawah; $r++) {
                    for ($k = $s['kolom']; $k < $kanan; $k++) {
                        $peta[$r][$k] = [$b, $i];
                    }
                }
            }
            unset($s);
        }
        unset($barisnya);

        $asli = $sel;
        foreach ($sel as $b => &$barisnya) {
            foreach ($barisnya as &$s) {
                foreach (['top' => 'bottom', 'bottom' => 'top', 'left' => 'right', 'right' => 'left'] as $sisi => $lawan) {
                    if ($s['garis'][$sisi] !== null) {
                        continue;
                    }
                    $titik = match ($sisi) {
                        'top' => [$b - 1, $s['kolom']],
                        'bottom' => [$b + $s['turun'], $s['kolom']],
                        'left' => [$b, $s['kolom'] - 1],
                        'right' => [$b, $s['kolom'] + $s['rentang']],
                    };
                    if ($letak = $peta[$titik[0]][$titik[1]] ?? null) {
                        $s['garis'][$sisi] = $asli[$letak[0]][$letak[1]]['garis'][$lawan];
                    }
                }
            }
            unset($s);
        }
    }

    private function gayaSel(array $s): string
    {
        $gaya = [];

        if (! array_filter($s['garis'])) {
            $gaya[] = 'border-style:none';
        } else {
            foreach ($s['garis'] as $sisi => $garis) {
                if ($garis === null) {
                    $gaya[] = "border-{$sisi}:none";
                } elseif ($garis[0] !== 'solid' || $garis[1] > 1) {
                    $gaya[] = "border-{$sisi}:{$garis[1]}pt {$garis[0]}";
                }
            }
        }

        if ($s['el']) {
            $warna = $this->xpath->query('./w:tcPr/w:shd/@w:fill', $s['el'])->item(0)?->value;
            if ($warna && preg_match('/^[0-9a-f]{6}$/i', $warna) && strtolower($warna) !== 'ffffff') {
                $gaya[] = 'background-color:#' . strtolower($warna);
            }
            $tegak = ['center' => 'middle', 'bottom' => 'bottom'][$this->xpath->query('./w:tcPr/w:vAlign/@w:val', $s['el'])->item(0)?->value ?? ''] ?? null;
            if ($tegak) {
                $gaya[] = "vertical-align:{$tegak}";
            }
        }

        return $gaya ? ' style="' . implode(';', $gaya) . '"' : '';
    }
}
