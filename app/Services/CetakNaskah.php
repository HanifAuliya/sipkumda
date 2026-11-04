<?php

namespace App\Services;

use App\Models\Naskah;
use App\Models\NaskahBaris;
use App\Support\TataLetakNaskah;
use App\Support\TemplateNaskah;
use Barryvdh\DomPDF\Facade\Pdf;
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
    /** Kertas F4 dalam satuan twip (1 inci = 1440). */
    private const F4_LEBAR = 12240;
    private const F4_TINGGI = 18720;
    private const TEPI_KIRI = 1701;
    private const TEPI_KANAN = 1134;
    private const LEBAR_ISI = self::F4_LEBAR - self::TEPI_KIRI - self::TEPI_KANAN;
    private const INDEN = 454;

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
            'marginTop' => 1134,
            'marginBottom' => 1134,
        ]);
        $halaman->addFooter()->addPreserveText('{PAGE}', null, ['alignment' => Jc::CENTER]);

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

    private function wordDiktum(Section $halaman, array $blok): void
    {
        [$lebarLabel, $lebarTitik] = [1531, 284];
        $tabel = $halaman->addTable(['layout' => 'fixed', 'cellMargin' => 0]);

        foreach ($blok['butir'] as $butir) {
            $baris = $tabel->addRow();
            $baris->addCell($lebarLabel)->addText((string) $butir['nomor']);
            $baris->addCell($lebarTitik)->addText(':');
            $this->wordIsi($baris->addCell(self::LEBAR_ISI - $lebarLabel - $lebarTitik), $butir['isi']);
        }

        $halaman->addTextBreak(1, null, ['spaceAfter' => 0]);
    }

    private function wordIsi($sel, string $isi): void
    {
        foreach (preg_split('/\R/u', $isi) as $baris) {
            $sel->addText($baris, null, ['alignment' => Jc::BOTH, 'spaceAfter' => 60]);
        }
    }

    /** Ayat dan butir digambar sebagai tabel tanpa garis supaya teks lanjutannya rata dengan nomor. */
    private function wordTeks(Section $halaman, array $blok): void
    {
        $tabel = null;

        foreach ($blok['baris'] as $baris) {
            if (isset($baris['tabel'])) {
                $tabel = null;
                $this->wordTabel($halaman, $baris['tabel']);

                continue;
            }

            if ($baris['nomor'] === null) {
                $tabel = null;
                $halaman->addText($baris['isi'], null, ['alignment' => Jc::BOTH, 'spaceAfter' => 60]);

                continue;
            }

            $tabel ??= $halaman->addTable(['layout' => 'fixed', 'cellMargin' => 0]);
            $inden = $baris['tingkat'] * self::INDEN;
            $lebarNomor = 567;

            $tr = $tabel->addRow();
            if ($inden) {
                $tr->addCell($inden);
            }
            $tr->addCell($lebarNomor)->addText($baris['nomor']);
            $tr->addCell(self::LEBAR_ISI - $inden - $lebarNomor)->addText($baris['isi'], null, ['alignment' => Jc::BOTH, 'spaceAfter' => 60]);
        }
    }

    /** @param array<int, array<int, string>> $isi */
    private function wordTabel(Section $halaman, array $isi): void
    {
        $kolom = max(array_map('count', $isi));
        $lebar = (int) (self::LEBAR_ISI / $kolom);
        $tabel = $halaman->addTable(['layout' => 'fixed', 'borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 60]);

        foreach ($isi as $sel) {
            $tr = $tabel->addRow();
            for ($i = 0; $i < $kolom; $i++) {
                $tr->addCell($lebar)->addText($sel[$i] ?? '', ['size' => 11]);
            }
        }

        $halaman->addTextBreak(1, null, ['spaceAfter' => 0]);
    }

    private function wordPenutup(Section $halaman, array $blok): void
    {
        $halaman->addTextBreak(1);

        foreach ($blok['ditetapkan'] as $baris) {
            $halaman->addText($baris, null, ['indentation' => ['left' => 4536], 'keepNext' => true, 'spaceAfter' => 0]);
        }

        if ($blok['diundangkan']) {
            $halaman->addTextBreak(1);
            foreach ($blok['diundangkan'] as $baris) {
                $halaman->addText($baris, null, ['keepNext' => true, 'spaceAfter' => 0]);
            }
        }

        if ($blok['berita']) {
            $halaman->addTextBreak(1);
            $halaman->addText($blok['berita'], null, ['spaceAfter' => 0]);
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
            // Satu paragraf yang melebihi batas pun dipenggal di antara kata.
            foreach (explode("\n", wordwrap($paragraf, $hurufPerBaris * self::MATRIKS_MAKS_BARIS, "\n")) as $bagian) {
                $tinggi = max(1, (int) ceil(mb_strlen($bagian) / $hurufPerBaris));

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
            'singkatan' => $naskah->jenis === 'Surat Keputusan' ? 'Rancangan Keputusan' : 'Raperbup',
            'judul' => $naskah->jenis === 'Surat Keputusan' ? 'KEPUTUSAN BUPATI' : 'PERATURAN BUPATI',
        ])->setPaper(self::F4_POINT, 'landscape')->output();
    }
}
