<?php

namespace App\Support;

use App\Models\Naskah;
use App\Models\NaskahBaris;
use App\Models\Pengaturan;
use Illuminate\Support\Collection;

/**
 * Susunan matriks per jenis produk hukum dan penomoran otomatisnya.
 * Peraturan Bupati memakai BAB/Pasal; Keputusan Bupati memakai KESATU, KEDUA, dst.
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

    /** Tuntunan singkat untuk Perangkat Daerah saat menyusun tiap bagian. */
    public const PETUNJUK = [
        'judul' => 'Sesuaikan judul bila perlu. Nomor dan tahun dikosongkan, karena diisi saat penetapan.',
        'menimbang' => 'Satu baris untuk satu pertimbangan, diawali "bahwa". Huruf a, b, c dibuat otomatis.',
        'mengingat' => 'Satu baris untuk satu peraturan, dari yang tertinggi. Nomor urut dibuat otomatis.',
        'diktum' => 'Isi bagian yang ditetapkan. Pada Keputusan Bupati, KESATU, KEDUA, dan seterusnya dibuat otomatis; rincian a., b. ditulis per baris di dalam diktumnya.',
        'batang_tubuh' => 'Satu baris untuk satu BAB atau Pasal. Ayat dan butir ditulis di dalam isi pasalnya.',
        'penutup' => 'Nama pejabat diambil dari Pengaturan. Tanggal dan nomor dikosongkan untuk tanda tangan basah.',
        'lampiran' => 'Keterangan lampiran, bila ada. Filenya diunggah di tab Berkas.',
    ];

    private const DIKTUM = [
        'KESATU', 'KEDUA', 'KETIGA', 'KEEMPAT', 'KELIMA', 'KEENAM', 'KETUJUH', 'KEDELAPAN', 'KESEMBILAN', 'KESEPULUH',
        'KESEBELAS', 'KEDUA BELAS', 'KETIGA BELAS', 'KEEMPAT BELAS', 'KELIMA BELAS',
    ];

    /** Bagian yang dipakai suatu jenis, berurutan. */
    public static function bagian(string $jenis): array
    {
        return $jenis === 'Keputusan Bupati'
            ? array_diff_key(self::BAGIAN, ['batang_tubuh' => true])
            : self::BAGIAN;
    }

    /** Baris awal untuk naskah yang diketik langsung tanpa unggah Word. */
    public static function kerangka(string $jenis, string $tentang): array
    {
        $tentang = mb_strtoupper($tentang);
        $penutup = self::penutup($jenis);

        if ($jenis === 'Keputusan Bupati') {
            return [
                // Nomor Keputusan: kode klasifikasi/nomor urut/kode unit/TAHUN, misalnya 412/44/412.31/TAHUN 2025.
                ['bagian' => 'judul', 'label' => null, 'teks' => "KEPUTUSAN BUPATI HULU SUNGAI TENGAH\nNOMOR .../.../.../TAHUN ...\nTENTANG\n{$tentang}"],
                ['bagian' => 'menimbang', 'label' => null, 'teks' => 'bahwa ...'],
                ['bagian' => 'mengingat', 'label' => null, 'teks' => ''],
                ['bagian' => 'diktum', 'label' => null, 'teks' => ''],
                ['bagian' => 'diktum', 'label' => null, 'teks' => 'Keputusan Bupati ini mulai berlaku pada tanggal ditetapkan.'],
                ['bagian' => 'penutup', 'label' => null, 'teks' => $penutup],
                ['bagian' => 'lampiran', 'label' => null, 'teks' => ''],
            ];
        }

        return [
            ['bagian' => 'judul', 'label' => null, 'teks' => "PERATURAN BUPATI HULU SUNGAI TENGAH\nNOMOR ... TAHUN ...\nTENTANG\n{$tentang}"],
            ['bagian' => 'menimbang', 'label' => null, 'teks' => 'bahwa ...'],
            ['bagian' => 'mengingat', 'label' => null, 'teks' => ''],
            ['bagian' => 'diktum', 'label' => null, 'teks' => "MEMUTUSKAN:\nMenetapkan: PERATURAN BUPATI TENTANG {$tentang}."],
            ['bagian' => 'batang_tubuh', 'label' => 'BAB I', 'teks' => 'KETENTUAN UMUM'],
            ['bagian' => 'batang_tubuh', 'label' => 'Pasal 1', 'teks' => ''],
            ['bagian' => 'batang_tubuh', 'label' => 'BAB II', 'teks' => 'KETENTUAN PENUTUP'],
            ['bagian' => 'batang_tubuh', 'label' => 'Pasal 2', 'teks' => "Peraturan Bupati ini mulai berlaku pada tanggal diundangkan.\nAgar setiap orang mengetahuinya, memerintahkan pengundangan Peraturan Bupati ini dengan penempatannya dalam Berita Daerah Kabupaten Hulu Sungai Tengah."],
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

        // Tempat penetapan diatur di menu Format Dokumen; berlaku untuk naskah yang dibuat sesudahnya.
        $tempat = FormatDokumen::naskah($jenis)['penutup']['tempat'];
        $ditetapkan = "Ditetapkan di {$tempat}\npada tanggal ...\n\nBUPATI HULU SUNGAI TENGAH,\n\n\n\n{$bupati}";

        if ($jenis === 'Keputusan Bupati') {
            return $ditetapkan;
        }

        $sekda = Pengaturan::ambil(Pengaturan::NAMA_SEKDA, $titik);

        return "{$ditetapkan}\n\nDiundangkan di {$tempat}\npada tanggal ...\n\nSEKRETARIS DAERAH\nKABUPATEN HULU SUNGAI TENGAH,\n\n\n\n{$sekda}"
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
     * Pertimbangan yang hanya satu ditulis tanpa huruf, seperti pada produk hukum yang terbit.
     *
     * @param  Collection<int, NaskahBaris>  $baris  urut menurut kolom urutan
     * @return array<int, string|null> id baris => penanda
     */
    public static function penanda(Naskah $naskah, Collection $baris): array
    {
        $hitung = ['menimbang' => 0, 'mengingat' => 0, 'diktum' => 0];
        $hasil = [];
        $satuPertimbangan = $baris->filter(fn (NaskahBaris $b) => $b->bagian === 'menimbang' && $b->status !== NaskahBaris::DIHAPUS)->count() === 1;

        foreach ($baris as $item) {
            $hidup = $item->status !== NaskahBaris::DIHAPUS;

            $hasil[$item->id] = match (true) {
                ! $hidup => $item->label,
                $item->bagian === 'menimbang' && $satuPertimbangan => null,
                $item->bagian === 'menimbang' => self::huruf(++$hitung['menimbang']) . '.',
                $item->bagian === 'mengingat' => ++$hitung['mengingat'] . '.',
                $item->bagian === 'diktum' && $naskah->jenis === 'Keputusan Bupati' => self::DIKTUM[$hitung['diktum']++] ?? null,
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
