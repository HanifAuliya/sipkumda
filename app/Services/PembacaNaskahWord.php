<?php

namespace App\Services;

use App\Support\TataLetakNaskah;
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
    /** @var array<string, array{abstrak: string, level: array<int, array{fmt: string, teks: string, mulai: int}>, mulai: array<int, int>}> per numId */
    private array $penomoran = [];
    /** Hitungan nomor per abstractNum dan tingkat. */
    private array $hitungan = [];
    /** numId dan tingkat yang "mulai ulang"-nya sudah diterapkan. */
    private array $dimulai = [];
    /** @var array<string, array{numId: ?string, ilvl: ?int}> nomor otomatis yang melekat pada gaya paragraf */
    private array $gayaNomor = [];

    /** @var array<int, array{bagian: string, label: ?string, teks: string}> */
    private array $baris = [];
    private array $peringatan = [];
    private string $bagian = 'judul';
    private string $jenis = '';

    /** Bentuk file yang diunggah: naskah seperti yang dicetak, atau matriks berbentuk tabel. */
    public const BENTUK_NASKAH = 'naskah';
    public const BENTUK_MATRIKS = 'matriks';

    /**
     * @param  string|null  $bentuk  BENTUK_NASKAH, BENTUK_MATRIKS, atau null untuk menebak dari isinya
     * @return array{baris: array<int, array{bagian: string, label: ?string, teks: string}>, ringkasan: array<string, int>, peringatan: array<int, string>}
     */
    public function baca(string $path, string $jenis, ?string $bentuk = null): array
    {
        $this->jenis = $jenis;

        foreach ($this->barisTeks($path, $bentuk) as [$teks, $petunjuk]) {
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
    private function barisTeks(string $path, ?string $bentuk): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File bukan dokumen Word (.docx) yang valid.');
        }

        try {
            $utama = $this->ambilXml($zip, 'word/document.xml')
                ?? throw new RuntimeException('Isi dokumen Word tidak ditemukan.');
            $this->muatPenomoran($this->ambilXml($zip, 'word/numbering.xml'));
            $this->muatGayaNomor($this->ambilXml($zip, 'word/styles.xml'));
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
                $kepala = $bentuk === self::BENTUK_NASKAH ? null : $this->kepalaMatriks($node);

                // Pada file matriks, teks di luar tabel hanya kepala dan tanda tangan matriks itu sendiri.
                if ($kepala !== null) {
                    return $this->barisMatriks($node, $kepala);
                }

                array_push($hasil, ...$this->barisTabel($node));
            }
        }

        if ($bentuk === self::BENTUK_MATRIKS) {
            throw new RuntimeException('Tabel matriks dengan kolom "Rumusan" tidak ditemukan. Pilih "Naskah Rancangan" bila file berupa naskah biasa.');
        }

        return $hasil;
    }

    /**
     * Kolom-kolom matriks menurut kepala tabelnya (dicari di tiga baris teratas), atau null bila tabel
     * ini bukan matriks. Sebagian matriks punya kolom sempit tanpa judul tepat di kiri Rumusan berisi
     * nomor butir (1, 2, a, b); nomor itu ikut dibaca supaya butirnya tidak menjadi paragraf biasa.
     *
     * @return array{baris: int, rumusan: int, materi: ?int, nomor: ?int}|null indeks kolom grid
     */
    private function kepalaMatriks(DOMElement $tabel): ?array
    {
        foreach ($this->xpath->query('./w:tr[position() <= 3]', $tabel) as $r => $tr) {
            $judul = array_map(fn (DOMElement $tc) => mb_strtolower($this->bersih($tc->textContent)), $this->selGrid($tr));
            $rumusan = $materi = null;

            foreach ($judul as $k => $teks) {
                $rumusan ??= str_contains($teks, 'rumusan') ? $k : null;
                $materi ??= str_contains($teks, 'materi') ? $k : null;
            }

            if ($rumusan === null) {
                continue;
            }

            $kiri = $rumusan - 1;
            $nomor = $kiri > ($materi ?? 0) && ($judul[$kiri] ?? '') === '' ? $kiri : null;

            return ['baris' => $r, 'rumusan' => $rumusan, 'materi' => $materi, 'nomor' => $nomor];
        }

        return null;
    }

    /**
     * Sel satu baris tabel menurut kolom grid tempatnya mulai, supaya sel gabungan (gridSpan)
     * tidak menggeser kolom Rumusan.
     *
     * @return array<int, DOMElement>
     */
    private function selGrid(DOMElement $tr): array
    {
        $hasil = [];
        $kolom = (int) ($this->xpath->query('./w:trPr/w:gridBefore/@w:val', $tr)->item(0)?->value ?? 0);

        foreach ($this->xpath->query('./w:tc', $tr) as $tc) {
            $hasil[$kolom] = $tc;
            $kolom += max(1, (int) ($this->xpath->query('./w:tcPr/w:gridSpan/@w:val', $tc)->item(0)?->value ?? 1));
        }

        return $hasil;
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

    /** Kolom Rumusan matriks dibaca per paragraf; kolom Materi menjadi petunjuk bagian. */
    private function barisMatriks(DOMElement $tabel, array $kepala): array
    {
        $hasil = [];

        foreach ($this->xpath->query('./w:tr', $tabel) as $r => $tr) {
            $sel = $this->selGrid($tr);
            $pertama = $sel === [] ? '' : $this->bersih($sel[array_key_first($sel)]->textContent);

            // Lewati kepala tabel dan baris nomor kolom (1), (2), (3).
            if ($r <= $kepala['baris'] || preg_match('/^\(\d+\)$/', $pertama)) {
                continue;
            }

            $petunjuk = $kepala['materi'] !== null && isset($sel[$kepala['materi']]) ? $this->bersih($sel[$kepala['materi']]->textContent) : null;
            $paragraf = $this->paragrafSel($sel[$kepala['rumusan']] ?? null);
            $nomor = $kepala['nomor'] !== null && isset($sel[$kepala['nomor']]) ? $this->nomorKolom($sel[$kepala['nomor']]->textContent) : null;

            if ($nomor !== null && $paragraf !== [] && TataLetakNaskah::nomorBaris($paragraf[0])[0] === null) {
                $paragraf[0] = "{$nomor} {$paragraf[0]}";
            }

            foreach ($paragraf as $i => $teks) {
                $hasil[] = [$teks, $i === 0 ? ($petunjuk ?: null) : null];
            }
            if ($petunjuk && $paragraf === []) {
                $hasil[] = ['', $petunjuk];
            }
        }

        return $hasil;
    }

    /** Isi kolom nomor butir: "9" menjadi "9.", "a" menjadi "a."; "(1)", "a.", "1)" dibiarkan. */
    private function nomorKolom(string $teks): ?string
    {
        $teks = $this->bersih($teks);

        return match (true) {
            preg_match('/^(\d{1,3}|[a-z])$/', $teks) === 1 => "{$teks}.",
            preg_match('/^(\(\d{1,3}[a-z]?\)|\d{1,3}[.)]|[a-z][.)])$/', $teks) === 1 => $teks,
            default => null,
        };
    }

    /** Spasi Word termasuk spasi tak-putus (NBSP) yang tidak dibuang trim(). */
    private function bersih(string $teks): string
    {
        return trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $teks));
    }

    /**
     * Tabel di luar matriks: tabel yang hanya mengatur tata letak Menimbang/Mengingat/diktum dibaca
     * sebagai paragraf, sedangkan tabel data dipertahankan dalam bentuk baris "| a | b |".
     */
    private function barisTabel(DOMElement $tabel): array
    {
        $tataLetak = preg_match('/menimbang|mengingat|menetapkan|\bKE\s*SATU\b/iu', $tabel->textContent) === 1;
        $hasil = [];

        foreach ($this->xpath->query('./w:tr', $tabel) as $tr) {
            $isi = array_filter(array_map(fn ($s) => trim(implode(' ', $this->paragrafSel($s))), $this->selGrid($tr)), 'strlen');

            if (count($isi) >= 3 && ! $tataLetak) {
                $this->peringatan[] = 'Dokumen memuat tabel. Periksa baris yang berisi tabel setelah dibaca.';
                $hasil[] = ['| ' . implode(' | ', array_map(fn ($s) => str_replace('|', '/', $s), $isi)) . ' |', null];
            } else {
                $hasil[] = [implode(' ', $isi), null];
            }
        }

        return $hasil;
    }

    /** Paragraf di dalam sel; tabel di dalam sel (misalnya rincian besaran di kolom Rumusan) ikut dibaca. */
    private function paragrafSel(?DOMElement $sel): array
    {
        if (! $sel) {
            return [];
        }

        $hasil = [];
        foreach ($this->xpath->query('./w:p | ./w:tbl', $sel) as $node) {
            $isi = $node->localName === 'tbl' ? array_column($this->barisTabel($node), 0) : [$this->teksParagraf($node)];

            foreach ($isi as $teks) {
                if ($teks !== '') {
                    $hasil[] = $teks;
                }
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

    /**
     * Nomor otomatis Word tidak tersimpan sebagai teks, jadi dihitung ulang dari numbering.xml.
     * Seperti di Word, daftar yang memakai definisi nomor (abstractNum) yang sama saling melanjutkan;
     * "Mulai ulang dari" (startOverride) memulai hitungan baru saat pertama dipakai.
     */
    private function awalanNomor(DOMElement $p): string
    {
        // Nomor bisa ditempel langsung pada paragraf, atau melekat pada gaya paragrafnya (misalnya gaya "Ayat").
        $gaya = $this->gayaNomor[$this->xpath->query('./w:pPr/w:pStyle/@w:val', $p)->item(0)?->value ?? ''] ?? null;
        $numId = $this->xpath->query('./w:pPr/w:numPr/w:numId/@w:val', $p)->item(0)?->value ?? $gaya['numId'] ?? null;
        if ($numId === null || $numId === '0' || ! isset($this->penomoran[$numId])) {
            return '';
        }

        $num = $this->penomoran[$numId];
        $level = (int) ($this->xpath->query('./w:pPr/w:numPr/w:ilvl/@w:val', $p)->item(0)?->value ?? $gaya['ilvl'] ?? 0);
        $format = $num['level'][$level] ?? null;
        if (! $format || $format['fmt'] === 'bullet' || $format['fmt'] === 'none') {
            return '';
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

            return $this->formatNomor($this->hitungan[$daftar][$l] ?? $lvl['mulai'], $lvl['fmt']);
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

    /** Gaya paragraf yang bernomor, termasuk yang mewarisi nomornya dari gaya induk (basedOn). */
    private function muatGayaNomor(?DOMDocument $dom): void
    {
        $this->gayaNomor = [];

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
        $this->penomoran = [];
        $this->hitungan = [];
        $this->dimulai = [];

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
                    'mulai' => (int) ($xp->query('./w:start/@w:val', $lvl)->item(0)?->value ?? 1),
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
