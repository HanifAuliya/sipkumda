<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;

class KelengkapanBerkasChecker
{
    // =========================================================
    // Ekstrak teks
    // =========================================================
    private function extractText($file): string
    {
        $path      = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            if ($extension === 'pdf') {
                $parser   = new Parser();
                $pdf      = $parser->parseFile($path);
                $text     = $pdf->getText();
            } elseif (in_array($extension, ['doc', 'docx'])) {
                $phpWord  = IOFactory::load($path);
                $parts    = [];

                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $parts[] = $element->getText();
                        } elseif (method_exists($element, 'getRows')) {
                            // tabel
                            foreach ($element->getRows() as $row) {
                                foreach ($row->getCells() as $cell) {
                                    foreach ($cell->getElements() as $cellEl) {
                                        if (method_exists($cellEl, 'getText')) {
                                            $parts[] = $cellEl->getText();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                $text = implode(' ', $parts);
            } else {
                return '';
            }
        } catch (\Exception $e) {
            return '';
        }

        return $this->normalize($text);
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[\r\n\t]+/', ' ', $text);
        $text = preg_replace('/ {2,}/', ' ', $text);
        return trim($text);
    }

    // =========================================================
    // Deteksi tanda tangan
    // =========================================================
    private function checkTandaTangan(string $text): bool
    {
        $foundNip = preg_match('/nip[\s:.]*[\d][\d\s]{16,21}\d/i', $text);

        $foundJabatan = preg_match(
            '/(kepala|mengetahui|bupati|wakil bupati|inspektur|sekretaris|'
                . 'camat|direktur|ketua|pimpinan|plt|plh|pj\.?|pejabat|'
                . 'ditandatangani|a\.n\.|an\.|atas nama)/i',
            $text
        );

        return $foundNip && $foundJabatan;
    }

    // =========================================================
    // Cek per dokumen
    // =========================================================
    private function checkNotaDinas(string $text): array
    {
        $bulan = '(januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember)';

        return [
            'nota_dinas_tertanggal' => (
                preg_match('/\b\d{1,2}\s*\/\s*\d{1,2}\s*\/\s*\d{4}\b/', $text) ||
                preg_match('/\b\d{1,2}\s+' . $bulan . '\s+\d{4}\b/i', $text) ||
                preg_match('/tanggal\s*[:\-]?\s*\d{1,2}/i', $text)
            ) ? 1 : 0,

            'nota_dinas_memiliki_nomor' => (
                preg_match('/nomor\s*[:\-]?\s*[\w\/\.\-]+/i', $text) && str_contains($text, '/')
            ) ? 1 : 0,

            'nota_dinas_memiliki_perihal' => (
                preg_match('/perihal\s*[:\-]?\s*\w+/i', $text)
            ) ? 1 : 0,

            'nota_dinas_ada_pengantar' => (
                preg_match('/(bersama ini|sehubung|dengan hormat|menindaklanjuti|memperhatikan|dalam rangka|berkenaan dengan)/i', $text)
            ) ? 1 : 0,

            'nota_dinas_ada_dasar_hukum' => (
                preg_match('/(undang.undang|peraturan pemerintah|peraturan menteri|peraturan daerah|peraturan bupati|keputusan|dasar)/i', $text)
            ) ? 1 : 0,

            'nota_dinas_ada_penutup' => (
                preg_match('/(demikian\s+(kami\s+)?disampaikan|atas\s+(perhatian|kerja\s*sama|perkenan))/i', $text)
            ) ? 1 : 0,

            'nota_dinas_tertandatangan' => $this->checkTandaTangan($text) ? 1 : 0,
        ];
    }

    private function checkRancangan(string $text): array
    {
        return [
            'rancangan_judul_dokumen' => (
                preg_match('/rancangan\s+peraturan\s+bupati/i', $text) ||
                preg_match('/rancangan\s+surat\s+keputusan/i', $text) ||
                preg_match('/rancangan\s+keputusan\s+bupati/i', $text) ||
                preg_match('/peraturan\s+bupati\s+\w+/i', $text)
            ) ? 1 : 0,

            'rancangan_memiliki_tentang' => (
                preg_match('/\btentang\b\s+\w+/i', $text)
            ) ? 1 : 0,

            'rancangan_memiliki_mengingat' => (
                preg_match('/\bmengingat\s*[:\-]?/i', $text)
            ) ? 1 : 0,

            'rancangan_memiliki_menimbang' => (
                preg_match('/\bmenimbang\s*[:\-]?/i', $text)
            ) ? 1 : 0,

            'rancangan_memiliki_dasar_hukum' => (
                preg_match('/(undang.undang\s+nomor|peraturan\s+pemerintah\s+nomor|peraturan\s+menteri|peraturan\s+daerah|pasal\s+\d+\s+ayat)/i', $text)
            ) ? 1 : 0,

            'rancangan_memiliki_pasal' => (
                preg_match('/\bpasal\s+\d+/i', $text) ||
                preg_match('/\bbab\s+(i|ii|iii|iv|v|vi|vii|viii|ix|x|\d+)\b/i', $text)
            ) ? 1 : 0,

            'rancangan_memiliki_penutup' => (
                preg_match('/(ditetapkan\s+di|disahkan\s+di|memutuskan|menetapkan)/i', $text)
            ) ? 1 : 0,

            'rancangan_tertandatangan' => (
                preg_match('/\bttd\b/i', $text) ||
                preg_match('/ditetapkan\s+di\s+\w+/i', $text) ||
                $this->checkTandaTangan($text)
            ) ? 1 : 0,
        ];
    }

    private function checkMatrik(string $text): array
    {
        return [
            'matrik_memiliki_judul' => (
                preg_match('/(rancangan\s+peraturan|peraturan\s+bupati|keputusan\s+bupati)/i', $text) ||
                preg_match('/\bjudul\b/i', $text)
            ) ? 1 : 0,

            'matrik_memiliki_konsideran' => (
                preg_match('/konsiderans?\s*(menimbang|mengingat)/i', $text) ||
                preg_match('/\bmenimbang\b/i', $text) ||
                preg_match('/\bmengingat\b/i', $text)
            ) ? 1 : 0,

            'matrik_memiliki_dasar_hukum' => (
                preg_match('/(undang.undang|peraturan\s+(pemerintah|menteri|daerah|bupati)(\s+nomor)?)/i', $text)
            ) ? 1 : 0,

            'matrik_memiliki_diktum' => (
                preg_match('/(memutuskan|menetapkan|diktum)/i', $text)
            ) ? 1 : 0,

            'matrik_memiliki_batang_tubuh' => (
                preg_match('/\bpasal\s+\d+/i', $text) ||
                preg_match('/\bbab\s+(i|ii|iii|iv|v|\d+)\b/i', $text) ||
                preg_match('/batang\s+tubuh/i', $text)
            ) ? 1 : 0,

            'matrik_memiliki_lampiran' => (
                preg_match('/\blampiran\b/i', $text)
            ) ? 1 : 0,

            'matrik_memiliki_keterangan' => (
                preg_match('/\bketerangan\b/i', $text) ||
                preg_match('/(penyesuaian|sesuai\s+dengan\s+ketentuan|tetap)/i', $text)
            ) ? 1 : 0,
        ];
    }

    // =========================================================
    // Fungsi utama — dipanggil dari Livewire
    // =========================================================
    public function check($notaDinas, $rancangan, $matrik): array
    {
        $indikator = [];

        $textNota      = $this->extractText($notaDinas);
        $textRancangan = $this->extractText($rancangan);
        $textMatrik    = $this->extractText($matrik);

        $indikator = array_merge(
            $this->checkNotaDinas($textNota),
            $this->checkRancangan($textRancangan),
            $this->checkMatrik($textMatrik),
        );

        // Tentukan hasil akhir
        $adaKurang = in_array(0, $indikator);

        $catatanKurang = [];
        if ($adaKurang) {
            $map = [
                'nota_dinas_tertanggal'         => 'Nota Dinas belum mencantumkan tanggal',
                'nota_dinas_memiliki_nomor'      => 'Nota Dinas tidak memiliki nomor surat',
                'nota_dinas_memiliki_perihal'    => 'Nota Dinas tidak memiliki bagian Perihal',
                'nota_dinas_ada_pengantar'       => 'Nota Dinas tidak memiliki kalimat pengantar',
                'nota_dinas_ada_dasar_hukum'     => 'Nota Dinas tidak mencantumkan dasar hukum',
                'nota_dinas_ada_penutup'         => 'Nota Dinas tidak memiliki kalimat penutup',
                'nota_dinas_tertandatangan'      => 'Nota Dinas belum ditandatangani',
                'rancangan_judul_dokumen'        => 'Rancangan tidak memiliki judul dokumen',
                'rancangan_memiliki_tentang'     => 'Rancangan tidak memiliki bagian Tentang',
                'rancangan_memiliki_mengingat'   => 'Rancangan tidak memiliki bagian Mengingat',
                'rancangan_memiliki_menimbang'   => 'Rancangan tidak memiliki bagian Menimbang',
                'rancangan_memiliki_dasar_hukum' => 'Dasar hukum Rancangan belum lengkap',
                'rancangan_memiliki_pasal'       => 'Pasal dalam Rancangan belum ditemukan',
                'rancangan_memiliki_penutup'     => 'Penutup Rancangan belum tersedia',
                'rancangan_tertandatangan'       => 'Rancangan belum ditandatangani',
                'matrik_memiliki_judul'          => 'Matrik tidak memiliki judul',
                'matrik_memiliki_konsideran'     => 'Konsideran Matrik belum ditemukan',
                'matrik_memiliki_dasar_hukum'    => 'Dasar hukum Matrik belum tersedia',
                'matrik_memiliki_diktum'         => 'Diktum dalam Matrik belum tersedia',
                'matrik_memiliki_batang_tubuh'   => 'Batang tubuh Matrik belum ada',
                'matrik_memiliki_lampiran'       => 'Lampiran Matrik belum ada',
                'matrik_memiliki_keterangan'     => 'Keterangan tambahan Matrik kosong',
            ];

            foreach ($indikator as $key => $nilai) {
                if ($nilai === 0 && isset($map[$key])) {
                    $catatanKurang[] = $map[$key];
                }
            }
        }

        return [
            'hasil'   => $adaKurang ? 'Tidak Lengkap' : 'Lengkap',
            'catatan' => $catatanKurang,
        ];
    }
}
