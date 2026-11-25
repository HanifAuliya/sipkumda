<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Nomor otomatis Word. Nomornya tidak tersimpan sebagai teks, jadi dihitung ulang dari numbering.xml dan styles.xml.
 * Seperti di Word, daftar yang memakai definisi nomor (abstractNum) yang sama saling melanjutkan; "Mulai ulang dari"
 * (startOverride) memulai hitungan baru saat pertama dipakai. Paragraf harus ditanyakan berurutan.
 */
class PenomoranWord
{
    public const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    /** @var array<string, array{abstrak: string, level: array<int, array{fmt: string, teks: string, mulai: int, kiri: ?int}>, mulai: array<int, int>}> per numId */
    private array $penomoran = [];
    /** Hitungan nomor per abstractNum dan tingkat. */
    private array $hitungan = [];
    /** numId dan tingkat yang "mulai ulang"-nya sudah diterapkan. */
    private array $dimulai = [];
    /** @var array<string, array{numId: ?string, ilvl: ?int}> nomor otomatis yang melekat pada gaya paragraf */
    private array $gayaNomor = [];

    public function __construct(?DOMDocument $penomoran, ?DOMDocument $gaya)
    {
        $this->muatPenomoran($penomoran);
        $this->muatGayaNomor($gaya);
    }

    /**
     * Nomor paragraf ini, atau null bila tidak bernomor. `polos`: penandanya hanya "%n." (bisa menjadi daftar HTML).
     *
     * @return array{teks: string, nilai: int, fmt: string, polos: bool, level: int, kiri: ?int}|null
     */
    public function nomor(DOMElement $p, DOMXPath $xpath): ?array
    {
        // Nomor bisa ditempel langsung pada paragraf, atau melekat pada gaya paragrafnya (misalnya gaya "Ayat").
        $gaya = $this->gayaNomor[$xpath->query('./w:pPr/w:pStyle/@w:val', $p)->item(0)?->value ?? ''] ?? null;
        $numId = $xpath->query('./w:pPr/w:numPr/w:numId/@w:val', $p)->item(0)?->value ?? $gaya['numId'] ?? null;
        if ($numId === null || $numId === '0' || ! isset($this->penomoran[$numId])) {
            return null;
        }

        $num = $this->penomoran[$numId];
        $level = (int) ($xpath->query('./w:pPr/w:numPr/w:ilvl/@w:val', $p)->item(0)?->value ?? $gaya['ilvl'] ?? 0);
        $format = $num['level'][$level] ?? null;
        if (! $format || $format['fmt'] === 'none') {
            return null;
        }
        if ($format['fmt'] === 'bullet') {
            return ['teks' => '-', 'nilai' => 1, 'fmt' => 'bullet', 'polos' => true, 'level' => $level, 'kiri' => $format['kiri']];
        }

        $daftar = $num['abstrak'];

        if (isset($num['mulai'][$level]) && ! isset($this->dimulai[$numId][$level])) {
            $this->dimulai[$numId][$level] = true;
            $this->hitungan[$daftar][$level] = $num['mulai'][$level] - 1;
        }

        $this->hitungan[$daftar][$level] = ($this->hitungan[$daftar][$level] ?? $format['mulai'] - 1) + 1;
        foreach (array_keys($this->hitungan[$daftar]) as $l) {
            if ($l > $level) {
                unset($this->hitungan[$daftar][$l]);
            }
        }

        $teks = preg_replace_callback('/%(\d)/', function ($m) use ($num, $daftar) {
            $l = (int) $m[1] - 1;
            $lvl = $num['level'][$l] ?? ['fmt' => 'decimal', 'mulai' => 1];

            return self::format($this->hitungan[$daftar][$l] ?? $lvl['mulai'], $lvl['fmt']);
        }, $format['teks']);

        return [
            'teks' => trim($teks),
            'nilai' => $this->hitungan[$daftar][$level],
            'fmt' => $format['fmt'],
            'polos' => trim($format['teks']) === '%' . ($level + 1) . '.' && in_array($format['fmt'], ['decimal', 'lowerLetter', 'upperLetter', 'lowerRoman', 'upperRoman'], true),
            'level' => $level,
            'kiri' => $format['kiri'],
        ];
    }

    private static function format(int $n, string $fmt): string
    {
        return match ($fmt) {
            'lowerLetter' => chr(96 + (($n - 1) % 26 + 1)),
            'upperLetter' => chr(64 + (($n - 1) % 26 + 1)),
            'lowerRoman' => mb_strtolower(self::romawi($n)),
            'upperRoman' => self::romawi($n),
            default => (string) $n,
        };
    }

    private static function romawi(int $n): string
    {
        $hasil = '';
        foreach (['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1] as $r => $nilai) {
            while ($n >= $nilai) {
                $hasil .= $r;
                $n -= $nilai;
            }
        }

        return $hasil;
    }

    /** Gaya paragraf yang bernomor, termasuk yang mewarisi nomornya dari gaya induk (basedOn). */
    private function muatGayaNomor(?DOMDocument $dom): void
    {
        if (! $dom) {
            return;
        }

        $xp = new DOMXPath($dom);
        $xp->registerNamespace('w', self::W);
        $gaya = [];

        foreach ($xp->query('/w:styles/w:style[@w:type="paragraph"]') as $node) {
            $gaya[$node->getAttributeNS(self::W, 'styleId')] = [
                'induk' => $xp->query('./w:basedOn/@w:val', $node)->item(0)?->value,
                'numId' => $xp->query('./w:pPr/w:numPr/w:numId/@w:val', $node)->item(0)?->value,
                'ilvl' => ($v = $xp->query('./w:pPr/w:numPr/w:ilvl/@w:val', $node)->item(0)?->value) === null ? null : (int) $v,
            ];
        }

        foreach (array_keys($gaya) as $id) {
            $numId = $ilvl = null;

            // Naik ke gaya induk sampai nomornya ketemu; dibatasi supaya rantai yang berputar tidak macet.
            for ($kini = $id, $langkah = 0; $kini !== null && isset($gaya[$kini]) && $langkah < 10; $kini = $gaya[$kini]['induk'], $langkah++) {
                $numId ??= $gaya[$kini]['numId'];
                $ilvl ??= $gaya[$kini]['ilvl'];
            }

            if ($numId !== null) {
                $this->gayaNomor[$id] = ['numId' => $numId, 'ilvl' => $ilvl];
            }
        }
    }

    private function muatPenomoran(?DOMDocument $dom): void
    {
        if (! $dom) {
            return;
        }

        $xp = new DOMXPath($dom);
        $xp->registerNamespace('w', self::W);

        $abstrak = [];
        foreach ($xp->query('/w:numbering/w:abstractNum') as $node) {
            foreach ($xp->query('./w:lvl', $node) as $lvl) {
                $kiri = $xp->query('./w:pPr/w:ind/@w:left | ./w:pPr/w:ind/@w:start', $lvl)->item(0)?->value;
                $abstrak[$node->getAttributeNS(self::W, 'abstractNumId')][(int) $lvl->getAttributeNS(self::W, 'ilvl')] = [
                    'fmt' => $xp->query('./w:numFmt/@w:val', $lvl)->item(0)?->value ?? 'decimal',
                    'teks' => $xp->query('./w:lvlText/@w:val', $lvl)->item(0)?->value ?? '',
                    'mulai' => (int) ($xp->query('./w:start/@w:val', $lvl)->item(0)?->value ?? 1),
                    'kiri' => $kiri === null ? null : (int) $kiri,
                ];
            }
        }

        foreach ($xp->query('/w:numbering/w:num') as $num) {
            $acuan = (string) $xp->query('./w:abstractNumId/@w:val', $num)->item(0)?->value;
            $mulai = [];

            foreach ($xp->query('./w:lvlOverride[w:startOverride]', $num) as $timpa) {
                $mulai[(int) $timpa->getAttributeNS(self::W, 'ilvl')] = (int) $xp->query('./w:startOverride/@w:val', $timpa)->item(0)->value;
            }

            $this->penomoran[$num->getAttributeNS(self::W, 'numId')] = ['abstrak' => $acuan, 'level' => $abstrak[$acuan] ?? [], 'mulai' => $mulai];
        }
    }
}
