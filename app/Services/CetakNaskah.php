<?php

namespace App\Services;

use App\Models\Naskah;
use App\Models\NaskahBaris;
use App\Models\NaskahNotaDinas;
use App\Models\PerangkatDaerah;
use App\Models\TandaTangan;
use App\Models\User;
use App\Support\FormatDokumen;
use App\Support\TandaTanganPeneliti;
use App\Support\TataLetakNaskah;
use App\Support\TemplateNaskah;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Dompdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\ComplexType\TblWidth;
use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Element\Footer;
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
     * Kertas, tepi, huruf, kop, dan paraf naskah diatur di menu Format Dokumen (App\Support\FormatDokumen).
     * Satuan Word adalah twip (1 inci = 1440), satuan DomPDF adalah point (1 inci = 72).
     */
    private array $f = [];

    /** Lebar bidang tulis naskah yang sedang dicetak, dalam twip. */
    private int $lebarIsi = 0;

    /** File font di resources/fonts => tebal dan gaya huruf. */
    private const BOOKMAN = [
        'BOOKOS.TTF' => ['normal', 'normal'],
        'BOOKOSB.TTF' => ['bold', 'normal'],
        'BOOKOSI.TTF' => ['normal', 'italic'],
        'BOOKOSBI.TTF' => ['bold', 'italic'],
    ];

    private const INDEN = 567;

    /** Lebar blok tanda tangan: baris jabatan terpanjang pada Bookman Old Style 12, supaya nama rata tengah terhadapnya. */
    private const LEBAR_DITETAPKAN = 3971;
    private const LEBAR_DIUNDANGKAN = 4576;

    /** Kertas F4 dalam satuan point untuk DomPDF. */
    private const F4_POINT = [0, 0, 612, 936];

    /** Batas tinggi satu baris tabel matriks, jauh di bawah kapasitas satu halaman mendatar. */
    private const MATRIKS_MAKS_BARIS = 22;

    /** @return string path file sementara; pemanggil yang menghapusnya */
    public function wordNaskah(Naskah $naskah): string
    {
        return $this->tulisWord("{$naskah->nomor_tampil} - {$naskah->tentang}", TataLetakNaskah::susun($naskah, $naskah->baris()->get()), FormatDokumen::naskah($naskah->jenis));
    }

    /** Template kosong untuk PD: bila ditulis mengikuti ini, pembacaan Word menjadi akurat. */
    public function wordTemplate(string $jenis, ?array $format = null): string
    {
        return $this->tulisWord("Template {$jenis}", TataLetakNaskah::susun(new Naskah(['jenis' => $jenis]), self::barisContoh($jenis)), $format ?? FormatDokumen::naskah($jenis));
    }

    /** Baris kerangka naskah berisi titik-titik, untuk template dan contoh di menu Format Dokumen. */
    private static function barisContoh(string $jenis): Collection
    {
        return collect(TemplateNaskah::kerangka($jenis, '...'))->map(function (array $item, int $i) {
            $contoh = match (true) {
                filled($item['teks']) => $item['teks'],
                str_starts_with((string) $item['label'], 'Pasal') => "(1) ...\n(2) ...",
                default => '...',
            };

            return (new NaskahBaris(['bagian' => $item['bagian'], 'label' => $item['label'], 'rumusan_awal' => $contoh]))
                ->forceFill(['id' => $i + 1, 'status' => NaskahBaris::BELUM]);
        });
    }

    private function tulisWord(string $judul, array $susunan, array $format): string
    {
        // Tanda & dan < di rumusan harus lolos sebagai teks, bukan merusak XML dokumen.
        Settings::setOutputEscapingEnabled(true);

        $format = self::lebarLabel($format, $susunan);
        $this->f = $format;
        $m = $format['margin'];
        $this->lebarIsi = $this->twip($format['kertas']['lebar']) - $this->twip($m['kiri']) - $this->twip($m['kanan']);

        $word = new PhpWord();
        $word->setDefaultFontName($format['huruf']['nama']);
        $word->setDefaultFontSize((float) $format['huruf']['ukuran']);
        $word->setDefaultParagraphStyle(['lineHeight' => (float) $format['huruf']['spasi']]);
        $word->getDocInfo()->setTitle($judul)->setCreator('SIFKUMDA');

        // Tepi atas halaman berikutnya; halaman pertama ditambah ruang kop (lihat wordKop()).
        $halaman = $word->addSection([
            'pageSizeW' => $this->twip($format['kertas']['lebar']),
            'pageSizeH' => $this->twip($format['kertas']['tinggi']),
            'marginLeft' => $this->twip($m['kiri']),
            'marginRight' => $this->twip($m['kanan']),
            'marginTop' => $this->twip($m['atas']),
            'marginBottom' => $this->twip($m['bawah']),
        ]);

        $this->wordKop($halaman);

        // Kotak paraf di halaman pertama; halaman tanda tangan mendapatkannya di bawah penutup.
        if ($this->adaParaf()) {
            $this->wordParaf($halaman->addFooter(Footer::FIRST));
        }

        foreach (self::gayaKonsiderans($susunan, $format) as $blok) {
            match ($blok['tipe']) {
                'tengah' => $this->wordTengah($halaman, $blok),
                'daftar' => $this->wordDaftar($halaman, $blok),
                'diktum' => $this->wordDiktum($halaman, $blok),
                'penutup' => $this->wordPenutup($halaman, $blok),
                'lampiran' => $this->wordKepalaLampiran($halaman, $blok),
                'ttd' => $this->wordTtdLampiran($halaman, $blok),
                default => $this->wordTeks($halaman, $blok),
            };
        }

        $path = tempnam(sys_get_temp_dir(), 'naskah');
        IOFactory::createWriter($word, 'Word2007')->save($path);

        return $path;
    }

    private function twip(float|int|string $cm): int
    {
        return FormatDokumen::cmKeTwip((float) $cm);
    }

    /**
     * Halaman pertama: judul dimulai pada jarak "atas_pertama" dari tepi kertas. Ruang di atasnya
     * (atas_pertama dikurangi tepi atas biasa) berisi lambang dan teks kop, atau dibiarkan kosong
     * bila kop dimatikan karena naskah dicetak di kertas kop emas.
     */
    private function wordKop(Section $halaman): void
    {
        $kop = $this->f['kop'];
        $ruang = max(0.0, (float) $this->f['margin']['atas_pertama'] - (float) $this->f['margin']['atas']);
        $terpakai = 0.0;

        if ($kop['tampil'] && ($lambang = FormatDokumen::pathLambang($this->f)) && $kop['lebar_lambang'] > 0) {
            [$w, $h] = getimagesize($lambang);
            $lebar = FormatDokumen::cmKePoint((float) $kop['lebar_lambang']);
            $halaman->addImage($lambang, ['width' => $lebar, 'height' => $lebar * $h / $w, 'alignment' => Jc::CENTER]);
            $terpakai += (float) $kop['lebar_lambang'] * $h / $w + 0.2;
        }

        $teks = $kop['tampil'] ? trim((string) $kop['teks']) : '';
        $tinggiTeks = $teks !== '' ? (float) $kop['ukuran'] * 1.25 / 72 * 2.54 : 0.0;
        $sisa = $ruang > 0 ? max(0.0, $ruang - $terpakai - $tinggiTeks) : 0.42;

        if ($teks !== '') {
            $halaman->addText($teks, ['bold' => true, 'size' => (float) $kop['ukuran']], ['alignment' => Jc::CENTER, 'spaceAfter' => $this->twip($sisa)]);
        } elseif ($sisa > 0) {
            // Paragraf 1 pt yang jarak sesudahnya mendorong judul ke posisinya.
            $halaman->addText('', ['size' => 1], ['spaceAfter' => $this->twip($sisa), 'lineHeight' => 1.0]);
        }
    }

    private function adaParaf(): bool
    {
        return $this->f['paraf']['tampil'] && $this->jabatanParaf() !== [];
    }

    private function jabatanParaf(): array
    {
        return array_values(array_filter(array_map('trim', $this->f['paraf']['jabatan'] ?? []), 'strlen'));
    }

    /** Kotak paraf hierarki: kolom jabatan dan kolom paraf yang dikosongkan untuk diisi tangan. */
    private function wordParaf(Section|Footer $wadah): void
    {
        $ukuran = min(11.0, (float) $this->f['huruf']['ukuran']);
        $tabel = $wadah->addTable(['layout' => 'fixed', 'borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 60]);
        $paragraf = ['spaceAfter' => 0, 'lineHeight' => 1.3];

        $judul = $tabel->addRow();
        $judul->addCell($this->twip(self::LEBAR_JABATAN_PARAF))->addText((string) $this->f['paraf']['judul'], ['size' => $ukuran], $paragraf + ['alignment' => Jc::CENTER]);
        $judul->addCell($this->twip(1.8));

        foreach ($this->jabatanParaf() as $jabatan) {
            $tr = $tabel->addRow();
            $tr->addCell($this->twip(self::LEBAR_JABATAN_PARAF))->addText($jabatan, ['size' => $ukuran], $paragraf);
            $tr->addCell($this->twip(1.8));
        }
    }

    /**
     * Gaya titik dua konsiderans. "rapat" (pedoman SK): "Menimbang:", "Menetapkan:", dan "MEMUTUSKAN:"
     * tanpa spasi dan tanpa kolom titik dua; "kolom": "Menimbang :" dengan titik dua sejajar.
     */
    /** Lebar "Memperhatikan:" pada Bookman Old Style 12 (3,41 cm) ditambah sedikit ruang. */
    private const LEBAR_LABEL_MEMPERHATIKAN = 3.75;

    /**
     * Label Memperhatikan lebih panjang dari Menimbang dan Mengingat, jadi kolom labelnya dilebarkan
     * bila bagian itu ada. Kolom KESATU ikut melebar supaya semua isi tetap sejajar.
     */
    private static function lebarLabel(array $format, array $susunan): array
    {
        $adaMemperhatikan = collect($susunan)->contains(fn (array $b) => $b['tipe'] === 'daftar' && str_starts_with($b['label'], 'Memperhatikan'));

        if ($adaMemperhatikan) {
            $format['konsiderans']['lebar_label'] = max((float) $format['konsiderans']['lebar_label'], self::LEBAR_LABEL_MEMPERHATIKAN);
        }

        return $format;
    }

    private static function gayaKonsiderans(array $susunan, array $format): array
    {
        $rapat = ($format['konsiderans']['titik_dua'] ?? 'kolom') === 'rapat';

        return array_map(function (array $blok) use ($rapat) {
            $blok['rapat'] = $rapat;

            if (! $rapat) {
                return $blok;
            }

            match ($blok['tipe']) {
                'tengah' => $blok['baris'] = array_map(fn ($b) => preg_replace('/^MEMUTUSKAN\s*:$/u', 'MEMUTUSKAN:', $b), $blok['baris']),
                'daftar' => $blok['label'] .= ':',
                'diktum' => $blok['pembuka'] .= ':',
                default => null,
            };

            return $blok;
        }, $susunan);
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
        // Gaya rapat: titik dua menempel pada label, jadi kolom titik dua tidak ada.
        [$lebarLabel, $lebarTitik, $lebarNomor] = [$this->twip($this->f['konsiderans']['lebar_label']), $blok['rapat'] ? 0 : 284, 454];
        $adaNomor = (bool) array_filter(array_column($blok['butir'], 'nomor'));
        $lebarIsi = $this->lebarIsi - $lebarLabel - $lebarTitik - ($adaNomor ? $lebarNomor : 0);

        $tabel = $halaman->addTable(['layout' => 'fixed', 'cellMargin' => 0]);

        foreach ($blok['butir'] as $i => $butir) {
            $baris = $tabel->addRow();
            $baris->addCell($lebarLabel)->addText($i === 0 ? $blok['label'] : '');
            if (! $blok['rapat']) {
                $baris->addCell($lebarTitik)->addText($i === 0 ? ':' : '');
            }
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
        [$lebarLabel, $lebarTitik] = [$this->twip($this->f['konsiderans']['lebar_label']), 284];
        $lebarSisa = $this->lebarIsi - $lebarLabel - $lebarTitik;

        $bernomor = array_filter(array_merge(...array_column($blok['butir'], 'baris')), fn ($b) => $b['nomor'] !== null && ! isset($b['tabel']));
        $kolomNomor = $bernomor ? max(array_column($bernomor, 'tingkat')) + 1 : 0;

        $tabel = $halaman->addTable(['layout' => 'fixed', 'cellMargin' => 0]);

        $selPenuh = function ($baris) use ($lebarSisa, $kolomNomor) {
            return $baris->addCell($lebarSisa, $kolomNomor ? ['gridSpan' => $kolomNomor + 1] : []);
        };

        $pembuka = $tabel->addRow();
        $pembuka->addCell($lebarLabel)->addText($blok['pembuka']);
        $pembuka->addCell($lebarTitik)->addText($blok['rapat'] ? '' : ':');
        $selPenuh($pembuka)->addText('');

        foreach ($blok['butir'] as $butir) {
            foreach ($butir['baris'] as $j => $isi) {
                $tr = $tabel->addRow();
                $tr->addCell($lebarLabel)->addText($j === 0 ? (string) $butir['nomor'] : '');
                $tr->addCell($lebarTitik)->addText($j === 0 ? ':' : '');

                if (isset($isi['tabel'])) {
                    $this->wordTabel($selPenuh($tr), $isi, $lebarSisa);
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
                // Tabel menjorok sejajar dengan teks butir di atasnya.
                $jorok = $baris['tingkat'] * self::INDEN;
                $this->wordTabel($halaman, $baris, $this->lebarIsi - $jorok, $jorok);
            } else {
                $rata = ($baris['tengah'] ?? false) ? Jc::CENTER : Jc::BOTH;
                $halaman->addText($baris['isi'], null, ['alignment' => $rata, 'spaceAfter' => 60]);
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
            $tr->addCell($this->lebarIsi - ($tingkat + 1) * self::INDEN, $sisaKolom > 1 ? ['gridSpan' => $sisaKolom] : [])
                ->addText($baris['isi'], null, ['alignment' => Jc::BOTH, 'spaceAfter' => 60]);
        }
    }

    /**
     * Tabel isi naskah: rata kolom, baris judul (diulang di tiap halaman), dan judul kelompok
     * yang dibentangkan selebar tabel mengikuti TataLetakNaskah::susunTabel().
     *
     * @param  array{tabel: list<list<string>>, rata?: ?list<string>, judul?: bool}  $item
     */
    private function wordTabel(Section|Cell $wadah, array $item, ?int $lebarTersedia = null, int $jorok = 0): void
    {
        $lebarTersedia ??= $this->lebarIsi;
        $kolom = max(array_map('count', $item['tabel']));
        $lebar = $this->lebarKolomTabel($item['tabel'], $kolom, $lebarTersedia);
        $gaya = ['layout' => 'fixed', 'borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 60];
        $rata = ['left' => Jc::START, 'center' => Jc::CENTER, 'right' => Jc::END];

        if ($jorok > 0) {
            $gaya['indent'] = new TblWidth($jorok);
        }

        $tabel = $wadah->addTable($gaya);

        foreach (TataLetakNaskah::susunTabel($item) as $baris) {
            // Satu baris tabel tidak boleh terbelah di pergantian halaman.
            $tr = $tabel->addRow(null, ['cantSplit' => true, 'tblHeader' => $baris['judul']]);
            $i = 0;

            foreach ($baris['sel'] as $sel) {
                $lebarSel = array_sum(array_slice($lebar, $i, $sel['bentang']));
                // Isi sel rata tengah secara vertikal, seperti tabel pada produk hukum yang terbit.
                $tr->addCell($lebarSel, ['valign' => 'center'] + ($sel['bentang'] > 1 ? ['gridSpan' => $sel['bentang']] : []))
                    ->addText((string) $sel['isi'], ['size' => 11, 'bold' => $sel['tebal']], ['alignment' => $rata[$sel['rata']], 'spaceAfter' => 0]);
                $i += $sel['bentang'];
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
        $kiri = min($this->twip($this->f['penutup']['kiri']), $this->lebarIsi - 2000);
        $this->wordBlokJabatan($halaman, $blok['ditetapkan'], $kiri, min(self::LEBAR_DITETAPKAN, $this->lebarIsi - $kiri));

        if ($this->adaParaf()) {
            $halaman->addTextBreak(1);
            $this->wordParaf($halaman);
        }

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

    /** Lampiran mulai di halaman baru; kepalanya sejajar dengan blok tanda tangan penutup. */
    private function wordKepalaLampiran(Section $halaman, array $blok): void
    {
        $kiri = min($this->twip($this->f['penutup']['kiri']), $this->lebarIsi - 2000);

        foreach ($blok['kepala'] as $i => $baris) {
            $halaman->addText($baris, null, [
                'indentation' => ['left' => $kiri],
                'pageBreakBefore' => $i === 0,
                'keepNext' => true,
                'spaceAfter' => 0,
            ]);
        }

        $halaman->addTextBreak(1, null, ['spaceAfter' => 0]);
    }

    /** Tanda tangan penutup lampiran: jabatan dan nama, tanpa tempat dan tanggal. */
    private function wordTtdLampiran(Section $halaman, array $blok): void
    {
        $halaman->addTextBreak(1);
        $kiri = min($this->twip($this->f['penutup']['kiri']), $this->lebarIsi - 2000);
        $this->wordBlokJabatan($halaman, $blok['baris'], $kiri, min(self::LEBAR_DITETAPKAN, $this->lebarIsi - $kiri));
    }

    /** Tempat dan tanggal rata kiri blok; jabatan dan nama rata tengah terhadap baris jabatan terpanjang. */
    private function wordBlokJabatan(Section $halaman, array $baris, int $kiri, int $lebar): void
    {
        foreach ($baris as $teks) {
            $halaman->addText($teks, null, [
                'indentation' => ['left' => $kiri, 'right' => max(0, $this->lebarIsi - $kiri - $lebar)],
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
        $naskah->loadMissing('pemrakarsa.perangkatDaerah');

        return $this->pdfNota($naskah, $nota, FormatDokumen::nota());
    }

    /** Contoh nota dinas dari isian menu Format Dokumen, dengan data contoh. */
    public function pdfContohNota(array $format): string
    {
        $naskah = new Naskah([
            'jenis' => FormatDokumen::KEPBUP, 'tentang' => 'Contoh Judul Keputusan Bupati',
            'nomor_nota' => '400.10/123/DPMD/' . now()->year, 'tanggal_nota' => now(),
        ]);
        $naskah->setRelation('pemrakarsa', (new User(['nama_user' => 'Contoh']))
            ->setRelation('perangkatDaerah', new PerangkatDaerah(['nama_perangkat_daerah' => 'Dinas Contoh'])));

        $ttd = TandaTangan::where('status', 'Aktif')->first()
            ?? new TandaTangan(['nama_ttd' => 'NAMA KEPALA BAGIAN HUKUM', 'jabatan' => 'Pembina', 'nip_ttd' => '19xxxxxx xxxxxx x xxx']);
        $nota = (new NaskahNotaDinas([
            'nomor_inputan' => '123',
            'nomor_nota' => FormatDokumen::nomorNota($format['nomor'], '123', FormatDokumen::KEPBUP, now()->year),
            'tanggal_nota' => now(),
            'kepada' => 'Kepala Dinas Contoh',
        ]))->setRelation('tandaTangan', $ttd);

        return $this->pdfNota($naskah, $nota, $format);
    }

    private function pdfNota(Naskah $naskah, NaskahNotaDinas $nota, array $f): string
    {
        $isian = [
            '{jenis}' => (string) $naskah->jenis,
            '{tentang}' => (string) $naskah->tentang,
            '{perangkat_daerah}' => $naskah->pemrakarsa?->perangkatDaerah?->nama_perangkat_daerah ?? '...',
            '{nomor_nota_pd}' => $naskah->nomor_nota ?: '...',
            '{tanggal_nota_pd}' => $naskah->tanggal_nota?->translatedFormat('j F Y') ?? '...',
        ];
        $isi = fn (?string $teks) => trim(FormatDokumen::isi((string) $teks, $isian));
        $daftar = fn (array $baris) => array_values(array_filter(array_map($isi, $baris), 'strlen'));

        return Pdf::loadView('pdf.v2.nota-dinas', [
            'nota' => $nota,
            'f' => $f,
            'logo' => $f['kop']['tampil'] ? FormatDokumen::pathLogoNota($f) : null,
            'kop' => $f['kop']['tampil'] ? array_values(array_filter($f['kop']['baris'], fn ($b) => filled($b['teks'] ?? null))) : [],
            'perihal' => $isi($f['perihal']),
            'pembuka' => $isi($f['pembuka']),
            'butir' => $daftar($f['butir']),
            'ketentuan' => $daftar($f['ketentuan']),
            'penutup' => $isi($f['penutup']),
            'gambarTtd' => $f['ttd']['gambar'] ? $nota->pathTandaTangan() : null,
        ])->setPaper($this->kertas($f), 'portrait')->output();
    }

    /** @return array{0: int, 1: int, 2: float, 3: float} ukuran kertas dalam point untuk DomPDF */
    private function kertas(array $f): array
    {
        return [0, 0, FormatDokumen::cmKePoint((float) $f['kertas']['lebar']), FormatDokumen::cmKePoint((float) $f['kertas']['tinggi'])];
    }

    public function pdfNaskah(Naskah $naskah): string
    {
        return $this->pdfNaskahDari($naskah, $naskah->baris()->get(), FormatDokumen::naskah($naskah->jenis));
    }

    /** Contoh naskah berisi titik-titik dari isian menu Format Dokumen, sebelum disimpan. */
    public function pdfContohNaskah(string $jenis, array $format): string
    {
        return $this->pdfNaskahDari(new Naskah(['jenis' => $jenis]), self::barisContoh($jenis), $format);
    }

    /** Tinggi satu baris kotak paraf hierarki (cm), untuk ruang yang disisihkan di halaman pertama PDF. */
    private const BARIS_PARAF = 0.75;

    /** Lebar kolom jabatan kotak paraf (cm); kolom paraf 1,8 cm. */
    private const LEBAR_JABATAN_PARAF = 6.2;

    private function pdfNaskahDari(Naskah $naskah, Collection $baris, array $f): string
    {
        $susunan = TataLetakNaskah::susun($naskah, $baris);
        $f = self::lebarLabel($f, $susunan);
        $this->f = $f;
        $paraf = $this->adaParaf() ? $this->jabatanParaf() : [];

        $blok = self::gayaKonsiderans($susunan, $f);

        $buat = function (bool $sisihkanParaf) use ($f, $blok, $paraf) {
            $pdf = Pdf::loadView('pdf.v2.naskah', [
                'f' => $f,
                'blok' => $blok,
                'lambang' => $f['kop']['tampil'] ? FormatDokumen::pathLambang($f) : null,
                'paraf' => $paraf,
                'sisihkanParaf' => $sisihkanParaf,
                'tinggiParaf' => (count($paraf) + 1) * self::BARIS_PARAF,
            ])->setPaper($this->kertas($f), 'portrait');

            $this->pasangBookman($pdf->getDomPDF());
            $pdf->render();

            return $pdf;
        };

        // Kotak paraf selalu ada di bawah tanda tangan. Halaman pertama juga diberi kotak bila naskahnya
        // lebih dari satu halaman: dirender ulang dengan ruang bawah halaman 1 disisihkan (@page :first),
        // supaya naskah satu halaman tidak terdorong menjadi dua oleh ruang yang tidak dipakai.
        $pdf = $buat(false);

        if ($paraf && $pdf->getDomPDF()->getCanvas()->get_page_count() > 1) {
            $pdf = $buat(true);
            $this->parafHalamanPertama($pdf->getDomPDF(), $paraf, $f);
        }

        return $pdf->output();
    }

    private function parafHalamanPertama(Dompdf $dompdf, array $jabatan, array $f): void
    {
        $dompdf->getCanvas()->page_script(function (int $halaman, int $jumlah, $canvas, $fontMetrics) use ($jabatan, $f) {
            if ($halaman !== 1 || $jumlah < 2) {
                return;
            }

            $pt = fn (float $cm) => FormatDokumen::cmKePoint($cm);
            $baris = $pt(self::BARIS_PARAF);
            $tinggi = $baris * (count($jabatan) + 1);
            $x = $pt((float) $f['margin']['kiri']);
            $y = $canvas->get_height() - $pt((float) $f['margin']['bawah']) - $tinggi;
            [$lebarJabatan, $lebarParaf] = [$pt(self::LEBAR_JABATAN_PARAF), $pt(1.8)];
            $huruf = $fontMetrics->getFont($f['huruf']['nama']) ?? $fontMetrics->getFont('times');

            foreach ([(string) $f['paraf']['judul'], ...$jabatan] as $i => $teks) {
                $atas = $y + $i * $baris;
                $canvas->rectangle($x, $atas, $lebarJabatan, $baris, [0, 0, 0], 0.5);
                $canvas->rectangle($x + $lebarJabatan, $atas, $lebarParaf, $baris, [0, 0, 0], 0.5);

                // Satu baris per kotak: jabatan yang panjang dikecilkan hurufnya, bukan dipotong.
                $ukuran = min(11.0, (float) $f['huruf']['ukuran']);
                $lebarTeks = $fontMetrics->getTextWidth($teks, $huruf, $ukuran);
                if ($lebarTeks > $lebarJabatan - 8) {
                    $ukuran *= ($lebarJabatan - 8) / $lebarTeks;
                    $lebarTeks = $fontMetrics->getTextWidth($teks, $huruf, $ukuran);
                }
                $kiriTeks = $i === 0 ? $x + ($lebarJabatan - $lebarTeks) / 2 : $x + 4;
                $canvas->text($kiriTeks, $atas + ($baris - $ukuran) / 2, $teks, $huruf, $ukuran);
            }
        });
    }

    /**
     * Produk hukum daerah wajib berhuruf Bookman Old Style (Permendagri 80/2015 Pasal 167,
     * Permendagri 1/2023 Pasal 32). Filenya berlisensi dan tidak ikut di repo: salin ke
     * resources/fonts (lihat docs/DEPLOY.md). Hanya huruf yang terpakai yang ditanam ke PDF.
     */
    private function pasangBookman(Dompdf $dompdf): void
    {
        $dompdf->getOptions()->setIsFontSubsettingEnabled(true);
        File::ensureDirectoryExists($dompdf->getOptions()->getFontDir());

        foreach (self::BOOKMAN as $berkas => [$tebal, $gaya]) {
            $path = resource_path("fonts/{$berkas}");

            if (! is_file($path)) {
                Log::error("Font {$berkas} tidak ada di resources/fonts: PDF naskah tidak memakai Bookman Old Style.");

                continue;
            }

            $dompdf->getFontMetrics()->registerFont(['family' => 'Bookman Old Style', 'weight' => $tebal, 'style' => $gaya], $path);
        }
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

        // Tanda tangan peneliti mengikuti Kepala Bagian Hukum: baru dibubuhkan setelah nota dinas terbit.
        $peneliti = $naskah->peneliti;

        return Pdf::loadView('pdf.v2.matriks', [
            'naskah' => $naskah,
            'nota' => $nota,
            'peneliti' => $peneliti,
            'ttdPeneliti' => $nota ? TandaTanganPeneliti::path($peneliti) : null,
            'isi' => $isi,
            'singkatan' => $naskah->jenis === 'Keputusan Bupati' ? 'Rancangan Keputusan' : 'Raperbup',
            'judul' => $naskah->jenis === 'Keputusan Bupati' ? 'KEPUTUSAN BUPATI' : 'PERATURAN BUPATI',
        ])->setPaper(self::F4_POINT, 'landscape')->output();
    }
}
