<?php

namespace App\Services;

use App\Models\Naskah;
use App\Models\NaskahBaris;
use App\Support\TataLetakNaskah;
use App\Support\TemplateNaskah;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Menghasilkan naskah (Word dan PDF) dan matriks (PDF) dari baris matriks.
 * Nomor, tanggal, dan tanda tangan sengaja kosong karena ditandatangani basah.
 */
class CetakNaskah
{
    /**
     * Kertas F4 (Permendagri 80/2015 Pasal 167) dalam satuan twip (1 inci = 1440).
     * Tepi 3/2/2/2,5 cm mengikuti batas paling sedikit pada Pasal 34 Perbup HST 12/2025. Pasal itu
     * ditujukan bagi naskah korespondensi dan khusus; belum ada angka khusus untuk produk hukum.
     */
    private const F4_LEBAR = 12240;
    private const F4_TINGGI = 18720;
    private const TEPI_KIRI = 1701;
    private const TEPI_KANAN = 1134;
    private const TEPI_ATAS = 1134;
    private const TEPI_BAWAH = 1417;
    private const LEBAR_ISI = self::F4_LEBAR - self::TEPI_KIRI - self::TEPI_KANAN;
    private const INDEN = 567;

    /** Lebar blok tanda tangan: baris jabatan terpanjang pada Bookman Old Style 12, supaya nama rata tengah terhadapnya. */
    private const KIRI_DITETAPKAN = 4536;
    private const LEBAR_DITETAPKAN = 3971;
    private const LEBAR_DIUNDANGKAN = 4576;

    /** Kertas F4 dalam satuan point untuk DomPDF. */
    private const F4_POINT = [0, 0, 612, 936];

    /** Batas tinggi satu baris tabel matriks, jauh di bawah kapasitas satu halaman mendatar. */
    private const MATRIKS_MAKS_BARIS = 22;

    /** Gambar lambang opsional: kop tetap tercetak tanpa gambar bila file ini belum ada. */
    public static function lambang(): ?string
    {
        $path = public_path('assets/img/garuda.png');

        return is_file($path) ? $path : null;
    }

    /** @return string path file sementara; pemanggil yang menghapusnya */
    public function wordNaskah(Naskah $naskah): string
    {
        return $this->tulisWord("{$naskah->nomor} - {$naskah->tentang}", TataLetakNaskah::susun($naskah, $naskah->baris()->get()));
    }

    /** Template kosong untuk PD: bila ditulis mengikuti ini, pembacaan Word menjadi akurat. */
    public function wordTemplate(string $jenis): string
    {
        $naskah = new Naskah(['jenis' => $jenis]);

        $baris = collect(TemplateNaskah::kerangka($jenis, '...'))->map(function (array $item, int $i) {
            $contoh = match (true) {
                filled($item['teks']) => $item['teks'],
                str_starts_with((string) $item['label'], 'Pasal') => "(1) ...\n(2) ...",
                default => '...',
            };

            return (new NaskahBaris(['bagian' => $item['bagian'], 'label' => $item['label'], 'rumusan_awal' => $contoh]))
                ->forceFill(['id' => $i + 1, 'status' => NaskahBaris::BELUM]);
        });

        return $this->tulisWord("Template {$jenis}", TataLetakNaskah::susun($naskah, $baris));
    }

    private function tulisWord(string $judul, array $susunan): string
    {
        // Tanda & dan < di rumusan harus lolos sebagai teks, bukan merusak XML dokumen.
        Settings::setOutputEscapingEnabled(true);

        $word = new PhpWord();
        $word->setDefaultFontName('Bookman Old Style');
        $word->setDefaultFontSize(12);
        $word->getDocInfo()->setTitle($judul)->setCreator('SIFKUMDA');

        $halaman = $word->addSection([
            'pageSizeW' => self::F4_LEBAR,
            'pageSizeH' => self::F4_TINGGI,
            'marginLeft' => self::TEPI_KIRI,
            'marginRight' => self::TEPI_KANAN,
            'marginTop' => self::TEPI_ATAS,
            'marginBottom' => self::TEPI_BAWAH,
        ]);

        if ($lambang = self::lambang()) {
            $halaman->addImage($lambang, ['width' => 70, 'alignment' => Jc::CENTER]);
        }
        $halaman->addText(TataLetakNaskah::KOP, ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER, 'spaceAfter' => 240]);

        foreach ($susunan as $blok) {
            match ($blok['tipe']) {
                'tengah' => $this->wordTengah($halaman, $blok),
                'daftar' => $this->wordDaftar($halaman, $blok),
                'diktum' => $this->wordDiktum($halaman, $blok),
                'penutup' => $this->wordPenutup($halaman, $blok),
                default => $this->wordTeks($halaman, $blok),
            };
        }

        $path = tempnam(sys_get_temp_dir(), 'naskah');
        IOFactory::createWriter($word, 'Word2007')->save($path);

        return $path;
    }

    private function wordTengah(Section $halaman, array $blok): void
    {
        foreach ($blok['baris'] as $i => $baris) {
            $halaman->addText($baris, null, [
                'alignment' => Jc::CENTER,
                'spaceBefore' => $i === 0 ? 240 : 0,
                'spaceAfter' => $i === array_key_last($blok['baris']) ? 120 : 0,
                'keepNext' => true,
            ]);
        }
    }

    private function wordDaftar(Section $halaman, array $blok): void
    {
        [$lebarLabel, $lebarTitik, $lebarNomor] = [1531, 284, 454];
        $adaNomor = (bool) array_filter(array_column($blok['butir'], 'nomor'));
        $lebarIsi = self::LEBAR_ISI - $lebarLabel - $lebarTitik - ($adaNomor ? $lebarNomor : 0);

        $tabel = $halaman->addTable(['layout' => 'fixed', 'cellMargin' => 0]);

        foreach ($blok['butir'] as $i => $butir) {
            $baris = $tabel->addRow();
            $baris->addCell($lebarLabel)->addText($i === 0 ? $blok['label'] : '');
            $baris->addCell($lebarTitik)->addText($i === 0 ? ':' : '');
            if ($adaNomor) {
                $baris->addCell($lebarNomor)->addText((string) $butir['nomor']);
            }
            $this->wordIsi($baris->addCell($lebarIsi), $butir['isi']);
        }

        $halaman->addTextBreak(1, null, ['spaceAfter' => 0]);
    }

    /**
     * Kolom label, titik dua, lalu kolom nomor butir per tingkat. Baris yang tidak memakai semua
     * kolom menggabungkan sisanya, dengan alasan yang sama seperti wordButir().
     */
    private function wordDiktum(Section $halaman, array $blok): void
    {
        [$lebarLabel, $lebarTitik] = [1531, 284];
        $lebarSisa = self::LEBAR_ISI - $lebarLabel - $lebarTitik;

        $bernomor = array_filter(array_merge(...array_column($blok['butir'], 'baris')), fn ($b) => $b['nomor'] !== null && ! isset($b['tabel']));
        $kolomNomor = $bernomor ? max(array_column($bernomor, 'tingkat')) + 1 : 0;

        $tabel = $halaman->addTable(['layout' => 'fixed', 'cellMargin' => 0]);

        $selPenuh = function ($baris) use ($lebarSisa, $kolomNomor) {
            return $baris->addCell($lebarSisa, $kolomNomor ? ['gridSpan' => $kolomNomor + 1] : []);
        };

        $pembuka = $tabel->addRow();
        $pembuka->addCell($lebarLabel)->addText($blok['pembuka']);
        $pembuka->addCell($lebarTitik)->addText(':');
        $selPenuh($pembuka)->addText('');

        foreach ($blok['butir'] as $butir) {
            foreach ($butir['baris'] as $j => $isi) {
                $tr = $tabel->addRow();
                $tr->addCell($lebarLabel)->addText($j === 0 ? (string) $butir['nomor'] : '');
                $tr->addCell($lebarTitik)->addText($j === 0 ? ':' : '');

                if (isset($isi['tabel'])) {
                    $this->wordTabel($selPenuh($tr), $isi['tabel'], $lebarSisa);
                } elseif ($isi['nomor'] === null) {
                    $selPenuh($tr)->addText($isi['isi'], null, ['alignment' => Jc::BOTH, 'spaceAfter' => 60]);
                } else {
                    for ($i = 0; $i < $isi['tingkat']; $i++) {
                        $tr->addCell(self::INDEN);
                    }

                    $sisaKolom = $kolomNomor - $isi['tingkat'];
                    $tr->addCell(self::INDEN)->addText($isi['nomor']);
                    $tr->addCell($lebarSisa - ($isi['tingkat'] + 1) * self::INDEN, $sisaKolom > 1 ? ['gridSpan' => $sisaKolom] : [])
                        ->addText($isi['isi'], null, ['alignment' => Jc::BOTH, 'spaceAfter' => 60]);
                }
            }
        }

        $halaman->addTextBreak(1, null, ['spaceAfter' => 0]);
    }

    private function wordIsi($sel, string $isi): void
    {
        foreach (preg_split('/\R/u', $isi) as $baris) {
            $sel->addText($baris, null, ['alignment' => Jc::BOTH, 'spaceAfter' => 60]);
        }
    }

    private function wordTeks(Section $halaman, array $blok): void
    {
        $butir = [];

        foreach ($blok['baris'] as $baris) {
            if ($baris['nomor'] !== null && ! isset($baris['tabel'])) {
                $butir[] = $baris;

                continue;
            }

            $this->wordButir($halaman, $butir);
            $butir = [];

            if (isset($baris['tabel'])) {
                $this->wordTabel($halaman, $baris['tabel']);
            } else {
                $halaman->addText($baris['isi'], null, ['alignment' => Jc::BOTH, 'spaceAfter' => 60]);
            }
        }

        $this->wordButir($halaman, $butir);
    }

    /**
     * Ayat dan butir digambar sebagai tabel tanpa garis supaya teks lanjutannya rata dengan nomor.
     * Satu kolom per tingkat, selebar kolom nomor. Baris yang tingkatnya lebih dangkal harus
     * menggabungkan kolom sisanya: tanpa itu Word memakai lebar sel terakhirnya untuk seluruh
     * kolom, sehingga isi butir di bawahnya terdorong keluar halaman.
     *
     * @param  list<array{tingkat: int, nomor: string, isi: string}>  $butir
     */
    private function wordButir(Section $halaman, array $butir): void
    {
        if ($butir === []) {
            return;
        }

        $terdalam = max(array_column($butir, 'tingkat'));
        $tabel = $halaman->addTable(['layout' => 'fixed', 'cellMargin' => 0]);

        foreach ($butir as $baris) {
            $tingkat = $baris['tingkat'];
            $sisaKolom = $terdalam - $tingkat + 1;
            $tr = $tabel->addRow();

            for ($i = 0; $i < $tingkat; $i++) {
                $tr->addCell(self::INDEN);
            }

            $tr->addCell(self::INDEN)->addText($baris['nomor']);
            $tr->addCell(self::LEBAR_ISI - ($tingkat + 1) * self::INDEN, $sisaKolom > 1 ? ['gridSpan' => $sisaKolom] : [])
                ->addText($baris['isi'], null, ['alignment' => Jc::BOTH, 'spaceAfter' => 60]);
        }
    }

    /**
     * @param  Section|Cell  $wadah
     * @param  array<int, array<int, string>>  $isi
     */
    private function wordTabel(Section|Cell $wadah, array $isi, int $lebarTersedia = self::LEBAR_ISI): void
    {
        $kolom = max(array_map('count', $isi));
        $lebar = $this->lebarKolomTabel($isi, $kolom, $lebarTersedia);
        $tabel = $wadah->addTable(['layout' => 'fixed', 'borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 60]);

        foreach ($isi as $sel) {
            // Satu baris tabel tidak boleh terbelah di pergantian halaman.
            $tr = $tabel->addRow(null, ['cantSplit' => true]);
            for ($i = 0; $i < $kolom; $i++) {
                $tr->addCell($lebar[$i])->addText($sel[$i] ?? '', ['size' => 11], ['spaceAfter' => 0]);
            }
        }

        // Di dalam sel, PhpWord sudah menutup tabel dengan paragraf yang diwajibkan Word.
        if ($wadah instanceof Section) {
            $wadah->addTextBreak(1, null, ['spaceAfter' => 0]);
        }
    }

    /**
     * Lebar kolom sebanding dengan isi terpanjangnya, dibatasi supaya kolom "No" tidak
     * terlalu sempit dan kolom berisi kalimat panjang tidak menghabiskan seluruh lebar.
     *
     * @return list<int>
     */
    private function lebarKolomTabel(array $isi, int $kolom, int $lebarTersedia): array
    {
        $bobot = [];

        for ($i = 0; $i < $kolom; $i++) {
            $terpanjang = max(array_map(fn (array $sel) => mb_strlen($sel[$i] ?? ''), $isi));
            $bobot[] = max(4, min($terpanjang, 30));
        }

        return array_map(fn (int $b) => (int) ($lebarTersedia * $b / array_sum($bobot)), $bobot);
    }

    private function wordPenutup(Section $halaman, array $blok): void
    {
        $halaman->addTextBreak(1);
        $this->wordBlokJabatan($halaman, $blok['ditetapkan'], self::KIRI_DITETAPKAN, self::LEBAR_DITETAPKAN);

        if ($blok['diundangkan']) {
            $halaman->addTextBreak(1);
            $this->wordBlokJabatan($halaman, $blok['diundangkan'], 0, self::LEBAR_DIUNDANGKAN);
        }

        if ($blok['berita']) {
            $halaman->addTextBreak(1);
            $halaman->addText($blok['berita'], null, ['spaceAfter' => 0]);
        }

        if ($blok['tembusan']) {
            $halaman->addTextBreak(1);
            foreach ($blok['tembusan'] as $baris) {
                $halaman->addText($baris, null, ['keepNext' => true, 'spaceAfter' => 0]);
            }
        }
    }

    /** Tempat dan tanggal rata kiri blok; jabatan dan nama rata tengah terhadap baris jabatan terpanjang. */
    private function wordBlokJabatan(Section $halaman, array $baris, int $kiri, int $lebar): void
    {
        foreach ($baris as $teks) {
            $halaman->addText($teks, null, [
                'indentation' => ['left' => $kiri, 'right' => self::LEBAR_ISI - $kiri - $lebar],
                'alignment' => TataLetakNaskah::barisTempatTanggal($teks) ? Jc::START : Jc::CENTER,
                'keepNext' => true,
                'spaceAfter' => 0,
            ]);
        }
    }

    /**
     * DomPDF tidak bisa memenggal baris tabel: baris yang lebih tinggi dari satu halaman terpotong
     * dan isinya hilang tanpa peringatan. Karena itu teks panjang dipecah dulu per paragraf.
     *
     * @param  int  $hurufPerBaris  perkiraan huruf per baris pada lebar kolomnya
     * @return array<int, string> potongan yang masing-masing muat dalam satu halaman
     */
    private static function potong(string $teks, int $hurufPerBaris): array
    {
        if ($teks === '') {
            return [];
        }

        $hasil = [];
        $sekarang = [];
        $terisi = 0;

        $tutup = function () use (&$hasil, &$sekarang, &$terisi) {
            if ($sekarang) {
                $hasil[] = implode("\n", $sekarang);
            }
            $sekarang = [];
            $terisi = 0;
        };

        foreach (preg_split('/\R/u', $teks) as $paragraf) {
            // Satu paragraf yang melebihi batas pun dipenggal di antara kata; baris tabel dipenggal per sel.
            $penggalan = str_starts_with(ltrim($paragraf), '|')
                ? self::penggalBarisTabel($paragraf, $hurufPerBaris)
                : explode("\n", wordwrap($paragraf, $hurufPerBaris * self::MATRIKS_MAKS_BARIS, "\n"));

            foreach ($penggalan as $bagian) {
                $tinggi = str_starts_with(ltrim($bagian), '|')
                    ? self::tinggiBarisTabel($bagian, $hurufPerBaris)
                    : max(1, (int) ceil(mb_strlen($bagian) / $hurufPerBaris));

                if ($terisi + $tinggi > self::MATRIKS_MAKS_BARIS) {
                    $tutup();
                }

                $sekarang[] = $bagian;
                $terisi += $tinggi;
            }
        }
        $tutup();

        return $hasil;
    }

    private static function hurufPerSel(int $hurufPerBaris, int $jumlahKolom): int
    {
        return max(4, (int) floor($hurufPerBaris / max(1, $jumlahKolom)));
    }

    /** Tinggi baris tabel mengikuti selnya yang paling panjang pada lebar kolom yang sempit. */
    private static function tinggiBarisTabel(string $baris, int $hurufPerBaris): int
    {
        $sel = TataLetakNaskah::selTabel($baris) ?? [];
        $lebar = self::hurufPerSel($hurufPerBaris, count($sel));

        return max(1, ...array_map(fn (string $isi) => (int) ceil(mb_strlen($isi) / $lebar), $sel ?: ['']));
    }

    /**
     * Baris tabel yang lebih tinggi dari batas dipecah menjadi beberapa baris tabel,
     * dengan alasan yang sama seperti potong(): isinya hilang bila dibiarkan melebihi halaman.
     *
     * @return list<string>
     */
    private static function penggalBarisTabel(string $baris, int $hurufPerBaris): array
    {
        if (self::tinggiBarisTabel($baris, $hurufPerBaris) <= self::MATRIKS_MAKS_BARIS) {
            return [$baris];
        }

        $sel = TataLetakNaskah::selTabel($baris);
        $batas = self::hurufPerSel($hurufPerBaris, count($sel)) * self::MATRIKS_MAKS_BARIS;
        $penggal = array_map(fn (string $isi) => explode("\n", wordwrap($isi, $batas, "\n", true)), $sel);
        $hasil = [];

        for ($i = 0; $i < max(array_map('count', $penggal)); $i++) {
            $hasil[] = '| ' . implode(' | ', array_map(fn (array $isi) => $isi[$i] ?? '', $penggal)) . ' |';
        }

        return $hasil;
    }

    public function pdfNotaDinas(Naskah $naskah): string
    {
        $nota = $naskah->notaDinas()->with('tandaTangan')->firstOrFail();

        return Pdf::loadView('pdf.v2.nota-dinas', ['naskah' => $naskah, 'nota' => $nota])->setPaper('A4', 'portrait')->output();
    }

    public function pdfNaskah(Naskah $naskah): string
    {
        return Pdf::loadView('pdf.v2.naskah', [
            'naskah' => $naskah,
            'blok' => TataLetakNaskah::susun($naskah, $naskah->baris()->get()),
            'lambang' => self::lambang(),
        ])->setPaper(self::F4_POINT, 'portrait')->output();
    }

    /**
     * Matriks F4 mendatar. Nomor nota dinas dan tanda tangan Kepala Bagian Hukum
     * terisi setelah nota dinas diterbitkan; sebelum itu matriks bertanda draf.
     */
    public function pdfMatriks(Naskah $naskah): string
    {
        $baris = $naskah->baris()->get();
        $penanda = TemplateNaskah::penanda($naskah, $baris);
        $nomorMateri = 0;
        $sebelumnya = null;

        $isi = $baris->flatMap(function (NaskahBaris $item) use ($penanda, &$nomorMateri, &$sebelumnya) {
            $bagianBaru = $item->bagian !== $sebelumnya;
            $sebelumnya = $item->bagian;

            $no = $bagianBaru ? ++$nomorMateri . '.' : '';
            $materi = $bagianBaru ? TemplateNaskah::BAGIAN[$item->bagian] : '';
            $sempurna = match ($item->status) {
                NaskahBaris::TETAP => 'Tetap;',
                NaskahBaris::DIHAPUS => 'Dihapus;',
                default => (string) $item->rumusan_penyempurnaan,
            };

            $rumusan = self::potong((string) $item->rumusan_awal, 72);
            $penyempurnaan = self::potong($sempurna, 50);
            $keterangan = self::potong((string) $item->keterangan, 28);

            // Sisa pasal panjang berlanjut di baris tabel berikutnya, tanpa nomor dan label lagi.
            return collect(range(0, max(count($rumusan), count($penyempurnaan), count($keterangan), 1) - 1))
                ->map(fn (int $i) => [
                    'no' => $i === 0 ? $no : '',
                    'materi' => $i === 0 ? $materi : '',
                    'penanda' => $i === 0 ? $penanda[$item->id] : null,
                    // Nomor otomatis (a., 1., KESATU) sebaris dengan teksnya; label BAB/Pasal di baris sendiri.
                    'sebaris' => blank($item->label),
                    'rumusan' => $rumusan[$i] ?? '',
                    'penyempurnaan' => $penyempurnaan[$i] ?? '',
                    'keterangan' => $keterangan[$i] ?? '',
                ]);
        });

        // Saat dibuka kembali untuk koreksi, matriks kembali bertanda draf sampai disetujui ulang.
        $nota = $naskah->tahap->terkunci() ? $naskah->notaDinas()->with('tandaTangan')->first() : null;

        return Pdf::loadView('pdf.v2.matriks', [
            'naskah' => $naskah,
            'nota' => $nota,
            'isi' => $isi,
            'singkatan' => $naskah->jenis === 'Keputusan Bupati' ? 'Rancangan Keputusan' : 'Raperbup',
            'judul' => $naskah->jenis === 'Keputusan Bupati' ? 'KEPUTUSAN BUPATI' : 'PERATURAN BUPATI',
        ])->setPaper(self::F4_POINT, 'landscape')->output();
    }
}
