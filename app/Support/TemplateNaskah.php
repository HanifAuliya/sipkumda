<?php

namespace App\Support;

use App\Models\Naskah;
use App\Models\NaskahBaris;
use App\Models\Pengaturan;
use Illuminate\Support\Collection;

/**
 * Susunan matriks per jenis produk hukum dan penomoran otomatisnya.
 * Peraturan Bupati memakai BAB/Pasal; Surat Keputusan memakai KESATU, KEDUA, dst.
 */
class TemplateNaskah
{
    public const BAGIAN = [
        'judul' => 'Judul',
        'menimbang' => 'Konsiderans Menimbang',
        'mengingat' => 'Dasar Hukum Mengingat',
        'diktum' => 'Diktum',
        'batang_tubuh' => 'Batang Tubuh',
        'penutup' => 'Penutup',
        'lampiran' => 'Lampiran',
    ];

    private const DIKTUM = [
        'KESATU', 'KEDUA', 'KETIGA', 'KEEMPAT', 'KELIMA', 'KEENAM', 'KETUJUH', 'KEDELAPAN', 'KESEMBILAN', 'KESEPULUH',
        'KESEBELAS', 'KEDUA BELAS', 'KETIGA BELAS', 'KEEMPAT BELAS', 'KELIMA BELAS',
    ];

    /** Bagian yang dipakai suatu jenis, berurutan. */
    public static function bagian(string $jenis): array
    {
        return $jenis === 'Surat Keputusan'
            ? array_diff_key(self::BAGIAN, ['batang_tubuh' => true])
            : self::BAGIAN;
    }

    /** Baris awal untuk naskah yang diketik langsung tanpa unggah Word. */
    public static function kerangka(string $jenis, string $tentang): array
    {
        $tentang = mb_strtoupper($tentang);
        $penutup = self::penutup($jenis);

        if ($jenis === 'Surat Keputusan') {
            return [
                ['bagian' => 'judul', 'label' => null, 'teks' => "KEPUTUSAN BUPATI HULU SUNGAI TENGAH\nNOMOR ... TAHUN ...\nTENTANG\n{$tentang}"],
                ['bagian' => 'menimbang', 'label' => null, 'teks' => 'bahwa ...'],
                ['bagian' => 'mengingat', 'label' => null, 'teks' => ''],
                ['bagian' => 'diktum', 'label' => null, 'teks' => ''],
                ['bagian' => 'penutup', 'label' => null, 'teks' => $penutup],
                ['bagian' => 'lampiran', 'label' => null, 'teks' => ''],
            ];
        }

        return [
            ['bagian' => 'judul', 'label' => null, 'teks' => "PERATURAN BUPATI HULU SUNGAI TENGAH\nNOMOR ... TAHUN ...\nTENTANG\n{$tentang}"],
            ['bagian' => 'menimbang', 'label' => null, 'teks' => 'bahwa ...'],
            ['bagian' => 'mengingat', 'label' => null, 'teks' => ''],
            ['bagian' => 'diktum', 'label' => null, 'teks' => "Menetapkan: PERATURAN BUPATI TENTANG {$tentang}."],
            ['bagian' => 'batang_tubuh', 'label' => 'BAB I', 'teks' => 'KETENTUAN UMUM'],
            ['bagian' => 'batang_tubuh', 'label' => 'Pasal 1', 'teks' => ''],
            ['bagian' => 'penutup', 'label' => null, 'teks' => $penutup],
            ['bagian' => 'lampiran', 'label' => null, 'teks' => ''],
        ];
    }

    /**
     * Teks penutup dengan nama pejabat dari Pengaturan. Tanggal dan nomor dikosongkan,
     * karena diisi saat penandatanganan basah.
     */
    public static function penutup(string $jenis): string
    {
        $titik = '.......................';
        $bupati = Pengaturan::ambil(Pengaturan::NAMA_BUPATI, $titik);

        $ditetapkan = "Ditetapkan di Barabai\npada tanggal ...\nBUPATI HULU SUNGAI TENGAH,\n\n\n\n{$bupati}";

        if ($jenis === 'Surat Keputusan') {
            return $ditetapkan;
        }

        $sekda = Pengaturan::ambil(Pengaturan::NAMA_SEKDA, $titik);

        return "{$ditetapkan}\n\nDiundangkan di Barabai\npada tanggal ...\nSEKRETARIS DAERAH\nKABUPATEN HULU SUNGAI TENGAH,\n\n\n\n{$sekda}"
            . "\n\nBERITA DAERAH KABUPATEN HULU SUNGAI TENGAH TAHUN ... NOMOR ...";
    }

    /**
     * Mengganti penutup hasil pembacaan Word: nama pejabat di file bisa sudah usang.
     *
     * @param  array<int, array{bagian: string, label: ?string, teks: ?string}>  $baris
     */
    public static function denganPenutup(array $baris, string $jenis): array
    {
        $baru = ['bagian' => 'penutup', 'label' => null, 'teks' => self::penutup($jenis)];
        $hasil = array_values(array_filter($baris, fn ($b) => $b['bagian'] !== 'penutup'));

        // Penutup berada tepat sebelum baris Lampiran.
        $posisi = array_search('lampiran', array_column($hasil, 'bagian'), true);
        array_splice($hasil, $posisi === false ? count($hasil) : $posisi, 0, [$baru]);

        return $hasil;
    }

    /**
     * Penanda tiap baris: a./b. pada Menimbang, 1./2. pada Mengingat, KESATU/KEDUA pada diktum SK,
     * sisanya label yang ditulis pengguna (BAB I, Pasal 5). Baris dihapus tidak ikut dihitung.
     *
     * @param  Collection<int, NaskahBaris>  $baris  urut menurut kolom urutan
     * @return array<int, string|null> id baris => penanda
     */
    public static function penanda(Naskah $naskah, Collection $baris): array
    {
        $hitung = ['menimbang' => 0, 'mengingat' => 0, 'diktum' => 0];
        $hasil = [];

        foreach ($baris as $item) {
            $hidup = $item->status !== NaskahBaris::DIHAPUS;

            $hasil[$item->id] = match (true) {
                ! $hidup => $item->label,
                $item->bagian === 'menimbang' => self::huruf(++$hitung['menimbang']) . '.',
                $item->bagian === 'mengingat' => ++$hitung['mengingat'] . '.',
                $item->bagian === 'diktum' && $naskah->jenis === 'Surat Keputusan' => self::DIKTUM[$hitung['diktum']++] ?? null,
                default => $item->label,
            };
        }

        return $hasil;
    }

    private static function huruf(int $urutan): string
    {
        return $urutan <= 26 ? chr(96 + $urutan) : chr(96 + (int) (($urutan - 1) / 26)) . chr(97 + ($urutan - 1) % 26);
    }
}
