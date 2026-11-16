<?php

namespace Database\Seeders;

use App\Models\Naskah;
use App\Models\NaskahBaris;
use App\Models\TandaTangan;
use App\Models\User;
use App\Services\AlurNaskah;
use App\Support\TemplateNaskah;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Illuminate\Support\Facades\DB;

/**
 * Rancangan contoh milik akun "perangkatdaerah" di berbagai tahap, untuk melihat format
 * naskah terbaru (lokal saja). Hanya menambah data dan aman dijalankan berulang:
 *   php artisan db:seed --class=V2RancanganPdSeeder
 *
 * Isinya rekaan untuk keperluan tampilan. Pejabat ditulis dengan jabatan, bukan nama orang.
 */
class V2RancanganPdSeeder extends Seeder
{
    private const UU_PEMDA = 'Undang-Undang Nomor 23 Tahun 2014 tentang Pemerintahan Daerah (Lembaran Negara Republik Indonesia Tahun 2014 Nomor 244, Tambahan Lembaran Negara Republik Indonesia Nomor 5587);';

    private const BERLAKU_PERBUP = "Peraturan Bupati ini mulai berlaku pada tanggal diundangkan.\nAgar setiap orang mengetahuinya, memerintahkan pengundangan Peraturan Bupati ini dengan penempatannya dalam Berita Daerah Kabupaten Hulu Sungai Tengah.";

    private const BERLAKU_KEPUTUSAN = 'Keputusan Bupati ini mulai berlaku pada tanggal ditetapkan.';

    private User $pd;

    private User $admin;

    private User $verifikator;

    private User $peneliti;

    public function run(AlurNaskah $alur): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Seeder contoh tidak boleh dijalankan di production.');
        }

        $this->pd = User::where('NIP', 'perangkatdaerah')->first()
            ?? throw new RuntimeException('Akun "perangkatdaerah" belum ada. Jalankan UserSeeder terlebih dahulu.');
        $this->admin = $this->akun('admin', 'Admin');
        $this->verifikator = $this->akun('verifikator', 'Verifikator');
        $this->peneliti = $this->akun('peneliti', 'Peneliti');

        foreach ($this->daftar() as $tentang => $contoh) {
            if (Naskah::where('pemrakarsa_id', $this->pd->id)->where('tentang', $tentang)->exists()) {
                $this->command?->warn("Sudah ada, dilewati: {$tentang}");

                continue;
            }

            $naskah = $this->buat($alur, $contoh['jenis'], $tentang, $contoh);

            if (isset($contoh['lanjut'])) {
                $contoh['lanjut']($alur, $naskah);
            }

            $naskah->refresh();
            $this->command?->info("{$naskah->nomor}  {$naskah->tahap->label()}  {$tentang}");
        }
    }

    private function akun(string $nip, string $peran): User
    {
        return User::where('NIP', $nip)->first() ?? User::role($peran)->firstOrFail();
    }

    /** Isi contoh per naskah: tiap jenis memperlihatkan butir, tabel, tembusan, dan perbandingan. */
    private function daftar(): array
    {
        return [
            'Pembentukan Tim Koordinasi Penanggulangan Kemiskinan Daerah' => [
                'jenis' => 'Keputusan Bupati',
                'menimbang' => [
                    'bahwa dalam rangka mempercepat penanggulangan kemiskinan di daerah diperlukan koordinasi lintas perangkat daerah;',
                    'bahwa berdasarkan pertimbangan sebagaimana dimaksud dalam huruf a, perlu menetapkan Keputusan Bupati tentang Pembentukan Tim Koordinasi Penanggulangan Kemiskinan Daerah;',
                ],
                'mengingat' => [
                    self::UU_PEMDA,
                    'Peraturan Presiden Nomor 15 Tahun 2010 tentang Percepatan Penanggulangan Kemiskinan (Lembaran Negara Republik Indonesia Tahun 2010 Nomor 10);',
                ],
                'diktum' => [
                    "Membentuk Tim Koordinasi Penanggulangan Kemiskinan Daerah dengan susunan sebagai berikut:\n| No | Jabatan | Kedudukan dalam Tim |\n| --- | --- | --- |\n| 1 | Sekretaris Daerah | Ketua |\n| 2 | Kepala Badan Perencanaan Pembangunan Daerah | Wakil Ketua |\n| 3 | Kepala Dinas Sosial | Sekretaris |\n| 4 | Kepala Dinas Pemberdayaan Masyarakat dan Desa | Anggota |",
                    "Tim sebagaimana dimaksud dalam Diktum KESATU bertugas:\na. menyusun rencana penanggulangan kemiskinan daerah;\nb. mengoordinasikan pelaksanaan program pada perangkat daerah:\n1. bidang perlindungan sosial;\n2. bidang pemberdayaan masyarakat; dan\nc. melaporkan hasil pelaksanaan tugas kepada Bupati setiap 6 (enam) bulan.",
                    'Biaya pelaksanaan tugas Tim dibebankan pada Anggaran Pendapatan dan Belanja Daerah Kabupaten Hulu Sungai Tengah.',
                    self::BERLAKU_KEPUTUSAN,
                ],
                'tembusan' => "Tembusan :\n1. Gubernur Kalimantan Selatan di Banjarbaru;\n2. Inspektur Daerah Kabupaten Hulu Sungai Tengah.",
            ],

            'Penetapan Desa Sadar Hukum' => [
                'jenis' => 'Keputusan Bupati',
                // Satu pertimbangan: dicetak tanpa huruf a.
                'menimbang' => ['bahwa untuk meningkatkan kesadaran hukum masyarakat desa, perlu menetapkan Keputusan Bupati tentang Penetapan Desa Sadar Hukum;'],
                'mengingat' => [
                    'Undang-Undang Nomor 6 Tahun 2014 tentang Desa (Lembaran Negara Republik Indonesia Tahun 2014 Nomor 7, Tambahan Lembaran Negara Republik Indonesia Nomor 5495);',
                    self::UU_PEMDA,
                ],
                'diktum' => [
                    "Menetapkan desa sadar hukum sebagai berikut:\na. Desa Contoh Satu;\nb. Desa Contoh Dua; dan\nc. Desa Contoh Tiga.",
                    'Pemerintah Desa sebagaimana dimaksud dalam Diktum KESATU melaporkan kegiatan penyuluhan hukum setiap tahun.',
                    self::BERLAKU_KEPUTUSAN,
                ],
                'lanjut' => function (AlurNaskah $alur, Naskah $naskah) {
                    $this->sampaiPenelitian($alur, $naskah);
                    $diktum = $naskah->baris()->where('bagian', 'diktum')->orderBy('urutan')->skip(1)->firstOrFail();
                    $naskah->baris()->whereKeyNot($diktum->id)->get()->each(fn ($b) => $alur->tetapkan($b, $this->peneliti));
                    $alur->mintaPerbaikan($diktum, $this->peneliti, 'Sebutkan perangkat daerah yang menerima laporan kegiatan.');
                    $alur->jalankan($naskah, $this->peneliti, 'kirim_ke_pd');
                },
            ],

            'Pembentukan Kelompok Kerja Operasional Posyandu' => [
                'jenis' => 'Keputusan Bupati',
                'menimbang' => [
                    'bahwa pelayanan kesehatan dasar di posyandu perlu dibina secara terpadu;',
                    'bahwa berdasarkan pertimbangan sebagaimana dimaksud dalam huruf a, perlu menetapkan Keputusan Bupati tentang Pembentukan Kelompok Kerja Operasional Posyandu;',
                ],
                'mengingat' => [self::UU_PEMDA],
                'diktum' => [
                    "Membentuk Kelompok Kerja Operasional Posyandu yang bertugas:\na. membina kader posyandu;\nb. memantau pelayanan posyandu; dan\nc. melaporkan hasil pembinaan.",
                    self::BERLAKU_KEPUTUSAN,
                ],
                'lanjut' => function (AlurNaskah $alur, Naskah $naskah) {
                    $this->sampaiPenelitian($alur, $naskah);
                    $naskah->baris()->whereIn('bagian', ['judul', 'menimbang', 'mengingat'])->get()->each(fn ($b) => $alur->tetapkan($b, $this->peneliti));

                    // Perbandingan pada butir diktum: ada yang diubah dan ada yang ditambah.
                    $alur->perbaiki(
                        $naskah->baris()->where('bagian', 'diktum')->orderBy('urutan')->firstOrFail(),
                        $this->peneliti,
                        "Membentuk Kelompok Kerja Operasional Posyandu yang bertugas:\na. membina dan mendampingi kader posyandu;\nb. memantau pelayanan posyandu;\nc. mengevaluasi kegiatan posyandu setiap triwulan; dan\nd. melaporkan hasil pembinaan kepada Bupati.",
                        'Merinci tugas dan penerima laporan.',
                    );
                },
            ],

            'Penyelenggaraan Perpustakaan Desa' => [
                'jenis' => 'Peraturan Bupati',
                'menimbang' => [
                    'bahwa perpustakaan desa merupakan sarana untuk meningkatkan literasi masyarakat;',
                    'bahwa berdasarkan pertimbangan sebagaimana dimaksud dalam huruf a, perlu menetapkan Peraturan Bupati tentang Penyelenggaraan Perpustakaan Desa;',
                ],
                'mengingat' => [
                    'Undang-Undang Nomor 43 Tahun 2007 tentang Perpustakaan (Lembaran Negara Republik Indonesia Tahun 2007 Nomor 129, Tambahan Lembaran Negara Republik Indonesia Nomor 4774);',
                    'Undang-Undang Nomor 6 Tahun 2014 tentang Desa (Lembaran Negara Republik Indonesia Tahun 2014 Nomor 7, Tambahan Lembaran Negara Republik Indonesia Nomor 5495);',
                    self::UU_PEMDA,
                ],
                'batang_tubuh' => [
                    ['BAB I', 'KETENTUAN UMUM'],
                    ['Pasal 1', "Dalam Peraturan Bupati ini yang dimaksud dengan:\n1. Daerah adalah Kabupaten Hulu Sungai Tengah.\n2. Bupati adalah Bupati Hulu Sungai Tengah.\n3. Perpustakaan Desa adalah perpustakaan yang diselenggarakan oleh Pemerintah Desa."],
                    ['BAB II', 'PENYELENGGARAAN'],
                    ['Pasal 2', "(1) Pemerintah Desa menyelenggarakan Perpustakaan Desa.\n(2) Penyelenggaraan sebagaimana dimaksud pada ayat (1) meliputi:\na. penyediaan ruang dan koleksi bahan pustaka;\nb. layanan perpustakaan, yang terdiri atas:\n1. layanan baca di tempat; dan\n2. layanan peminjaman; dan\nc. pembinaan pengelola perpustakaan."],
                    ['Pasal 3', "Standar koleksi Perpustakaan Desa paling sedikit sebagai berikut:\n| No | Jenis Koleksi | Jumlah Paling Sedikit |\n| --- | --- | --- |\n| 1 | Buku pengetahuan | 500 judul |\n| 2 | Buku pertanian dan usaha desa | 100 judul |\n| 3 | Buku anak | 200 judul |"],
                    ['BAB III', 'KETENTUAN PENUTUP'],
                    ['Pasal 4', self::BERLAKU_PERBUP],
                ],
            ],

            'Standar Pelayanan pada Mal Pelayanan Publik' => [
                'jenis' => 'Peraturan Bupati',
                'menimbang' => [
                    'bahwa pelayanan publik perlu diselenggarakan secara terpadu dalam satu tempat;',
                    'bahwa untuk memberikan kepastian pelayanan perlu disusun standar pelayanan;',
                    'bahwa berdasarkan pertimbangan sebagaimana dimaksud dalam huruf a dan huruf b, perlu menetapkan Peraturan Bupati tentang Standar Pelayanan pada Mal Pelayanan Publik;',
                ],
                'mengingat' => [
                    'Undang-Undang Nomor 25 Tahun 2009 tentang Pelayanan Publik (Lembaran Negara Republik Indonesia Tahun 2009 Nomor 112, Tambahan Lembaran Negara Republik Indonesia Nomor 5038);',
                    self::UU_PEMDA,
                ],
                'batang_tubuh' => [
                    ['BAB I', 'KETENTUAN UMUM'],
                    ['Pasal 1', "Dalam Peraturan Bupati ini yang dimaksud dengan:\n1. Daerah adalah Kabupaten Hulu Sungai Tengah.\n2. Mal Pelayanan Publik adalah tempat berlangsungnya kegiatan penyelenggaraan pelayanan publik."],
                    ['BAB II', 'STANDAR PELAYANAN'],
                    ['Pasal 2', "(1) Setiap penyelenggara menetapkan standar pelayanan.\n(2) Standar pelayanan diumumkan melalui papan informasi."],
                    ['Pasal 3', 'Penyelenggara dapat mengenakan biaya tambahan di luar ketentuan.'],
                    ['BAB III', 'KETENTUAN PENUTUP'],
                    ['Pasal 4', self::BERLAKU_PERBUP],
                ],
                'lanjut' => function (AlurNaskah $alur, Naskah $naskah) {
                    $this->sampaiPenelitian($alur, $naskah);
                    $naskah->baris()->whereIn('bagian', ['judul', 'menimbang'])->get()->each(fn ($b) => $alur->tetapkan($b, $this->peneliti));

                    // Tiga bentuk perubahan Peneliti: mengubah, menghapus, dan menyisipkan baris.
                    $alur->perbaiki(
                        $naskah->baris()->where('label', 'Pasal 2')->firstOrFail(),
                        $this->peneliti,
                        "(1) Setiap penyelenggara wajib menetapkan standar pelayanan.\n(2) Standar pelayanan sebagaimana dimaksud pada ayat (1) meliputi:\na. persyaratan;\nb. jangka waktu penyelesaian; dan\nc. biaya pelayanan.\n(3) Standar pelayanan diumumkan melalui papan informasi dan laman resmi Pemerintah Daerah.",
                        'Merinci komponen standar pelayanan dan sarana pengumuman.',
                    );
                    $alur->hapus(
                        $naskah->baris()->where('label', 'Pasal 3')->firstOrFail(),
                        $this->peneliti,
                        'Bertentangan dengan prinsip pelayanan publik.',
                    );
                    $sisipan = $alur->tambahSetelah($naskah->baris()->where('bagian', 'mengingat')->get()->last(), $this->peneliti);
                    $alur->perbaiki($sisipan, $this->peneliti, 'Peraturan Presiden Nomor 89 Tahun 2021 tentang Penyelenggaraan Mal Pelayanan Publik (Lembaran Negara Republik Indonesia Tahun 2021 Nomor 221);', 'Menambah dasar hukum.');
                },
            ],

            'Pedoman Pengelolaan Arsip Dinamis' => [
                'jenis' => 'Peraturan Bupati',
                'menimbang' => ['bahwa untuk menjamin ketersediaan arsip yang autentik dan terpercaya, perlu menetapkan Peraturan Bupati tentang Pedoman Pengelolaan Arsip Dinamis;'],
                'mengingat' => [
                    'Undang-Undang Nomor 43 Tahun 2009 tentang Kearsipan (Lembaran Negara Republik Indonesia Tahun 2009 Nomor 152, Tambahan Lembaran Negara Republik Indonesia Nomor 5071);',
                    self::UU_PEMDA,
                ],
                'batang_tubuh' => [
                    ['BAB I', 'KETENTUAN UMUM'],
                    ['Pasal 1', "Dalam Peraturan Bupati ini yang dimaksud dengan:\n1. Daerah adalah Kabupaten Hulu Sungai Tengah.\n2. Arsip Dinamis adalah arsip yang digunakan secara langsung dalam kegiatan pencipta arsip."],
                    ['BAB II', 'PENGELOLAAN'],
                    ['Pasal 2', "Pengelolaan Arsip Dinamis meliputi:\na. penciptaan arsip;\nb. penggunaan arsip;\nc. pemeliharaan arsip; dan\nd. penyusutan arsip."],
                    ['BAB III', 'KETENTUAN PENUTUP'],
                    ['Pasal 3', self::BERLAKU_PERBUP],
                ],
                'lanjut' => function (AlurNaskah $alur, Naskah $naskah) {
                    $this->sampaiPenelitian($alur, $naskah);
                    $pasal = $naskah->baris()->where('label', 'Pasal 2')->firstOrFail();
                    $naskah->baris()->whereKeyNot($pasal->id)->get()->each(fn ($b) => $alur->tetapkan($b, $this->peneliti));
                    $alur->perbaiki($pasal, $this->peneliti, "Pengelolaan Arsip Dinamis dilaksanakan oleh setiap perangkat daerah dan meliputi:\na. penciptaan arsip;\nb. penggunaan arsip;\nc. pemeliharaan arsip; dan\nd. penyusutan arsip.", 'Menegaskan pelaksana.');
                    $alur->jalankan($naskah, $this->peneliti, 'teruskan_ke_verifikator');
                },
            ],

            'Pedoman Penyelenggaraan Forum Anak Daerah' => [
                'jenis' => 'Peraturan Bupati',
                'menimbang' => [
                    'bahwa anak berhak menyampaikan pendapat dalam proses pembangunan daerah;',
                    'bahwa berdasarkan pertimbangan sebagaimana dimaksud dalam huruf a, perlu menetapkan Peraturan Bupati tentang Pedoman Penyelenggaraan Forum Anak Daerah;',
                ],
                'mengingat' => [
                    'Undang-Undang Nomor 35 Tahun 2014 tentang Perubahan atas Undang-Undang Nomor 23 Tahun 2002 tentang Perlindungan Anak (Lembaran Negara Republik Indonesia Tahun 2014 Nomor 297, Tambahan Lembaran Negara Republik Indonesia Nomor 5606);',
                    self::UU_PEMDA,
                ],
                'batang_tubuh' => [
                    ['BAB I', 'KETENTUAN UMUM'],
                    ['Pasal 1', 'Forum Anak Daerah adalah wadah partisipasi anak di Kabupaten Hulu Sungai Tengah.'],
                    ['BAB II', 'KETENTUAN PENUTUP'],
                    ['Pasal 2', self::BERLAKU_PERBUP],
                ],
                'lanjut' => function (AlurNaskah $alur, Naskah $naskah) {
                    $this->sampaiPenelitian($alur, $naskah);
                    $naskah->baris()->get()->each(fn ($b) => $alur->tetapkan($b, $this->peneliti));
                    $alur->jalankan($naskah, $this->peneliti, 'teruskan_ke_verifikator');
                    $alur->jalankan($naskah, $this->verifikator, 'setujui_rancangan');
                    $alur->jalankan($naskah, $this->pd, 'ajukan_fasilitasi');
                    $alur->jalankan($naskah, $this->peneliti, 'setujui_fasilitasi');
                    $alur->jalankan($naskah, $this->verifikator, 'finalkan');

                    $this->terbitkanNota($alur, $naskah);
                },
            ],
        ];
    }

    private function buat(AlurNaskah $alur, string $jenis, string $tentang, array $contoh): Naskah
    {
        return DB::transaction(function () use ($alur, $jenis, $tentang, $contoh) {
            $naskah = Naskah::create([
                'jenis' => $jenis,
                'tentang' => $tentang,
                'pemrakarsa_id' => $this->pd->id,
                'nomor_nota' => '180/' . random_int(100, 999) . '/ND/' . now()->year,
                'tanggal_nota' => now(),
            ]);

            $alur->isiBaris($naskah, $this->baris($jenis, $tentang, $contoh));

            $path = "naskah/{$naskah->slug}/nota-dinas.pdf";
            Storage::disk('local')->put($path, Pdf::loadHTML('<p>Contoh Nota Dinas Perangkat Daerah untuk tampilan.</p>')->output());
            $naskah->berkas()->create(['jenis' => 'nota_dinas_pd', 'path' => $path, 'nama_asli' => 'nota-dinas.pdf', 'diunggah_oleh' => $this->pd->id]);

            return $naskah;
        });
    }

    /** Memakai kerangka resmi untuk judul, pembuka, dan penutup; isinya dari contoh. */
    private function baris(string $jenis, string $tentang, array $contoh): array
    {
        $kerangka = collect(TemplateNaskah::kerangka($jenis, $tentang));
        $hasil = [];

        foreach (array_keys(TemplateNaskah::bagian($jenis)) as $bagian) {
            $isi = $contoh[$bagian] ?? null;

            if ($isi === null) {
                array_push($hasil, ...$kerangka->where('bagian', $bagian)->values()->all());

                continue;
            }

            foreach ($isi as $item) {
                [$label, $teks] = is_array($item) ? $item : [null, $item];
                $hasil[] = ['bagian' => $bagian, 'label' => $label, 'teks' => $teks];
            }
        }

        if (isset($contoh['tembusan'])) {
            $penutup = array_search('penutup', array_column($hasil, 'bagian'), true);
            $hasil[$penutup]['teks'] .= "\n\n{$contoh['tembusan']}";
        }

        return $hasil;
    }

    private function sampaiPenelitian(AlurNaskah $alur, Naskah $naskah): void
    {
        $alur->jalankan($naskah, $this->pd, 'ajukan');
        $alur->jalankan($naskah, $this->admin, 'setujui_berkas');
        $alur->jalankan($naskah, $this->verifikator, 'tugaskan_peneliti', null, $this->peneliti->id);
    }

    /** Tanpa tanda tangan aktif, naskah berhenti pada tahap Menunggu Nota Dinas. */
    private function terbitkanNota(AlurNaskah $alur, Naskah $naskah): void
    {
        $ttd = TandaTangan::where('status', 'Aktif')->first();

        if (! $ttd) {
            $this->command?->warn('Belum ada tanda tangan aktif, nota dinas contoh tidak diterbitkan.');

            return;
        }

        // Nomor bisa sudah terpakai oleh data lain, jadi dicari yang masih kosong dari atas.
        foreach (range(999, 900) as $nomor) {
            try {
                $alur->terbitkanNota($naskah, $this->admin, (string) $nomor, 'Sekretaris Daerah', $ttd->id);
                $alur->tandaiTahapan($naskah, $this->admin);

                return;
            } catch (ValidationException) {
                continue;
            }
        }
    }
}
