<?php

namespace Database\Seeders;

use App\Models\DokumentasiProdukHukum;
use App\Models\PerangkatDaerah;
use App\Models\TandaTangan;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Data contoh untuk melihat tampilan (khusus lingkungan lokal): akun Perangkat Daerah tambahan,
 * pejabat penanda tangan nota dinas, dan arsip produk hukum. Naskah contoh di setiap tahap dibuat
 * V2RancanganPdSeeder.
 *
 *   php artisan db:seed --class=DemoSeeder
 *
 * Hanya menambah data. Membutuhkan PerangkatDaerahSeeder, RoleSeeder, dan UserSeeder.
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

        $verifikator = User::where('NIP', 'verifikator')->first()
            ?? throw new RuntimeException('Jalankan UserSeeder terlebih dahulu (php artisan db:seed).');

        $this->buatFileContoh();
        $this->buatPerangkatDaerah();
        $this->buatTandaTangan($verifikator);
        $this->buatArsipManual();

        $this->command?->info(sprintf('Data demo dibuat: %d dokumentasi.', DokumentasiProdukHukum::count()));
    }

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

    /** Keputusan Bupati bernomor lengkap (140/1/412.1.6/TAHUN 2025); Peraturan Bupati tanpa pola. */
    private function polaNomor(string $jenis): ?string
    {
        return $jenis === 'Keputusan Bupati' ? '140/{nomor}/412.1.6/TAHUN {tahun}' : null;
    }

    /** Arsip produk hukum yang ditetapkan sebelum ada sistem. */
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
                'pola_nomor' => $this->polaNomor($jenis),
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

    /** File contoh di storage/app/private. */
    private function buatFileContoh(): void
    {
        $pdf = Pdf::loadHTML(
            '<div style="font-family: sans-serif; text-align: center; margin-top: 200px">'
            . '<h1 style="color:#172b4d">Produk Hukum Daerah</h1>'
            . '<p style="color:#64748b">Dokumen contoh SIFKUMDA &mdash; dibuat oleh DemoSeeder untuk keperluan tampilan.</p></div>'
        )->output();

        foreach ([
            'produk_hukum' => ['dokumentasi/file_produk_hukum/demo_produk_hukum.pdf', $pdf],
            'ttd' => ['tanda_tangan/ttd/demo_ttd.png', $this->png()],
        ] as $kunci => [$path, $isi]) {
            Storage::disk('local')->put($path, $isi);
            $this->file[$kunci] = $path;
        }
    }

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
