<?php

namespace Database\Seeders;

use App\Models\DokumentasiProdukHukum;
use App\Models\FasilitasiProdukHukum;
use App\Models\NotaDinas;
use App\Models\PerangkatDaerah;
use App\Models\RancanganProdukHukum;
use App\Models\Revisi;
use App\Models\TandaTangan;
use App\Models\User;
use App\Notifications\ValidationResultNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Data contoh untuk melihat tampilan semua halaman (khusus lingkungan lokal).
 *
 *   php artisan db:seed --class=DemoSeeder
 *
 * Hanya menambah data, tidak menghapus apa pun. Membutuhkan PerangkatDaerahSeeder,
 * RoleSeeder, dan UserSeeder sudah dijalankan. Setiap tahap alur diwakili minimal
 * satu rancangan, lengkap dengan file contoh (PDF, Word, PNG) di storage/app/private.
 */
class DemoSeeder extends Seeder
{
    /** NIP penanda bahwa data demo sudah pernah dibuat. */
    private const PENANDA = 'demo_pd_dinkes';

    private array $file = [];

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoSeeder tidak boleh dijalankan di production.');
        }

        if (User::where('NIP', self::PENANDA)->exists()) {
            $this->command?->warn('Data demo sudah ada, seeder dilewati.');
            return;
        }

        $peneliti = [User::where('NIP', 'peneliti')->first(), User::where('NIP', 'peneliti2')->first()];
        $verifikator = User::where('NIP', 'verifikator')->first();
        $admin = User::where('NIP', 'admin')->first();

        if (in_array(null, [...$peneliti, $verifikator, $admin], true)) {
            throw new RuntimeException('Jalankan UserSeeder terlebih dahulu (php artisan db:seed).');
        }

        try {
            $this->buatFileContoh();
            $pengaju = $this->buatPerangkatDaerah();
            $ttd = $this->buatTandaTangan($verifikator);

            foreach ($this->skenario() as $i => [$tanggal, $jenis, $tentang, $tahap]) {
                $this->buatAlur($i, Carbon::parse($tanggal), $jenis, $tentang, $tahap, $pengaju[$i % count($pengaju)], $peneliti[$i % 2], $ttd);
            }

            Carbon::setTestNow();
            $this->buatArsipManual();
            $this->buatNotifikasi($peneliti[0], $verifikator, $admin);
        } finally {
            Carbon::setTestNow();
        }

        $this->command?->info(sprintf(
            'Data demo dibuat: %d rancangan, %d fasilitasi, %d nota dinas, %d dokumentasi.',
            RancanganProdukHukum::count(), FasilitasiProdukHukum::count(), NotaDinas::count(), DokumentasiProdukHukum::count(),
        ));
    }

    /**
     * [tanggal pengajuan, jenis, tentang, tahap terakhir yang dicapai].
     * Urut kronologis karena nomor rancangan dibuat berurutan per tahun.
     */
    private function skenario(): array
    {
        return [
            ['2025-02-10 09:15', 'Peraturan Bupati', 'Pedoman Pengelolaan Barang Milik Daerah', 'selesai'],
            ['2025-03-04 10:30', 'Surat Keputusan', 'Pembentukan Tim Koordinasi Penanggulangan Kemiskinan Daerah', 'selesai'],
            ['2025-05-19 13:45', 'Peraturan Bupati', 'Standar Pelayanan Minimal Bidang Kesehatan', 'selesai'],
            ['2025-08-07 08:50', 'Surat Keputusan', 'Penetapan Desa Wisata di Kabupaten Hulu Sungai Tengah', 'selesai'],
            ['2025-10-21 14:10', 'Peraturan Bupati', 'Tata Cara Pemberian Hibah dan Bantuan Sosial', 'ditolak'],
            ['2026-01-12 09:05', 'Peraturan Bupati', 'Penjabaran Anggaran Pendapatan dan Belanja Daerah Tahun 2026', 'selesai'],
            ['2026-02-03 11:20', 'Surat Keputusan', 'Pembentukan Satuan Tugas Percepatan Penurunan Stunting', 'selesai_tanpa_arsip'],
            ['2026-03-16 10:00', 'Peraturan Bupati', 'Kedudukan, Susunan Organisasi, dan Tugas Dinas Pendidikan', 'fas_paraf'],
            ['2026-04-08 15:30', 'Surat Keputusan', 'Tim Pengendali Inflasi Daerah', 'fas_diterima'],
            ['2026-05-14 08:40', 'Peraturan Bupati', 'Pelaksanaan Sistem Pemerintahan Berbasis Elektronik', 'fas_validasi'],
            ['2026-06-02 13:15', 'Peraturan Bupati', 'Pengelolaan Sampah Rumah Tangga', 'fas_ditolak'],
            ['2026-06-25 09:45', 'Surat Keputusan', 'Penetapan Lokasi Kawasan Agropolitan', 'fas_menunggu'],
            ['2026-07-09 10:25', 'Peraturan Bupati', 'Rencana Kerja Pemerintah Daerah Tahun 2027', 'disetujui'],
            ['2026-07-30 14:00', 'Surat Keputusan', 'Pemberian Tambahan Penghasilan Pegawai', 'disetujui'],
            ['2026-08-18 09:30', 'Peraturan Bupati', 'Pengelolaan Air Limbah Domestik', 'validasi'],
            ['2026-09-01 11:10', 'Surat Keputusan', 'Penetapan Cagar Budaya Kabupaten', 'validasi'],
            ['2026-09-15 08:20', 'Peraturan Bupati', 'Penyelenggaraan Perlindungan Anak', 'revisi'],
            ['2026-09-29 13:40', 'Peraturan Bupati', 'Pedoman Penyusunan Produk Hukum Desa', 'revisi'],
            ['2026-10-06 10:05', 'Surat Keputusan', 'Pembentukan Forum Kota Layak Anak', 'pilih_peneliti'],
            ['2026-10-12 09:00', 'Peraturan Bupati', 'Tarif Layanan Badan Layanan Umum Daerah RSUD', 'pilih_peneliti'],
            ['2026-10-14 15:20', 'Surat Keputusan', 'Penetapan Pemenang Lomba Desa Tingkat Kabupaten', 'ditolak'],
            ['2026-10-19 10:45', 'Peraturan Bupati', 'Pengelolaan Ruang Terbuka Hijau', 'menunggu'],
            ['2026-10-21 13:30', 'Surat Keputusan', 'Tim Percepatan Penurunan Angka Kematian Ibu', 'menunggu'],
            ['2026-10-23 08:55', 'Peraturan Bupati', 'Pengendalian Penyakit Menular', 'menunggu'],
        ];
    }

    private function buatAlur(int $i, Carbon $tgl, string $jenis, string $tentang, string $tahap, User $pengaju, User $peneliti, TandaTangan $ttd): void
    {
        $urutan = ['menunggu', 'ditolak', 'pilih_peneliti', 'revisi', 'validasi', 'disetujui', 'fas_menunggu', 'fas_ditolak',
            'fas_validasi', 'fas_diterima', 'fas_paraf', 'selesai_tanpa_arsip', 'selesai'];
        $sampai = fn (string $t) => array_search($tahap, $urutan) >= array_search($t, $urutan) && $tahap !== 'ditolak';

        // Tanggal tahap: N hari setelah pengajuan, tidak melewati waktu sebenarnya.
        Carbon::setTestNow();
        $batas = now()->subHour();
        $hari = fn (int $n) => $tgl->copy()->addDays($n)->min($batas);

        Carbon::setTestNow($tgl); // nomor rancangan & created_at mengikuti tanggal pengajuan

        $rancangan = RancanganProdukHukum::create([
            'jenis_rancangan' => $jenis,
            'tentang' => $tentang,
            'nomor_nota' => sprintf('100.3.3/%03d/PD/%d', 40 + $i, $tgl->year),
            'tanggal_nota' => $tgl->copy()->subDays(3),
            'nota_dinas_pd' => $this->file['nota_pd'],
            'rancangan' => $this->file['rancangan'],
            'matrik' => $this->file['matrik'],
            'bahan_pendukung' => $i % 3 ? $this->file['bahan'] : null,
            'tanggal_pengajuan' => $tgl,
            'id_user' => $pengaju->id,
            'status_berkas' => match (true) {
                $tahap === 'menunggu' => 'Menunggu Persetujuan',
                $tahap === 'ditolak' => 'Ditolak',
                default => 'Disetujui',
            },
            'status_rancangan' => match (true) {
                $tahap === 'ditolak' => 'Ditolak',
                $sampai('disetujui') => 'Disetujui',
                default => 'Dalam Proses',
            },
            'catatan_berkas' => match ($tahap) {
                'ditolak' => 'Matrik belum ditandatangani kepala perangkat daerah dan nota dinas belum bernomor.',
                'menunggu' => null,
                default => 'Berkas lengkap, diteruskan ke verifikator.',
            },
            'tanggal_berkas_disetujui' => in_array($tahap, ['menunggu', 'ditolak']) ? null : $hari(1),
        ]);
        $rancangan->forceFill(['tanggal_rancangan_disetujui' => $sampai('disetujui') ? $hari(12) : null])->save();

        if (in_array($tahap, ['menunggu', 'ditolak'])) {
            return;
        }

        $ditunjuk = $tahap !== 'pilih_peneliti';
        $direvisi = $sampai('validasi');
        Revisi::create([
            'id_rancangan' => $rancangan->id_rancangan,
            'id_user' => $ditunjuk ? $peneliti->id : null,
            'status_revisi' => match (true) {
                $direvisi => 'Direvisi',
                $ditunjuk => 'Proses Revisi',
                default => 'Menunggu Peneliti',
            },
            'status_validasi' => match (true) {
                $sampai('disetujui') => 'Diterima',
                $direvisi => 'Menunggu Validasi',
                default => 'Belum Tahap Validasi',
            },
            'revisi_rancangan' => $direvisi ? $this->file['revisi_rancangan'] : null,
            'revisi_matrik' => $direvisi ? $this->file['revisi_matrik'] : null,
            'catatan_revisi' => $direvisi ? 'Perbaikan konsiderans menimbang, dasar hukum, dan sistematika pasal.' : null,
            'catatan_validasi' => $sampai('disetujui') ? 'Hasil penelitian sesuai, rancangan disetujui.' : null,
            'tanggal_peneliti_ditunjuk' => $ditunjuk ? $hari(3) : null,
            'tanggal_revisi' => $direvisi ? $hari(8) : null,
            'tanggal_validasi' => $sampai('disetujui') ? $hari(11) : null,
        ]);

        if (! $sampai('fas_menunggu')) {
            return;
        }

        $diperiksa = $tahap !== 'fas_menunggu';
        $diterima = $sampai('fas_diterima');
        $paraf = fn (int $ke) => $sampai('selesai_tanpa_arsip') || ($tahap === 'fas_paraf' && $ke <= 2);

        $fasilitasi = FasilitasiProdukHukum::create([
            'rancangan_id' => $rancangan->id_rancangan,
            'tanggal_fasilitasi' => $hari(15),
            'file_rancangan' => $this->file['fasilitasi'],
            'status_berkas_fasilitasi' => match (true) {
                $tahap === 'fas_ditolak' => 'Ditolak',
                $diperiksa => 'Disetujui',
                default => 'Menunggu Persetujuan',
            },
            'catatan_persetujuan_fasilitasi' => match (true) {
                $tahap === 'fas_ditolak' => 'Format penomoran pasal belum sesuai hasil penelitian, mohon diperbaiki.',
                $diperiksa => 'Berkas fasilitasi sesuai hasil penelitian.',
                default => null,
            },
            'tanggal_persetujuan_berkas' => $diperiksa ? $hari(16) : null,
            'status_validasi_fasilitasi' => match (true) {
                $diterima => 'Diterima',
                $tahap === 'fas_validasi' => 'Menunggu Validasi',
                default => 'Belum Tahap Validasi',
            },
            'catatan_validasi_fasilitasi' => $diterima ? 'Fasilitasi divalidasi, lanjut pembuatan nota dinas.' : null,
            'tanggal_validasi_fasilitasi' => $diterima ? $hari(18) : null,
            'status_paraf_koordinasi' => $paraf(1) ? 'Selesai' : 'Belum',
            'tanggal_paraf_koordinasi' => $paraf(1) ? $hari(21) : null,
            'status_asisten' => $paraf(2) ? 'Selesai' : 'Belum',
            'tanggal_asisten' => $paraf(2) ? $hari(23) : null,
            'status_sekda' => $paraf(3) ? 'Selesai' : 'Belum',
            'tanggal_sekda' => $paraf(3) ? $hari(25) : null,
            'status_bupati' => $paraf(4) ? 'Selesai' : 'Belum',
            'tanggal_bupati' => $paraf(4) ? $hari(27) : null,
        ]);

        if ($sampai('fas_paraf')) {
            $nomor = 10 + $i;
            NotaDinas::create([
                'fasilitasi_id' => $fasilitasi->id,
                'nomor_inputan' => $nomor,
                'nomor_nota' => sprintf('180/%d/%s/KUM/%d', $nomor, $jenis === 'Peraturan Bupati' ? 'NDPB' : 'NDSK', $tgl->year),
                'tanggal_nota' => $hari(19),
                'kepada' => 'Bupati Hulu Sungai Tengah',
                'tanda_tangan_id' => $ttd->id,
            ]);
        }

        if ($tahap === 'selesai') {
            DokumentasiProdukHukum::create([
                'rancangan_id' => $rancangan->id_rancangan,
                'nomor' => $i + 1,
                'tahun' => $tgl->year,
                'nomor_tahun_berita' => sprintf('%d/%d', 20 + $i, $tgl->year),
                'tanggal_penetapan' => $hari(28),
                'tanggal_pengarsipan' => $hari(30),
                'file_produk_hukum' => $this->file['produk_hukum'],
                'perangkat_daerah_id' => $pengaju->perangkat_daerah_id,
                'jenis_dokumentasi' => $jenis,
                'tentang_dokumentasi' => $tentang,
            ]);
        }
    }

    /** Akun Perangkat Daerah tambahan agar daftar memuat beberapa instansi. Password: "password". */
    private function buatPerangkatDaerah(): array
    {
        $akun = [
            [self::PENANDA, 'dr. Rina Marlina', 'Kesehatan'],
            ['demo_pd_disdik', 'Hj. Siti Aminah, S.Pd.', 'Pendidikan'],
            ['demo_pd_dlh', 'Ir. Bambang Hidayat', 'Lingkungan Hidup'],
        ];

        return collect($akun)->map(function ($data) {
            [$nip, $nama, $dinas] = $data;

            $user = User::create([
                'NIP' => $nip,
                'nama_user' => $nama,
                'email' => "{$nip}@example.com",
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'perangkat_daerah_id' => (PerangkatDaerah::where('nama_perangkat_daerah', 'like', "%{$dinas}%")->first()
                    ?? PerangkatDaerah::inRandomOrder()->first())?->id,
            ]);
            $user->assignRole('Perangkat Daerah');

            return $user;
        })->push(User::where('NIP', 'perangkatdaerah')->first())->filter()->values()->all();
    }

    private function buatTandaTangan(User $verifikator): TandaTangan
    {
        TandaTangan::create([
            'nama_ttd' => 'H. Muhammad Yusuf, S.H.', 'nip_ttd' => '196805121994031005',
            'jabatan' => 'Kepala Bagian Hukum (2020–2024)', 'file_ttd' => $this->file['ttd'],
            'dibuat_oleh' => $verifikator->id, 'status' => 'Tidak Aktif',
        ]);

        return TandaTangan::create([
            'nama_ttd' => 'Dr. H. Ahmad Fauzi, S.H., M.H.', 'nip_ttd' => '197501012000031001',
            'jabatan' => 'Pembina Tk. I', 'file_ttd' => $this->file['ttd'],
            'dibuat_oleh' => $verifikator->id, 'status' => 'Aktif',
        ]);
    }

    /** Arsip produk hukum sebelum ada sistem (tanpa rancangan). */
    private function buatArsipManual(): void
    {
        $arsip = [
            [2023, 4, 'Peraturan Bupati', 'Pedoman Pengelolaan Keuangan Desa', 'Bagian Tata Pemerintahan'],
            [2023, 17, 'Keputusan Bupati', 'Penetapan Lokasi Pasar Rakyat', 'Bagian Perekonomian'],
            [2024, 9, 'Peraturan Bupati', 'Tata Kelola Teknologi Informasi dan Komunikasi', 'Komunikasi'],
            [2024, 22, 'Keputusan Bupati', 'Pembentukan Tim Penilai Kinerja Aparatur', 'Kepegawaian'],
        ];

        foreach ($arsip as [$tahun, $nomor, $jenis, $tentang, $instansi]) {
            $ditetapkan = Carbon::create($tahun, ($nomor % 11) + 1, 15);

            DokumentasiProdukHukum::create([
                'nomor' => $nomor,
                'tahun' => $tahun,
                'nomor_tahun_berita' => "{$nomor}/{$tahun}",
                'tanggal_penetapan' => $ditetapkan,
                'tanggal_pengarsipan' => Carbon::create(2025, 1, 20)->addDays($nomor),
                'file_produk_hukum' => $this->file['produk_hukum'],
                'perangkat_daerah_id' => (PerangkatDaerah::where('nama_perangkat_daerah', 'like', "%{$instansi}%")->first()
                    ?? PerangkatDaerah::inRandomOrder()->first())?->id,
                'jenis_dokumentasi' => $jenis,
                'tentang_dokumentasi' => $tentang,
            ]);
        }
    }

    /** Beberapa notifikasi agar lonceng di topbar terisi. */
    private function buatNotifikasi(User $peneliti, User $verifikator, User $admin): void
    {
        // Notifikasi tentang rancangan terbaru di $tahap ("%s" = judulnya); $user null = pemilik rancangan.
        $kirim = function (?User $user, string $tahap, string $title, string $message, string $type, bool $dibaca = false) {
            $tentang = collect($this->skenario())->where(3, $tahap)->last()[2];
            $rancangan = RancanganProdukHukum::where('tentang', $tentang)->latest('id_rancangan')->first();
            $penerima = $user ?? $rancangan->user;

            $penerima->notify(new ValidationResultNotification([
                'title' => $title,
                'message' => sprintf($message, $tentang),
                'type' => $type,
                'slug' => $rancangan?->slug,
            ]));

            if ($dibaca) {
                $penerima->unreadNotifications()->latest()->first()?->markAsRead();
            }
        };

        $kirim($admin, 'fas_diterima', 'Fasilitasi Telah Diterima', 'Fasilitasi "%s" diterima, silakan buat nota dinas.', 'fasilitasi_diterima_admin', true);
        $kirim($admin, 'menunggu', 'Rancangan Baru Diajukan', 'Rancangan "%s" menunggu persetujuan berkas.', 'rancangan_baru');
        $kirim($verifikator, 'validasi', 'Revisi Menunggu Validasi', 'Peneliti telah mengunggah revisi "%s".', 'revisi_selesai', true);
        $kirim($verifikator, 'pilih_peneliti', 'Pilih Peneliti', 'Rancangan "%s" menunggu penunjukan peneliti.', 'pilih_peneliti');
        $kirim($peneliti, 'fas_menunggu', 'Fasilitasi Menunggu Persetujuan', 'Periksa berkas fasilitasi "%s".', 'fasilitasi_baru', true);
        $kirim($peneliti, 'revisi', 'Anda Ditunjuk sebagai Peneliti', 'Silakan teliti rancangan "%s".', 'peneliti_ditunjuk');
        $kirim(null, 'disetujui', 'Rancangan Disetujui', 'Rancangan "%s" disetujui, silakan ajukan fasilitasi.', 'rancangan_disetujui', true);
        $kirim(null, 'ditolak', 'Berkas Rancangan Ditolak', 'Berkas "%s" ditolak, periksa catatan lalu ajukan ulang.', 'rancangan_ditolak');
    }

    /** File contoh di storage/app/private; dipakai bersama oleh semua data demo. */
    private function buatFileContoh(): void
    {
        $disk = Storage::disk('local');

        $pdf = fn (string $judul) => Pdf::loadHTML(
            '<div style="font-family: sans-serif; text-align: center; margin-top: 200px">'
            . '<h1 style="color:#172b4d">' . e($judul) . '</h1>'
            . '<p style="color:#64748b">Dokumen contoh SIFKUMDA &mdash; dibuat oleh DemoSeeder untuk keperluan tampilan.</p></div>'
        )->output();

        $files = [
            'nota_pd' => ['rancangan/nota_dinas/demo_nota_dinas.pdf', $pdf('Nota Dinas Perangkat Daerah')],
            'rancangan' => ['rancangan/rancangan/demo_rancangan.docx', $this->docx('Rancangan Produk Hukum (contoh)')],
            'matrik' => ['rancangan/matrik/demo_matrik.docx', $this->docx('Matrik Rancangan (contoh)')],
            'bahan' => ['rancangan/bahan_pendukung/demo_bahan_pendukung.pdf', $pdf('Bahan Pendukung')],
            'revisi_rancangan' => ['revisi/rancangan/demo_revisi_rancangan.docx', $this->docx('Revisi Rancangan (contoh)')],
            'revisi_matrik' => ['revisi/matrik/demo_revisi_matrik.docx', $this->docx('Revisi Matrik (contoh)')],
            'fasilitasi' => ['fasilitasi/rancangan/demo_fasilitasi.pdf', $pdf('Rancangan Fasilitasi')],
            'produk_hukum' => ['dokumentasi/file_produk_hukum/demo_produk_hukum.pdf', $pdf('Produk Hukum Daerah')],
            'ttd' => ['tanda_tangan/ttd/demo_ttd.png', $this->png()],
        ];

        foreach ($files as $kunci => [$path, $isi]) {
            $disk->put($path, $isi);
            $this->file[$kunci] = $path;
        }
    }

    /** Dokumen Word minimal (.docx) berisi satu paragraf. */
    private function docx(string $teks): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            . '<w:p><w:r><w:rPr><w:b/><w:sz w:val="32"/></w:rPr><w:t>' . e($teks) . '</w:t></w:r></w:p>'
            . '<w:p><w:r><w:t>Dokumen contoh SIFKUMDA, dibuat oleh DemoSeeder.</w:t></w:r></w:p>'
            . '</w:body></w:document>');
        $zip->close();

        $isi = file_get_contents($tmp);
        unlink($tmp);

        return $isi;
    }

    /** Gambar tanda tangan contoh: coretan biru di latar transparan (400×300). */
    private function png(): string
    {
        $img = imagecreatetruecolor(400, 300);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
        imagesetthickness($img, 4);
        $tinta = imagecolorallocate($img, 23, 43, 140);

        $titik = [];
        for ($x = 40; $x <= 360; $x += 4) {
            $titik[] = [$x, (int) (150 + 55 * sin($x / 22) * cos($x / 70) - ($x - 200) * 0.15)];
        }
        foreach (array_slice($titik, 1) as $k => $p) {
            imageline($img, $titik[$k][0], $titik[$k][1], $p[0], $p[1], $tinta);
        }
        imageline($img, 60, 230, 340, 215, $tinta);

        ob_start();
        imagepng($img);

        return ob_get_clean();
    }
}
