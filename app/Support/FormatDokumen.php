<?php

namespace App\Support;

use App\Models\Pengaturan;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

/**
 * Format cetak naskah dan nota dinas yang bisa diatur dari menu Format Dokumen, supaya perubahan
 * pedoman (margin, kop, isi nota dinas) tidak perlu mengubah kode. Disimpan sebagai JSON di
 * tabel pengaturan; nilai yang belum pernah diatur memakai bawaan di bawah.
 *
 * Bawaan Keputusan Bupati mengikuti "Pedoman Penyusunan Produk Hukum (SK)" Bagian Hukum,
 * bawaan Nota Dinas mengikuti Nota Dinas Kepala Bagian Hukum Nomor 100.3/437/ND/KUM/2026.
 * Peraturan Bupati memakai format yang sudah berjalan.
 */
class FormatDokumen
{
    public const KEPBUP = 'Keputusan Bupati';
    public const PERBUP = 'Peraturan Bupati';

    private const KUNCI = [
        self::KEPBUP => 'format_naskah_kepbup',
        self::PERBUP => 'format_naskah_perbup',
        'nota' => 'format_nota_dinas',
    ];

    /** Huruf yang tersedia di Word maupun PDF. Arial digambar sebagai Helvetica oleh DomPDF. */
    public const HURUF = ['Bookman Old Style', 'Times New Roman', 'Arial'];

    /** Isian otomatis pada teks nota dinas. */
    public const ISIAN_NOTA = [
        '{jenis}' => 'Jenis naskah, mis. Keputusan Bupati',
        '{tentang}' => 'Judul naskah',
        '{perangkat_daerah}' => 'Perangkat Daerah pemrakarsa',
        '{nomor_nota_pd}' => 'Nomor nota dinas dari Perangkat Daerah',
        '{tanggal_nota_pd}' => 'Tanggal nota dinas dari Perangkat Daerah',
    ];

    public const FOLDER_GAMBAR = 'format_dokumen';

    public static function bawaanNaskah(string $jenis): array
    {
        $sk = $jenis === self::KEPBUP;

        return [
            'kertas' => $sk ? ['lebar' => 21.5, 'tinggi' => 33.0] : ['lebar' => 21.59, 'tinggi' => 33.02],
            // atas_pertama: jarak tepi atas ke judul pada halaman 1 (kop berada di ruang ini).
            'margin' => $sk
                ? ['atas_pertama' => 8.0, 'atas' => 3.0, 'bawah' => 2.5, 'kiri' => 2.5, 'kanan' => 2.5]
                : ['atas_pertama' => 2.0, 'atas' => 2.0, 'bawah' => 2.5, 'kiri' => 3.0, 'kanan' => 2.0],
            'huruf' => ['nama' => 'Bookman Old Style', 'ukuran' => 12.0, 'spasi' => $sk ? 1.0 : 1.15],
            // Matikan bila naskah dicetak di kertas kop emas yang lambangnya sudah tercetak.
            'kop' => ['tampil' => true, 'teks' => 'BUPATI HULU SUNGAI TENGAH', 'ukuran' => 14.0, 'lebar_lambang' => $sk ? 2.5 : 2.4, 'lambang' => null],
            // rapat: "Menimbang:" dan "MEMUTUSKAN:"; kolom: "Menimbang :" dengan titik dua sejajar.
            'konsiderans' => ['titik_dua' => $sk ? 'rapat' : 'kolom', 'lebar_label' => 2.85],
            'penutup' => ['tempat' => 'Barabai', 'kiri' => 8.0],
            'paraf' => [
                'tampil' => $sk,
                'judul' => 'Paraf Hierarki',
                'jabatan' => ['Sekretaris Daerah', 'Asisten Pemerintahan dan Kesra', 'Kepala Bagian Hukum'],
            ],
        ];
    }

    public static function bawaanNota(): array
    {
        return [
            'kertas' => ['lebar' => 21.59, 'tinggi' => 33.02],
            'margin' => ['atas' => 0.75, 'bawah' => 2.5, 'kiri' => 2.5, 'kanan' => 2.5],
            'huruf' => ['nama' => 'Arial', 'ukuran' => 11.0, 'spasi' => 1.15],
            'kop' => [
                'tampil' => true,
                'lebar_logo' => 2.2,
                'logo' => null,
                'baris' => [
                    ['teks' => 'PEMERINTAH KABUPATEN HULU SUNGAI TENGAH', 'ukuran' => 14.0, 'tebal' => true],
                    ['teks' => 'SEKRETARIAT DAERAH', 'ukuran' => 16.0, 'tebal' => true],
                    ['teks' => 'Jalan Perwira No. 01 Barabai, Hulu Sungai Tengah, Kalimantan Selatan 71311', 'ukuran' => 9.0, 'tebal' => false],
                    ['teks' => 'Faksimile (0517) 41029, Laman https://setda.hst.go.id, Pos-el setda@hstkab.go.id', 'ukuran' => 9.0, 'tebal' => false],
                ],
            ],
            'judul' => ['teks' => 'NOTA DINAS', 'ukuran' => 14.0],
            // {nomor} nomor urut yang diisi saat menerbitkan, {tahun} tahun terbit,
            // {kode} NDPB untuk Peraturan Bupati dan NDSK untuk Keputusan Bupati.
            'nomor' => '100.3/{nomor}/ND/KUM/{tahun}',
            'dari' => 'Kepala Bagian Hukum',
            'perihal' => 'Koreksi atas {jenis} tentang {tentang}.',
            'pembuka' => 'Sehubungan dengan Nota Dinas Kepala {perangkat_daerah} Kabupaten Hulu Sungai Tengah Nomor: {nomor_nota_pd} tanggal {tanggal_nota_pd} perihal sebagaimana tersebut di atas, dengan ini disampaikan beberapa hal:',
            'butir' => [
                'Konsep {jenis} Hulu Sungai Tengah sebagaimana dimaksud telah diteliti dan dikoreksi oleh Bagian Hukum sesuai dengan bentuk dan format penyusunan Peraturan Perundang-undangan yang berlaku dengan beberapa perbaikan sebagaimana tercantum dalam matriks hasil koreksi;',
                'Berkenaan dengan substansi/materi dari {jenis} Hulu Sungai Tengah ini menjadi tanggung jawab Perangkat Daerah/Unit Kerja pengusul atau pemrakarsa;',
                'Untuk proses penandatanganan {jenis} Hulu Sungai Tengah dimaksud haruslah mengikuti mekanisme dan prosedur yang berlaku sebagaimana diatur dalam Peraturan Menteri Dalam Negeri Nomor 80 Tahun 2015 tentang Pembentukan Produk Hukum Daerah sebagaimana diubah dengan Peraturan Menteri Dalam Negeri Nomor 120 Tahun 2018, yaitu sebagai berikut:',
            ],
            // Dicetak sebagai a., b., c. di bawah butir terakhir.
            'ketentuan' => [
                'Konsep {jenis} dibuat dalam rangkap 3 (tiga);',
                'Sebelum dilakukan pemarafan secara hierarki agar dimintakan paraf koordinasi Bagian Hukum;',
                'Jenis huruf menggunakan "Bookman Old Style";',
                'Ukuran huruf 12;',
                'Kertas F4, ukuran margin kanan, kiri, bawah 2,5 cm dan atas 3 cm sesuai dengan kop garuda;',
                'Sebelum pemberian nomor di Bagian Hukum disarankan untuk menyalin SK 1 (satu) rangkap.',
            ],
            'penutup' => 'Demikian disampaikan, untuk bahan selanjutnya.',
            'ttd' => ['jabatan' => 'Kepala Bagian Hukum,', 'kiri' => 8.5, 'gambar' => true],
        ];
    }

    public static function naskah(string $jenis): array
    {
        $jenis = $jenis === self::KEPBUP ? self::KEPBUP : self::PERBUP;

        $bawaan = self::bawaanNaskah($jenis);

        // JSON menulis 3.0 sebagai 3: tipe disamakan lagi dengan bawaan setiap kali dibaca.
        return self::amankan(self::rapikan(self::gabung($bawaan, self::baca(self::KUNCI[$jenis])), $bawaan), $bawaan);
    }

    public static function nota(): array
    {
        $bawaan = self::bawaanNota();

        return self::amankan(self::rapikan(self::gabung($bawaan, self::baca(self::KUNCI['nota'])), $bawaan), $bawaan);
    }

    /** Nama huruf masuk ke CSS PDF apa adanya, jadi hanya huruf dari daftar yang diterima. */
    private static function amankan(array $format, array $bawaan): array
    {
        if (! in_array($format['huruf']['nama'] ?? null, self::HURUF, true)) {
            $format['huruf']['nama'] = $bawaan['huruf']['nama'];
        }

        return $format;
    }

    public static function simpanNaskah(string $jenis, array $format): void
    {
        Pengaturan::simpan(self::KUNCI[$jenis === self::KEPBUP ? self::KEPBUP : self::PERBUP], json_encode($format, JSON_UNESCAPED_UNICODE));
    }

    public static function simpanNota(array $format): void
    {
        Pengaturan::simpan(self::KUNCI['nota'], json_encode($format, JSON_UNESCAPED_UNICODE));
    }

    /** Kembali ke bawaan: gambar unggahan lama ikut dibuang. */
    public static function kembalikan(string $jenis): void
    {
        $lama = $jenis === 'nota' ? self::nota()['kop']['logo'] : self::naskah($jenis)['kop']['lambang'];
        if ($lama) {
            Storage::disk('local')->delete($lama);
        }

        Pengaturan::simpan(self::KUNCI[$jenis], null);
    }

    /** Lambang kop naskah: unggahan bila ada, selain itu Garuda bawaan. */
    public static function pathLambang(array $format): ?string
    {
        return self::pathGambar($format['kop']['lambang'] ?? null, public_path('assets/img/garuda.png'), $format['kop']['pratinjau'] ?? null);
    }

    public static function pathLogoNota(array $format): ?string
    {
        return self::pathGambar($format['kop']['logo'] ?? null, public_path('assets/img/brand/barabai.png'), $format['kop']['pratinjau'] ?? null);
    }

    /** $pratinjau: file unggahan yang belum disimpan, dipakai saat mengunduh contoh dari formulir. */
    private static function pathGambar(?string $unggahan, string $bawaan, ?string $pratinjau = null): ?string
    {
        if ($pratinjau && is_file($pratinjau)) {
            return $pratinjau;
        }
        if ($unggahan && Storage::disk('local')->exists($unggahan)) {
            return Storage::disk('local')->path($unggahan);
        }

        return is_file($bawaan) ? $bawaan : null;
    }

    /**
     * Isian formulir (teks dari browser) disamakan tipenya dengan bawaan: angka, ya/tidak, teks.
     * Baris kosong pada daftar (jabatan paraf, butir, ketentuan, baris kop) dibuang.
     */
    public static function rapikan(array $isian, array $bawaan): array
    {
        $hasil = [];

        foreach ($bawaan as $kunci => $nilai) {
            $baru = $isian[$kunci] ?? $nilai;

            $hasil[$kunci] = match (true) {
                $kunci === 'baris' && is_array($nilai) => array_values(array_filter(array_map(fn ($b) => [
                    'teks' => trim((string) ($b['teks'] ?? '')),
                    'ukuran' => (float) ($b['ukuran'] ?? 11),
                    'tebal' => (bool) ($b['tebal'] ?? false),
                ], is_array($baru) ? $baru : []), fn ($b) => $b['teks'] !== '')),
                is_array($nilai) && ! Arr::isAssoc($nilai) => array_values(array_filter(array_map(fn ($b) => trim((string) $b), is_array($baru) ? $baru : []), 'strlen')),
                is_array($nilai) => self::rapikan(is_array($baru) ? $baru : [], $nilai),
                is_bool($nilai) => (bool) $baru,
                is_int($nilai) => (int) $baru,
                is_float($nilai) => (float) $baru,
                // Gambar unggahan hanya diganti lewat unggah atau hapus, bukan dari isian.
                $nilai === null => $isian[$kunci] ?? null,
                default => trim((string) $baru),
            };
        }

        return $hasil;
    }

    /** Mengganti isian {jenis}, {tentang}, dst. pada teks nota dinas. */
    public static function isi(string $teks, array $nilai): string
    {
        return strtr($teks, $nilai);
    }

    /** Pola nomor nota dinas, mis. 100.3/{nomor}/ND/KUM/{tahun}. */
    public static function nomorNota(string $pola, string $nomor, string $jenis, int $tahun): string
    {
        return strtr($pola, [
            '{nomor}' => $nomor,
            '{tahun}' => (string) $tahun,
            '{kode}' => $jenis === self::PERBUP ? 'NDPB' : 'NDSK',
        ]);
    }

    /**
     * Pola nomor nota dinas dipecah untuk formulir penerbitan: bagian tetap (100.3, ND, KUM, tahun)
     * tampil terkunci dan hanya {nomor} yang diisi. Tiap potongan: ['jenis' => teks|pemisah|nomor, 'isi' => ...].
     */
    public static function potonganNomorNota(string $pola, string $jenis, int $tahun): array
    {
        $potongan = [];

        foreach (preg_split('/(\{nomor\}|\/)/', $pola, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $bagian) {
            $potongan[] = match ($bagian) {
                '{nomor}' => ['jenis' => 'nomor', 'isi' => ''],
                '/' => ['jenis' => 'pemisah', 'isi' => '/'],
                default => ['jenis' => 'teks', 'isi' => self::nomorNota($bagian, '', $jenis, $tahun)],
            };
        }

        return $potongan;
    }

    public static function cmKeTwip(float $cm): int
    {
        return (int) round($cm * 1440 / 2.54);
    }

    public static function cmKePoint(float $cm): float
    {
        return round($cm * 72 / 2.54, 2);
    }

    /** Aturan validasi formulir; nama kolom sama dengan susunan array format. */
    public static function aturanNaskah(): array
    {
        return [
            'kertas.lebar' => 'required|numeric|between:10,50',
            'kertas.tinggi' => 'required|numeric|between:10,60',
            'margin.atas_pertama' => 'required|numeric|between:0,15',
            'margin.atas' => 'required|numeric|between:0,10',
            'margin.bawah' => 'required|numeric|between:0,10',
            'margin.kiri' => 'required|numeric|between:0,10',
            'margin.kanan' => 'required|numeric|between:0,10',
            'huruf.nama' => 'required|in:' . implode(',', self::HURUF),
            'huruf.ukuran' => 'required|numeric|between:8,20',
            'huruf.spasi' => 'required|numeric|between:0.8,3',
            'kop.tampil' => 'boolean',
            'kop.teks' => 'nullable|string|max:150',
            'kop.ukuran' => 'required|numeric|between:8,30',
            'kop.lebar_lambang' => 'required|numeric|between:0,8',
            'konsiderans.titik_dua' => 'required|in:rapat,kolom',
            'konsiderans.lebar_label' => 'required|numeric|between:1.5,6',
            'penutup.tempat' => 'required|string|max:60',
            'penutup.kiri' => 'required|numeric|between:0,15',
            'paraf.tampil' => 'boolean',
            'paraf.judul' => 'nullable|string|max:60',
            'paraf.jabatan' => 'array|max:8',
            'paraf.jabatan.*' => 'nullable|string|max:80',
        ];
    }

    public static function aturanNota(): array
    {
        return [
            'kertas.lebar' => 'required|numeric|between:10,50',
            'kertas.tinggi' => 'required|numeric|between:10,60',
            'margin.atas' => 'required|numeric|between:0,10',
            'margin.bawah' => 'required|numeric|between:0,10',
            'margin.kiri' => 'required|numeric|between:0,10',
            'margin.kanan' => 'required|numeric|between:0,10',
            'huruf.nama' => 'required|in:' . implode(',', self::HURUF),
            'huruf.ukuran' => 'required|numeric|between:8,20',
            'huruf.spasi' => 'required|numeric|between:0.8,3',
            'kop.tampil' => 'boolean',
            'kop.lebar_logo' => 'required|numeric|between:0,6',
            'kop.baris' => 'array|max:8',
            'kop.baris.*.teks' => 'nullable|string|max:200',
            'kop.baris.*.ukuran' => 'required|numeric|between:6,30',
            'kop.baris.*.tebal' => 'boolean',
            'judul.teks' => 'required|string|max:60',
            'judul.ukuran' => 'required|numeric|between:8,30',
            'nomor' => ['required', 'string', 'max:80', 'regex:/\{nomor\}/'],
            'dari' => 'required|string|max:150',
            'perihal' => 'required|string|max:500',
            'pembuka' => 'nullable|string|max:2000',
            'butir' => 'array|max:12',
            'butir.*' => 'nullable|string|max:2000',
            'ketentuan' => 'array|max:15',
            'ketentuan.*' => 'nullable|string|max:500',
            'penutup' => 'nullable|string|max:500',
            'ttd.jabatan' => 'required|string|max:150',
            'ttd.kiri' => 'required|numeric|between:0,15',
            'ttd.gambar' => 'boolean',
        ];
    }

    private static function baca(string $kunci): array
    {
        return json_decode(Pengaturan::ambil($kunci), true) ?: [];
    }

    /**
     * Isian tersimpan menimpa bawaan, kunci demi kunci; daftar (jabatan paraf, butir nota) diganti utuh.
     * Kunci yang belum ada di simpanan lama, misalnya setelah fitur baru, otomatis memakai bawaan.
     */
    private static function gabung(array $bawaan, array $simpanan): array
    {
        foreach ($bawaan as $kunci => $nilai) {
            if (! array_key_exists($kunci, $simpanan)) {
                continue;
            }

            $bawaan[$kunci] = is_array($nilai) && Arr::isAssoc($nilai) && is_array($simpanan[$kunci])
                ? self::gabung($nilai, $simpanan[$kunci])
                : $simpanan[$kunci];
        }

        return $bawaan;
    }
}
