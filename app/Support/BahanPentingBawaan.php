<?php

namespace App\Support;

use App\Models\NaskahBerkas;
use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;

/**
 * Berkas bawaan untuk tombol "Bahan Penting" Perangkat Daerah (perintah bahan-penting:bawaan):
 * template matriks yang terbaca otomatis oleh PembacaNaskahWord (bentuk "Matriks"), contoh nota dinas
 * pengajuan, dan daftar periksa sebelum mengajukan. Template naskah biasa (Perbup/Kepbup) tidak di sini;
 * template itu dibuat langsung dari Format Dokumen (CetakNaskah::wordTemplate).
 */
class BahanPentingBawaan
{
    /** nama tombol => [nama berkas, metode pembuat] */
    public const DAFTAR = [
        'Template Matriks Peraturan Bupati' => ['bawaan-template-matriks-perbup.docx', 'matriksPerbup'],
        'Template Matriks Keputusan Bupati' => ['bawaan-template-matriks-kepbup.docx', 'matriksKepbup'],
        'Contoh Nota Dinas Pengajuan' => ['bawaan-contoh-nota-dinas-pengajuan.docx', 'notaDinas'],
        'Daftar Periksa Pengajuan' => ['bawaan-daftar-periksa-pengajuan.docx', 'daftarPeriksa'],
    ];

    private const HURUF = 'Arial';

    private const ISIAN = ['color' => '1F4E9E', 'italic' => true];

    /** Membuat berkas .docx di folder sementara dan mengembalikan alamatnya. */
    public static function buat(string $metode): string
    {
        Settings::setOutputEscapingEnabled(true);

        $word = new PhpWord();
        $word->setDefaultFontName(self::HURUF);
        $word->setDefaultFontSize(11);
        $word->getDocInfo()->setCreator('SIFKUMDA');

        (new self())->{$metode}($word);

        $path = tempnam(sys_get_temp_dir(), 'bahan') . '.docx';
        IOFactory::createWriter($word, 'Word2007')->save($path);

        return $path;
    }

    // ------------------------------------------------------------------ matriks

    private function matriksPerbup(PhpWord $word): void
    {
        $this->matriks($word, 'PERATURAN BUPATI', [
            ['Judul', ['PERATURAN BUPATI HULU SUNGAI TENGAH', 'NOMOR ... TAHUN ...', 'TENTANG', '[JUDUL RANCANGAN, HURUF KAPITAL]']],
            ['Konsiderans Menimbang', [
                'a. bahwa [alasan filosofis/sosiologis perlunya pengaturan];',
                'b. bahwa berdasarkan pertimbangan sebagaimana dimaksud dalam huruf a, perlu menetapkan Peraturan Bupati tentang [Judul Rancangan];',
            ]],
            ['Dasar Hukum Mengingat', [
                '1. Undang-Undang Nomor 23 Tahun 2014 tentang Pemerintahan Daerah (Lembaran Negara Republik Indonesia Tahun 2014 Nomor 244, Tambahan Lembaran Negara Republik Indonesia Nomor 5587);',
                '2. [peraturan perundang-undangan lain yang menjadi dasar, lengkap dengan Lembaran Negara/Daerah];',
            ]],
            ['Diktum', ['MEMUTUSKAN:', 'Menetapkan: PERATURAN BUPATI TENTANG [JUDUL RANCANGAN].']],
            ['Batang Tubuh', ['BAB I', 'KETENTUAN UMUM']],
            ['', [
                'Pasal 1',
                'Dalam Peraturan Bupati ini yang dimaksud dengan:',
                '1. Daerah adalah Kabupaten Hulu Sungai Tengah.',
                '2. Pemerintah Daerah adalah Bupati sebagai unsur penyelenggara Pemerintahan Daerah yang memimpin pelaksanaan urusan pemerintahan yang menjadi kewenangan daerah otonom.',
                '3. Bupati adalah Bupati Hulu Sungai Tengah.',
            ]],
            ['', ['BAB II', '[JUDUL BAB]']],
            ['', ['Pasal 2', '(1) [rumusan ayat (1)].', '(2) [rumusan ayat (2)].']],
            ['', ['BAB III', 'KETENTUAN PENUTUP']],
            ['', [
                'Pasal 3',
                'Peraturan Bupati ini mulai berlaku pada tanggal diundangkan.',
                'Agar setiap orang mengetahuinya, memerintahkan pengundangan Peraturan Bupati ini dengan penempatannya dalam Berita Daerah Kabupaten Hulu Sungai Tengah.',
            ]],
            ['Penutup', ['Ditetapkan di Barabai', 'pada tanggal ...', 'BUPATI HULU SUNGAI TENGAH,']],
        ]);
    }

    private function matriksKepbup(PhpWord $word): void
    {
        $this->matriks($word, 'KEPUTUSAN BUPATI', [
            ['Judul', ['KEPUTUSAN BUPATI HULU SUNGAI TENGAH', 'NOMOR .../.../.../TAHUN ...', 'TENTANG', '[JUDUL RANCANGAN, HURUF KAPITAL]']],
            ['Konsiderans Menimbang', [
                'a. bahwa [alasan perlunya keputusan];',
                'b. bahwa berdasarkan pertimbangan sebagaimana dimaksud dalam huruf a, perlu menetapkan Keputusan Bupati tentang [Judul Rancangan];',
            ]],
            ['Dasar Hukum Mengingat', [
                '1. Undang-Undang Nomor 23 Tahun 2014 tentang Pemerintahan Daerah (Lembaran Negara Republik Indonesia Tahun 2014 Nomor 244, Tambahan Lembaran Negara Republik Indonesia Nomor 5587);',
                '2. [peraturan perundang-undangan lain yang menjadi dasar];',
            ]],
            ['Diktum', ['KESATU : [isi keputusan, mis. Menetapkan ... sebagaimana tercantum dalam Lampiran yang merupakan bagian tidak terpisahkan dari Keputusan Bupati ini.]']],
            ['', ['KEDUA : [tugas, kewajiban, atau ketentuan lain].']],
            ['', ['KETIGA : Segala biaya yang timbul akibat ditetapkannya Keputusan Bupati ini dibebankan pada Anggaran Pendapatan dan Belanja Daerah Kabupaten Hulu Sungai Tengah.']],
            ['', ['KEEMPAT : Keputusan Bupati ini mulai berlaku pada tanggal ditetapkan.']],
            ['Penutup', ['Ditetapkan di Barabai', 'pada tanggal ...', 'BUPATI HULU SUNGAI TENGAH,']],
        ]);
    }

    /** Tabel No | Materi | Rumusan. Judul kolom jangan diubah: kolom "Rumusan" yang dibaca SIFKUMDA. */
    private function matriks(PhpWord $word, string $jenis, array $baris): void
    {
        $bagian = $word->addSection([
            'orientation' => 'landscape', 'pageSizeW' => 16838, 'pageSizeH' => 11906,
            'marginLeft' => 1134, 'marginRight' => 1134, 'marginTop' => 1134, 'marginBottom' => 1134,
        ]);

        $bagian->addText("MATRIKS RANCANGAN {$jenis} HULU SUNGAI TENGAH", ['bold' => true, 'size' => 12], ['alignment' => 'center', 'spaceAfter' => 0]);
        $bagian->addText('TENTANG [JUDUL RANCANGAN]', ['bold' => true, 'size' => 12], ['alignment' => 'center', 'spaceAfter' => 160]);
        $this->catatan($bagian, [
            'Isi kolom Rumusan dengan naskah rancangan; satu paragraf untuk satu butir, ayat, atau huruf. Kolom Materi menandai bagian naskah.',
            'Jangan ubah judul kolom "Materi" dan "Rumusan". Saat mengajukan di SIFKUMDA, pilih bentuk file "Matriks".',
            'Teks dalam [kurung siku] adalah contoh isian; ganti atau hapus. Baris boleh ditambah sesuai kebutuhan.',
        ]);

        $tabel = $bagian->addTable(['borderSize' => 6, 'borderColor' => '808080', 'cellMargin' => 90, 'width' => 14570, 'unit' => 'dxa']);
        $tabel->addRow(400, ['tblHeader' => true]);
        foreach ([[700, 'No'], [3300, 'Materi'], [10570, 'Rumusan']] as [$lebar, $judul]) {
            $tabel->addCell($lebar, ['bgColor' => 'D9E2F3', 'valign' => 'center'])->addText($judul, ['bold' => true], ['alignment' => 'center', 'spaceAfter' => 0]);
        }

        foreach ($baris as $i => [$materi, $rumusan]) {
            $tabel->addRow();
            $tabel->addCell(700)->addText((string) ($i + 1), [], ['alignment' => 'center', 'spaceAfter' => 0]);
            $tabel->addCell(3300)->addText($materi, ['bold' => true], ['spaceAfter' => 0]);
            $sel = $tabel->addCell(10570);
            foreach ($rumusan as $teks) {
                $this->teksIsian($sel, $teks);
            }
        }
    }

    // ------------------------------------------------------------------ nota dinas

    private function notaDinas(PhpWord $word): void
    {
        $bagian = $word->addSection([
            'pageSizeW' => 12242, 'pageSizeH' => 18722, // F4
            'marginLeft' => 1701, 'marginRight' => 1418, 'marginTop' => 1134, 'marginBottom' => 1418,
        ]);

        $bagian->addText('[KOP PERANGKAT DAERAH]', self::ISIAN + ['bold' => true, 'size' => 12], ['alignment' => 'center', 'spaceAfter' => 0]);
        $bagian->addText('[Alamat, telepon, laman, dan pos-el Perangkat Daerah]', self::ISIAN + ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 120,
            'borderBottomSize' => 18, 'borderBottomColor' => '000000']);
        $bagian->addText('NOTA DINAS', ['bold' => true, 'size' => 13], ['alignment' => 'center', 'spaceBefore' => 240, 'spaceAfter' => 240]);

        $info = $bagian->addTable(['cellMargin' => 30]);
        foreach ([
            ['Kepada Yth.', 'Kepala Bagian Hukum Sekretariat Daerah Kabupaten Hulu Sungai Tengah'],
            ['Dari', 'Kepala [nama Perangkat Daerah]'],
            ['Tanggal', '[tanggal]'],
            ['Nomor', '[nomor nota dinas Perangkat Daerah]'],
            ['Sifat', 'Biasa'],
            ['Lampiran', '1 (satu) berkas'],
            ['Hal', 'Permohonan Fasilitasi Rancangan [Peraturan Bupati/Keputusan Bupati] tentang [Judul Rancangan]'],
        ] as [$label, $isi]) {
            $info->addRow();
            $info->addCell(1700)->addText($label, [], ['spaceAfter' => 40]);
            $info->addCell(300)->addText(':', [], ['spaceAfter' => 40]);
            $this->teksIsian($info->addCell(7100), $isi, 40);
        }

        $bagian->addText('', [], ['spaceAfter' => 0, 'borderBottomSize' => 6, 'borderBottomColor' => '000000']);
        $paragraf = ['alignment' => 'both', 'spaceBefore' => 200, 'spaceAfter' => 120, 'lineHeight' => 1.3, 'indentation' => ['firstLine' => 567]];
        $this->teksIsian($bagian, 'Dalam rangka [maksud dan tujuan penyusunan], bersama ini kami sampaikan Rancangan [Peraturan Bupati/Keputusan Bupati] Hulu Sungai Tengah tentang [Judul Rancangan] untuk dapat dilakukan penelitian dan fasilitasi oleh Bagian Hukum Sekretariat Daerah Kabupaten Hulu Sungai Tengah.', null, $paragraf);
        $this->teksIsian($bagian, 'Rancangan tersebut kami ajukan melalui aplikasi SIFKUMDA. Substansi atau materi rancangan menjadi tanggung jawab [nama Perangkat Daerah] selaku pemrakarsa.', null, $paragraf);
        $this->teksIsian($bagian, 'Demikian disampaikan, atas perhatian dan kerja samanya diucapkan terima kasih.', null, $paragraf);

        $ttd = ['alignment' => 'center', 'spaceAfter' => 0, 'indentation' => ['left' => 4535]];
        $bagian->addText('', [], ['spaceAfter' => 240]);
        $this->teksIsian($bagian, 'KEPALA [NAMA PERANGKAT DAERAH],', null, $ttd);
        $bagian->addText('', [], ['spaceAfter' => 1100]);
        $this->teksIsian($bagian, '[NAMA LENGKAP]', null, $ttd + ['spaceAfter' => 0]);
        $this->teksIsian($bagian, '[Pangkat/Golongan]', null, $ttd);
        $this->teksIsian($bagian, 'NIP [NIP]', null, $ttd);
    }

    // ------------------------------------------------------------------ daftar periksa

    private function daftarPeriksa(PhpWord $word): void
    {
        $bagian = $word->addSection(['marginLeft' => 1134, 'marginRight' => 1134, 'marginTop' => 1134, 'marginBottom' => 1134]);

        $bagian->addText('DAFTAR PERIKSA PENGAJUAN RANCANGAN', ['bold' => true, 'size' => 13], ['alignment' => 'center', 'spaceAfter' => 0]);
        $bagian->addText('Peraturan Bupati dan Keputusan Bupati melalui SIFKUMDA', ['size' => 11], ['alignment' => 'center', 'spaceAfter' => 240]);

        $tabel = $bagian->addTable(['borderSize' => 6, 'borderColor' => '808080', 'cellMargin' => 90]);
        $tabel->addRow(400, ['tblHeader' => true]);
        foreach ([[500, 'No'], [3000, 'Yang disiapkan'], [5400, 'Keterangan'], [800, 'Siap']] as [$lebar, $judul]) {
            $tabel->addCell($lebar, ['bgColor' => 'D9E2F3', 'valign' => 'center'])->addText($judul, ['bold' => true], ['alignment' => 'center', 'spaceAfter' => 0]);
        }

        foreach ([
            ['Nota Dinas Kepala Perangkat Daerah', 'Wajib. PDF yang sudah ditandatangani, diunggah saat Ajukan Rancangan Baru. Nomor dan tanggalnya juga diisi di formulir karena dikutip dalam nota dinas balasan Bagian Hukum. Contoh: Bahan Penting › Contoh Nota Dinas Pengajuan.'],
            ['Judul rancangan (Tentang)', 'Wajib. Sama persis dengan judul di dalam naskah.'],
            ['Rancangan naskah dalam Word', 'Dianjurkan. Pakai Template Peraturan Bupati atau Keputusan Bupati (bentuk "Naskah Rancangan"), atau Template Matriks (bentuk "Matriks"). Tanpa file Word, isi rancangan diketik langsung di layar kerja.'],
            ['Konsiderans Menimbang', 'Memuat alasan pengaturan dan diakhiri kalimat "perlu menetapkan ... tentang ...".'],
            ['Dasar hukum Mengingat', 'Lengkap dengan tahun, nomor Lembaran Negara/Daerah, dan Tambahan Lembaran Negara bila ada; diurutkan menurut hierarki.'],
            ['Isi naskah', 'Peraturan Bupati: BAB dan Pasal berurutan, ditutup Ketentuan Penutup. Keputusan Bupati: diktum KESATU, KEDUA, dan seterusnya.'],
            [NaskahBerkas::LABEL[NaskahBerkas::LAMPIRAN] . ' (bila ada)', 'Diunggah terpisah di tab Berkas, bukan di dalam file Word naskah.'],
            [NaskahBerkas::LABEL[NaskahBerkas::BAHAN_PENDUKUNG] . ' (bila ada)', 'Kajian, data, atau dokumen rujukan; diunggah di tab Berkas.'],
        ] as $i => [$apa, $ket]) {
            $tabel->addRow();
            $tabel->addCell(500)->addText((string) ($i + 1), [], ['alignment' => 'center', 'spaceAfter' => 0]);
            $tabel->addCell(3000)->addText($apa, ['bold' => true], ['spaceAfter' => 0]);
            $tabel->addCell(5400)->addText($ket, [], ['spaceAfter' => 0]);
            $tabel->addCell(800)->addText('☐', ['size' => 14], ['alignment' => 'center', 'spaceAfter' => 0]);
        }

        $bagian->addText('Sesudah diajukan', ['bold' => true], ['spaceBefore' => 300, 'spaceAfter' => 80]);
        foreach ([
            'Pantau tahapnya di menu Rancanganku dan Dashboard › Giliran Anda; setiap perubahan tahap dikabarkan lewat notifikasi.',
            'Bila Peneliti meminta penyesuaian (tahap Perbaikan PD), buka naskah, perbaiki baris yang ditandai, lalu tekan Kirim tindak lanjut.',
            'Panduan lengkap ada di menu Panduan di dalam SIFKUMDA.',
        ] as $teks) {
            $bagian->addListItem($teks, 0, [], ['listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED], ['spaceAfter' => 60]);
        }
    }

    // ------------------------------------------------------------------ bantu

    private function catatan(Section $bagian, array $baris): void
    {
        foreach ($baris as $teks) {
            $bagian->addText('• ' . $teks, ['size' => 9, 'color' => '555555', 'italic' => true], ['spaceAfter' => 0]);
        }
        $bagian->addText('', [], ['spaceAfter' => 120]);
    }

    /** Teks dengan bagian [isian] berwarna biru miring, supaya jelas mana yang harus diganti. */
    private function teksIsian(Section|Cell $wadah, string $teks, ?int $spasiBawah = 0, ?array $paragraf = null): void
    {
        $run = $wadah->addTextRun($paragraf ?? ['spaceAfter' => $spasiBawah ?? 0]);

        foreach (preg_split('/(\[[^\]]+\])/u', $teks, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $potong) {
            $run->addText($potong, str_starts_with($potong, '[') ? self::ISIAN : []);
        }
    }
}
