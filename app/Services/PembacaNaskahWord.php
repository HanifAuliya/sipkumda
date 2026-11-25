<?php

namespace App\Services;

use App\Models\NaskahBaris;
use App\Support\FormatDokumen;
use App\Support\HtmlLampiran;
use App\Support\PenomoranWord;
use App\Support\TataLetakNaskah;
use DOMElement;
use DOMXPath;
use Generator;
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
    private const W = PenomoranWord::W;

    private const DIKTUM = 'SATU|DUA|TIGA|EMPAT|LIMA|ENAM|TUJUH|DELAPAN|SEMBILAN|SEPULUH|SEBELAS|DUA BELAS|TIGA BELAS';

    private DOMXPath $xpath;
    private PenomoranWord $penomoran;
    private PembacaLampiranWord $pembacaLampiran;

    /** @var array<int, array{bagian: string, label: ?string, teks: string, format?: string}> */
    private array $baris = [];
    private array $peringatan = [];
    private string $bagian = 'judul';
    private string $jenis = '';

    /** Bentuk file yang diunggah: naskah seperti yang dicetak, atau matriks berbentuk tabel. */
    public const BENTUK_NASKAH = 'naskah';
    public const BENTUK_MATRIKS = 'matriks';

    /**
     * @param  string|null  $bentuk  BENTUK_NASKAH, BENTUK_MATRIKS, atau null untuk menebak dari isinya
     * @return array{baris: array<int, array{bagian: string, label: ?string, teks: string, format?: string}>, ringkasan: array<string, int>, peringatan: array<int, string>}
     */
    public function baca(string $path, string $jenis, ?string $bentuk = null): array
    {
        $this->jenis = $jenis;

        foreach ($this->barisTeks($path, $bentuk) as [$teks, $petunjuk, $node, $selLampiran]) {
            if ($node && $this->bagian !== 'judul' && preg_match('/^LAMPIRAN\b/u', $teks)) {
                $this->bacaLampiran($node);

                break;
            }

            $this->proses($teks, $petunjuk);

            if ($selLampiran) {
                $this->tambahLampiran(iterator_to_array($selLampiran->childNodes));
            }
        }

        $this->lengkapi();

        return [
            'baris' => array_values(array_map(fn ($b) => ['bagian' => $b['bagian'], 'label' => $b['label'], 'teks' => trim($b['teks'])] + array_intersect_key($b, ['format' => 1]), $this->baris)),
            'ringkasan' => $this->ringkasan(),
            'peringatan' => array_values(array_unique($this->peringatan)),
        ];
    }

    /**
     * Lampiran di dalam Word: sejak tulisan "LAMPIRAN" sampai akhir dokumen. Setiap kepala lampiran memulai baris baru;
     * kepala dan tanda tangannya dibuang karena dibuat otomatis.
     */
    private function bacaLampiran(DOMElement $awal): void
    {
        $bagian = [];
        for ($node = $awal; $node; $node = $node->nextSibling) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            if (! $bagian || preg_match('/^LAMPIRAN(\s+[IVXLC]+|\s+\d+)?\s*(:|$|PERATURAN|KEPUTUSAN)/u', $this->bersih($node->textContent))) {
                $bagian[] = [];
            }
            $bagian[array_key_last($bagian)][] = $node;
        }

        $this->bagian = 'lampiran';
        foreach ($bagian as $nodes) {
            $this->baruBaris('lampiran', null, '');
            $this->tambahLampiran($nodes);
        }
    }

    /** Menambah isi ke baris lampiran terakhir. */
    private function tambahLampiran(array $nodes): void
    {
        $terakhir = array_key_last($this->baris);
        if ($terakhir === null || $this->baris[$terakhir]['bagian'] !== 'lampiran') {
            $this->baruBaris('lampiran', null, '');
            $terakhir = array_key_last($this->baris);
        }

        $judul = collect($this->baris)->where('bagian', 'judul')->pluck('teks')->implode(' ');
        $tentang = preg_match('/\bTENTANG\b(.*)$/isu', $judul, $m) ? $m[1] : $judul;

        $this->baris[$terakhir]['format'] = NaskahBaris::FORMAT_HTML;
        $this->baris[$terakhir]['teks'] .= HtmlLampiran::tanpaKepalaTtd($this->pembacaLampiran->html($nodes), $tentang);

        if ($this->pembacaLampiran->gambar > 0) {
            $this->peringatan[] = 'Gambar di lampiran tidak ikut terbaca.';
        }
    }

    /**
     * Baris teks satu per satu, supaya nomor otomatis dihitung berurutan sampai lampiran.
     *
     * [teks paragraf, petunjuk bagian dari kolom Materi, elemennya di naskah biasa, sel Rumusan bila barisnya lampiran]
     *
     * @return Generator<array{0: string, 1: ?string, 2: ?DOMElement, 3: ?DOMElement}>
     */
    private function barisTeks(string $path, ?string $bentuk): Generator
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File bukan dokumen Word (.docx) yang valid.');
        }

        try {
            $utama = PembacaLampiranWord::ambilXml($zip, 'word/document.xml')
                ?? throw new RuntimeException('Isi dokumen Word tidak ditemukan.');
            $gaya = PembacaLampiranWord::ambilXml($zip, 'word/styles.xml');
            $this->penomoran = new PenomoranWord(PembacaLampiranWord::ambilXml($zip, 'word/numbering.xml'), $gaya);
        } finally {
            $zip->close();
        }

        $this->xpath = new DOMXPath($utama);
        $this->xpath->registerNamespace('w', self::W);

        $this->pembacaLampiran = new PembacaLampiranWord();
        $this->pembacaLampiran->siapkan($this->xpath, $gaya, $this->penomoran, FormatDokumen::lebarIsiPx($this->jenis));

        // Pada file matriks, teks di luar tabel hanya kepala dan tanda tangan matriks itu sendiri.
        foreach ($bentuk === self::BENTUK_NASKAH ? [] : $this->xpath->query('/w:document/w:body/w:tbl') as $tabel) {
            if ($kepala = $this->kepalaMatriks($tabel)) {
                yield from $this->barisMatriks($tabel, $kepala);

                return;
            }
        }

        if ($bentuk === self::BENTUK_MATRIKS) {
            throw new RuntimeException('Tabel matriks dengan kolom "Rumusan" tidak ditemukan. Pilih "Naskah Rancangan" bila file berupa naskah biasa.');
        }

        foreach ($this->xpath->query('/w:document/w:body/*') as $node) {
            if ($node->localName === 'p') {
                yield [$this->teksParagraf($node), null, $node, null];
            } elseif ($node->localName === 'tbl') {
                foreach ($this->barisTabel($node) as [$teks]) {
                    yield [$teks, null, $node, null];
                }
            }
        }
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

    /** Kolom Rumusan matriks dibaca per paragraf; kolom Materi menjadi petunjuk bagian. */
    private function barisMatriks(DOMElement $tabel, array $kepala): Generator
    {
        $lampiran = false;

        foreach ($this->xpath->query('./w:tr', $tabel) as $r => $tr) {
            $sel = $this->selGrid($tr);
            $pertama = $sel === [] ? '' : $this->bersih($sel[array_key_first($sel)]->textContent);

            // Lewati kepala tabel dan baris nomor kolom (1), (2), (3).
            if ($r <= $kepala['baris'] || preg_match('/^\(\d+\)$/', $pertama)) {
                continue;
            }

            $petunjuk = $kepala['materi'] !== null && isset($sel[$kepala['materi']]) ? $this->bersih($sel[$kepala['materi']]->textContent) : null;

            // Lampiran dibaca beserta formatnya, bukan per paragraf.
            $bagian = $petunjuk ? $this->bagianDariPetunjuk($petunjuk) : null;
            $lampiran = $bagian ? $bagian === 'lampiran' : $lampiran;
            if ($lampiran) {
                yield ['', $petunjuk ?: null, null, $sel[$kepala['rumusan']] ?? null];

                continue;
            }

            $paragraf = $this->paragrafSel($sel[$kepala['rumusan']] ?? null);
            $nomor = $kepala['nomor'] !== null && isset($sel[$kepala['nomor']]) ? $this->nomorKolom($sel[$kepala['nomor']]->textContent) : null;

            if ($nomor !== null && $paragraf !== [] && TataLetakNaskah::nomorBaris($paragraf[0])[0] === null) {
                $paragraf[0] = "{$nomor} {$paragraf[0]}";
            }

            foreach ($paragraf as $i => $teks) {
                yield [$teks, $i === 0 ? ($petunjuk ?: null) : null, null, null];
            }
            if ($petunjuk && $paragraf === []) {
                yield ['', $petunjuk, null, null];
            }
        }
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

    private function awalanNomor(DOMElement $p): string
    {
        $nomor = $this->penomoran->nomor($p, $this->xpath);

        return $nomor && $nomor['fmt'] !== 'bullet' ? $nomor['teks'] . ' ' : '';
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
            $this->peringatan[] = 'Lampiran di dalam matriks tidak dibaca. Ambil lampirannya dari file Word atau PDF di tab Naskah.';

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
