<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

/**
 * Memecah file .docx menjadi baris matriks.
 *
 * Menerima naskah biasa (paragraf berurutan) maupun matriks yang sudah berbentuk tabel
 * (kolom "Rumusan" yang dibaca). Hasilnya hanya pengisi awal kolom 3: PD selalu memeriksanya
 * sebelum mengajukan, jadi pembacaan yang meleset tidak merusak apa pun.
 */
class PembacaNaskahWord
{
    private const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    /** Melindungi server dari file kecil yang mengembang sangat besar saat dibuka. */
    private const BATAS_XML = 30 * 1024 * 1024;

    private const DIKTUM = 'SATU|DUA|TIGA|EMPAT|LIMA|ENAM|TUJUH|DELAPAN|SEMBILAN|SEPULUH|SEBELAS|DUA BELAS|TIGA BELAS';

    private DOMXPath $xpath;
    private array $penomoran = [];
    private array $hitungan = [];

    /** @var array<int, array{bagian: string, label: ?string, teks: string}> */
    private array $baris = [];
    private array $peringatan = [];
    private string $bagian = 'judul';
    private string $jenis = '';

    /**
     * @return array{baris: array<int, array{bagian: string, label: ?string, teks: string}>, ringkasan: array<string, int>, peringatan: array<int, string>}
     */
    public function baca(string $path, string $jenis): array
    {
        $this->jenis = $jenis;

        foreach ($this->barisTeks($path) as [$teks, $petunjuk]) {
            $this->proses($teks, $petunjuk);
        }

        $this->lengkapi();

        return [
            'baris' => array_values(array_map(fn ($b) => ['bagian' => $b['bagian'], 'label' => $b['label'], 'teks' => trim($b['teks'])], $this->baris)),
            'ringkasan' => $this->ringkasan(),
            'peringatan' => array_values(array_unique($this->peringatan)),
        ];
    }

    /** @return array<int, array{0: string, 1: ?string}> pasangan teks paragraf dan petunjuk bagian (dari kolom Materi) */
    private function barisTeks(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File bukan dokumen Word (.docx) yang valid.');
        }

        try {
            $utama = $this->ambilXml($zip, 'word/document.xml')
                ?? throw new RuntimeException('Isi dokumen Word tidak ditemukan.');
            $this->muatPenomoran($this->ambilXml($zip, 'word/numbering.xml'));
        } finally {
            $zip->close();
        }

        $this->xpath = new DOMXPath($utama);
        $this->xpath->registerNamespace('w', self::W);

        $hasil = [];
        foreach ($this->xpath->query('/w:document/w:body/*') as $node) {
            if ($node->localName === 'p') {
                $hasil[] = [$this->teksParagraf($node), null];
            } elseif ($node->localName === 'tbl') {
                $isiTabel = $this->barisTabel($node);

                // Pada file matriks, teks di luar tabel hanya kepala dan tanda tangan matriks itu sendiri.
                if ($this->kolomRumusan($node) !== null) {
                    return $isiTabel;
                }

                array_push($hasil, ...$isiTabel);
            }
        }

        return $hasil;
    }

    /** Indeks kolom "Rumusan" bila tabel ini adalah matriks, selain itu null. */
    private function kolomRumusan(DOMElement $tabel): ?int
    {
        $kepala = $this->xpath->query('./w:tr', $tabel)->item(0);

        foreach ($kepala ? $this->xpath->query('./w:tc', $kepala) : [] as $i => $sel) {
            if (str_contains(mb_strtolower($sel->textContent), 'rumusan')) {
                return $i;
            }
        }

        return null;
    }

    private function ambilXml(ZipArchive $zip, string $nama): ?DOMDocument
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

    private function barisTabel(DOMElement $tabel): array
    {
        $baris = iterator_to_array($this->xpath->query('./w:tr', $tabel));
        $kolomRumusan = $this->kolomRumusan($tabel);
        $kolomMateri = null;

        if ($kolomRumusan !== null) {
            foreach ($this->xpath->query('./w:tc', $baris[0]) as $i => $sel) {
                if (str_contains(mb_strtolower($sel->textContent), 'materi')) {
                    $kolomMateri = $i;
                }
            }
        }

        // Tabel yang hanya mengatur tata letak Menimbang/Mengingat/diktum dibaca sebagai paragraf,
        // sedangkan tabel data dipertahankan dalam bentuk baris "| a | b |".
        $tataLetak = preg_match('/menimbang|mengingat|menetapkan|\bKE\s*SATU\b/iu', $tabel->textContent) === 1;

        $hasil = [];
        foreach ($baris as $nomor => $tr) {
            $sel = iterator_to_array($this->xpath->query('./w:tc', $tr));

            if ($kolomRumusan === null) {
                $isi = array_filter(array_map(fn ($s) => trim(implode(' ', $this->paragrafSel($s))), $sel), 'strlen');

                if (count($isi) >= 3 && ! $tataLetak) {
                    $this->peringatan[] = 'Dokumen memuat tabel. Periksa baris yang berisi tabel setelah dibaca.';
                    $hasil[] = ['| ' . implode(' | ', array_map(fn ($s) => str_replace('|', '/', $s), $isi)) . ' |', null];
                } else {
                    $hasil[] = [implode(' ', $isi), null];
                }

                continue;
            }

            // Lewati kepala tabel dan baris nomor kolom (1), (2), (3).
            if ($nomor === 0 || preg_match('/^\(\d+\)$/', trim($sel[0]->textContent ?? ''))) {
                continue;
            }

            $petunjuk = $kolomMateri !== null && isset($sel[$kolomMateri]) ? trim($sel[$kolomMateri]->textContent) : null;

            foreach ($this->paragrafSel($sel[$kolomRumusan] ?? null) as $i => $teks) {
                $hasil[] = [$teks, $i === 0 ? ($petunjuk ?: null) : null];
            }
            if ($petunjuk && ! $this->paragrafSel($sel[$kolomRumusan] ?? null)) {
                $hasil[] = ['', $petunjuk];
            }
        }

        return $hasil;
    }

    private function paragrafSel(?DOMElement $sel): array
    {
        if (! $sel) {
            return [];
        }

        $hasil = [];
        foreach ($this->xpath->query('./w:p', $sel) as $p) {
            $teks = $this->teksParagraf($p);
            if ($teks !== '') {
                $hasil[] = $teks;
            }
        }

        return $hasil;
    }

    private function teksParagraf(DOMElement $p): string
    {
        $teks = '';
        foreach ($this->xpath->query('.//w:t | .//w:tab[not(ancestor::w:tabs)] | .//w:br', $p) as $node) {
            $teks .= match ($node->localName) {
                't' => $node->textContent,
                'tab' => ' ',
                default => "\n",
            };
        }

        $teks = trim(preg_replace('/[ \t\x{00A0}]+/u', ' ', $teks));

        return $teks === '' ? '' : $this->awalanNomor($p) . $teks;
    }

    /** Nomor otomatis Word tidak tersimpan sebagai teks, jadi dihitung ulang dari numbering.xml. */
    private function awalanNomor(DOMElement $p): string
    {
        $numId = $this->xpath->query('./w:pPr/w:numPr/w:numId/@w:val', $p)->item(0)?->value;
        if ($numId === null || $numId === '0' || ! isset($this->penomoran[$numId])) {
            return '';
        }

        $level = (int) ($this->xpath->query('./w:pPr/w:numPr/w:ilvl/@w:val', $p)->item(0)?->value ?? 0);
        $format = $this->penomoran[$numId][$level] ?? null;
        if (! $format || $format['fmt'] === 'bullet' || $format['fmt'] === 'none') {
            return '';
        }

        $this->hitungan[$numId][$level] = ($this->hitungan[$numId][$level] ?? 0) + 1;
        foreach (array_keys($this->hitungan[$numId]) as $l) {
            if ($l > $level) {
                unset($this->hitungan[$numId][$l]);
            }
        }

        $teks = preg_replace_callback('/%(\d)/', function ($m) use ($numId) {
            $l = (int) $m[1] - 1;

            return $this->formatNomor($this->hitungan[$numId][$l] ?? 1, $this->penomoran[$numId][$l]['fmt'] ?? 'decimal');
        }, $format['teks']);

        return trim($teks) . ' ';
    }

    private function formatNomor(int $n, string $fmt): string
    {
        return match ($fmt) {
            'lowerLetter' => chr(96 + (($n - 1) % 26 + 1)),
            'upperLetter' => chr(64 + (($n - 1) % 26 + 1)),
            'lowerRoman' => mb_strtolower($this->romawi($n)),
            'upperRoman' => $this->romawi($n),
            default => (string) $n,
        };
    }

    private function romawi(int $n): string
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

    private function muatPenomoran(?DOMDocument $dom): void
    {
        $this->penomoran = [];
        $this->hitungan = [];

        if (! $dom) {
            return;
        }

        $xp = new DOMXPath($dom);
        $xp->registerNamespace('w', self::W);

        $abstrak = [];
        foreach ($xp->query('/w:numbering/w:abstractNum') as $node) {
            foreach ($xp->query('./w:lvl', $node) as $lvl) {
                $abstrak[$node->getAttributeNS(self::W, 'abstractNumId')][(int) $lvl->getAttributeNS(self::W, 'ilvl')] = [
                    'fmt' => $xp->query('./w:numFmt/@w:val', $lvl)->item(0)?->value ?? 'decimal',
                    'teks' => $xp->query('./w:lvlText/@w:val', $lvl)->item(0)?->value ?? '',
                ];
            }
        }

        foreach ($xp->query('/w:numbering/w:num') as $num) {
            $acuan = $xp->query('./w:abstractNumId/@w:val', $num)->item(0)?->value;
            $this->penomoran[$num->getAttributeNS(self::W, 'numId')] = $abstrak[$acuan] ?? [];
        }
    }

    /** Mesin keadaan: setiap paragraf memulai baris baru atau menyambung baris yang sedang dibaca. */
    private function proses(string $teks, ?string $petunjuk): void
    {
        $teks = trim($teks);

        if ($petunjuk && ($bagian = $this->bagianDariPetunjuk($petunjuk))) {
            $this->pindahBagian($bagian);
            if ($bagian === 'lampiran') {
                $this->baruBaris('lampiran', null, '');
            }
        }

        if ($teks === '') {
            return;
        }

        if (preg_match('/^LAMPIRAN\b/u', $teks) && $this->bagian !== 'judul') {
            $this->bagian = 'lampiran';
            $this->peringatan[] = 'Bagian lampiran di dalam Word tidak dibaca. Unggah lampiran sebagai file terpisah.';

            return;
        }
        if ($this->bagian === 'lampiran') {
            return;
        }

        if (preg_match('/^menimbang\s*:?\s*(.*)$/isu', $teks, $m)) {
            $this->pindahBagian('menimbang');
            $teks = trim($m[1]);
            if ($teks === '') {
                return;
            }
        } elseif (preg_match('/^mengingat\s*:?\s*(.*)$/isu', $teks, $m)) {
            $this->pindahBagian('mengingat');
            $teks = trim($m[1]);
            if ($teks === '') {
                return;
            }
        } elseif ($this->jenis === 'Keputusan Bupati' && preg_match('/^memperhatikan\s*:?\s*(.*)$/isu', $teks, $m)) {
            // Memperhatikan hanya dikenal pada Keputusan Bupati, sesudah Mengingat.
            $this->pindahBagian('memperhatikan');
            $teks = trim($m[1]);
            if ($teks === '') {
                return;
            }
        } elseif (preg_match('/^(?:ditetapkan|diundangkan)\s+di\b/iu', $teks)) {
            $this->pindahBagian('penutup');
        } elseif (preg_match('/^M\s*E\s*M\s*U\s*T\s*U\s*S\s*K\s*A\s*N\s*:?$/u', $teks)) {
            $this->pindahBagian('diktum');
            if ($this->jenis === 'Keputusan Bupati') {
                return;
            }
        } elseif (preg_match('/^menetapkan\s*:?\s*(.*)$/isu', $teks, $m) && in_array($this->bagian, ['judul', 'menimbang', 'mengingat', 'memperhatikan', 'diktum'], true)) {
            $this->pindahBagian('diktum');
            if ($this->jenis === 'Keputusan Bupati') {
                if (trim($m[1]) === '') {
                    return;
                }
                $teks = trim($m[1]);
            }
        }

        // Naskah tanpa baris "Menetapkan": kepala BAB atau Pasal tetap menandai awal Batang Tubuh.
        if ($this->jenis !== 'Keputusan Bupati' && in_array($this->bagian, ['menimbang', 'mengingat'], true) && $this->labelStruktur($teks)) {
            $this->bagian = 'batang_tubuh';
        }

        match ($this->bagian) {
            'judul' => $this->prosesJudul($teks),
            'menimbang', 'mengingat', 'memperhatikan' => $this->baruBaris($this->bagian, null, $this->buangNomor($teks)),
            'diktum' => $this->prosesDiktum($teks),
            'batang_tubuh' => $this->prosesBatangTubuh($teks),
            default => $this->sambung($teks),
        };
    }

    private function prosesJudul(string $teks): void
    {
        // Kop dan kalimat pembuka dicetak dari template, bukan bagian judul.
        if (preg_match('/^BUPATI HULU SUNGAI TENGAH,?$|^PROVINSI KALIMANTAN SELATAN,?$|^DENGAN RAHMAT TUHAN YANG MAHA ESA,?$/iu', $teks)) {
            return;
        }

        $this->sambung($teks);
    }

    private function prosesDiktum(string $teks): void
    {
        if ($this->jenis === 'Keputusan Bupati') {
            if (preg_match('/^KE\s*(?:' . self::DIKTUM . ')\s*:?\s*(.*)$/su', $teks, $m)) {
                $this->baruBaris('diktum', null, trim($m[1]));

                return;
            }
            $this->sambung($teks);

            return;
        }

        // Pada Peraturan Bupati, BAB atau Pasal pertama menandai awal Batang Tubuh.
        if ($this->labelStruktur($teks)) {
            $this->bagian = 'batang_tubuh';
            $this->prosesBatangTubuh($teks);

            return;
        }

        $this->sambung($teks);
    }

    private function prosesBatangTubuh(string $teks): void
    {
        if ($hasil = $this->labelStruktur($teks)) {
            $this->baruBaris('batang_tubuh', $hasil[0], $hasil[1]);

            return;
        }

        $this->sambung($teks);
    }

    /** @return array{0: string, 1: string}|null [label, sisa teks] bila paragraf adalah kepala BAB, Bagian, Paragraf, atau Pasal */
    private function labelStruktur(string $teks): ?array
    {
        // Baris di dalam sel bisa memuat "Pasal 1\nDalam Peraturan...".
        $baris = preg_split('/\R/u', $teks, 2);
        $kepala = trim($baris[0]);
        $sisa = trim($baris[1] ?? '');

        if (preg_match('/^(BAB\s+[IVXLC]+)\s*(.*)$/u', $kepala, $m)) {
            return [preg_replace('/\s+/', ' ', $m[1]), trim($m[2] . "\n" . $sisa)];
        }
        if (preg_match('/^(Pasal\s+(?:\d+[A-Z]?|[IVXLC]+))$/iu', $kepala, $m)) {
            return [preg_replace('/\s+/', ' ', $m[1]), $sisa];
        }
        if (preg_match('/^((?:Bagian\s+Ke\w+|Paragraf\s+\d+))$/iu', $kepala, $m)) {
            return [preg_replace('/\s+/', ' ', $m[1]), $sisa];
        }

        return null;
    }

    private function buangNomor(string $teks): string
    {
        return preg_replace('/^(?:[a-z]\.|\d{1,2}\.)\s*/u', '', $teks);
    }

    private function bagianDariPetunjuk(string $petunjuk): ?string
    {
        $p = mb_strtolower($petunjuk);

        return match (true) {
            str_contains($p, 'judul') => 'judul',
            str_contains($p, 'menimbang') => 'menimbang',
            str_contains($p, 'mengingat') => 'mengingat',
            str_contains($p, 'memperhatikan') && $this->jenis === 'Keputusan Bupati' => 'memperhatikan',
            str_contains($p, 'diktum'), str_contains($p, 'menetapkan') => 'diktum',
            str_contains($p, 'batang') => 'batang_tubuh',
            str_contains($p, 'penutup') => 'penutup',
            str_contains($p, 'lampiran') => 'lampiran',
            default => null,
        };
    }

    private function pindahBagian(string $bagian): void
    {
        $this->bagian = $bagian;

        // Bagian yang berisi satu blok teks memulai barisnya saat pertama dimasuki.
        if (in_array($bagian, ['judul', 'diktum', 'penutup'], true) && ! ($bagian === 'diktum' && $this->jenis === 'Keputusan Bupati')) {
            $terakhir = end($this->baris);
            if (! $terakhir || $terakhir['bagian'] !== $bagian) {
                $this->baruBaris($bagian, null, '');
            }
        }
    }

    private function baruBaris(string $bagian, ?string $label, string $teks): void
    {
        $this->baris[] = ['bagian' => $bagian, 'label' => $label, 'teks' => $teks];
    }

    private function sambung(string $teks): void
    {
        $terakhir = array_key_last($this->baris);

        if ($terakhir === null || $this->baris[$terakhir]['bagian'] !== $this->bagian) {
            $this->baruBaris($this->bagian, null, $teks);

            return;
        }

        $sebelum = $this->baris[$terakhir]['teks'];
        $this->baris[$terakhir]['teks'] = $sebelum === '' ? $teks : "{$sebelum}\n{$teks}";
    }

    /** Baris kosong tanpa label dibuang, dan baris Lampiran selalu ada untuk ditanggapi Peneliti. */
    private function lengkapi(): void
    {
        $this->baris = array_values(array_filter(
            $this->baris,
            fn ($b) => trim($b['teks']) !== '' || filled($b['label']) || $b['bagian'] === 'lampiran',
        ));

        if (! in_array('lampiran', array_column($this->baris, 'bagian'), true)) {
            $this->baruBaris('lampiran', null, '');
        }

        if (! array_filter($this->baris, fn ($b) => in_array($b['bagian'], ['menimbang', 'mengingat'], true))) {
            $this->peringatan[] = 'Bagian Menimbang atau Mengingat tidak ditemukan. Periksa hasil pembacaan.';
        }
    }

    private function ringkasan(): array
    {
        $hitung = [];
        foreach ($this->baris as $b) {
            $kunci = str_starts_with((string) $b['label'], 'Pasal') ? 'pasal' : $b['bagian'];
            $hitung[$kunci] = ($hitung[$kunci] ?? 0) + 1;
        }

        return $hitung;
    }
}
