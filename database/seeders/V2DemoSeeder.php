<?php

namespace Database\Seeders;

use App\Models\Naskah;
use App\Models\NaskahBaris;
use App\Models\TandaTangan;
use App\Models\User;
use App\Services\AlurNaskah;
use App\Support\TemplateNaskah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Data contoh aplikasi v2 untuk melihat tampilan (lokal saja):
 *   php artisan db:seed --class=DemoSeeder      (akun PD contoh)
 *   php artisan db:seed --class=V2DemoSeeder
 */
class V2DemoSeeder extends Seeder
{
    private const PERBUP = [
        ['bagian' => 'judul', 'label' => null, 'teks' => "RANCANGAN\nPERATURAN BUPATI HULU SUNGAI TENGAH\nNOMOR ... TAHUN ...\nTENTANG\nTATA CARA PEMBAGIAN DAN PENETAPAN RINCIAN ALOKASI DANA DESA"],
        ['bagian' => 'menimbang', 'label' => null, 'teks' => 'bahwa berdasarkan Pasal 96 ayat (4) Peraturan Pemerintah Nomor 47 Tahun 2015, pengalokasian Alokasi Dana Desa di setiap Desa diatur dengan Peraturan Bupati;'],
        ['bagian' => 'menimbang', 'label' => null, 'teks' => 'bahwa berdasarkan pertimbangan sebagaimana dimaksud dalam huruf a, perlu menetapkan Peraturan Bupati tentang Tata Cara Pembagian dan Penetapan Rincian Alokasi Dana Desa;'],
        ['bagian' => 'mengingat', 'label' => null, 'teks' => 'Undang-Undang Nomor 6 Tahun 2014 tentang Desa (Lembaran Negara Republik Indonesia Tahun 2014 Nomor 7);'],
        ['bagian' => 'mengingat', 'label' => null, 'teks' => 'Undang-Undang Nomor 23 Tahun 2014 tentang Pemerintahan Daerah (Lembaran Negara Republik Indonesia Tahun 2014 Nomor 244);'],
        ['bagian' => 'diktum', 'label' => null, 'teks' => "MEMUTUSKAN:\nMenetapkan: PERATURAN BUPATI TENTANG TATA CARA PEMBAGIAN DAN PENETAPAN RINCIAN ALOKASI DANA DESA."],
        ['bagian' => 'batang_tubuh', 'label' => 'BAB I', 'teks' => 'KETENTUAN UMUM'],
        ['bagian' => 'batang_tubuh', 'label' => 'Pasal 1', 'teks' => "Dalam Peraturan Bupati ini yang dimaksud dengan:\n1. Desa adalah kesatuan masyarakat hukum yang memiliki batas wilayah.\n2. Alokasi Dana Desa adalah dana perimbangan yang diterima kabupaten."],
        ['bagian' => 'batang_tubuh', 'label' => 'BAB II', 'teks' => 'PENYALURAN'],
        ['bagian' => 'batang_tubuh', 'label' => 'Pasal 2', 'teks' => "(1) Penyaluran dilakukan paling lambat 7 hari setelah permohonan diterima.\n(2) Penyaluran dilakukan melalui rekening kas Desa."],
        ['bagian' => 'batang_tubuh', 'label' => 'Pasal 3', 'teks' => 'Peraturan Bupati ini mulai berlaku pada tanggal diundangkan.'],
        ['bagian' => 'penutup', 'label' => null, 'teks' => "Ditetapkan di Barabai\npada tanggal ..."],
        ['bagian' => 'lampiran', 'label' => null, 'teks' => ''],
    ];

    private const SK = [
        ['bagian' => 'judul', 'label' => null, 'teks' => "KEPUTUSAN BUPATI HULU SUNGAI TENGAH\nNOMOR 400/ /911/TAHUN 2026\nTENTANG\nPENETAPAN PENERIMA HIBAH"],
        ['bagian' => 'menimbang', 'label' => null, 'teks' => 'bahwa dalam rangka pemberian hibah perlu menetapkan Keputusan Bupati;'],
        ['bagian' => 'mengingat', 'label' => null, 'teks' => 'Undang-Undang Nomor 23 Tahun 2014 tentang Pemerintahan Daerah;'],
        ['bagian' => 'diktum', 'label' => null, 'teks' => 'Menetapkan penerima hibah sebagaimana tercantum dalam Lampiran.'],
        ['bagian' => 'diktum', 'label' => null, 'teks' => 'Keputusan Bupati ini mulai berlaku pada tanggal ditetapkan.'],
        ['bagian' => 'penutup', 'label' => null, 'teks' => "Ditetapkan di Barabai\npada tanggal ..."],
        ['bagian' => 'lampiran', 'label' => null, 'teks' => ''],
    ];

    public function run(AlurNaskah $alur): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Seeder contoh tidak boleh dijalankan di production.');
        }

        if (Naskah::where('tentang', 'Penetapan Penerima Hibah')->exists()) {
            $this->command?->warn('Data contoh v2 sudah ada, dilewati.');

            return;
        }

        $pd = User::where('NIP', 'demo_pd_dinkes')->first() ?? User::role('Perangkat Daerah')->firstOrFail();
        $admin = User::role('Admin')->firstOrFail();
        $verifikator = User::role('Verifikator')->firstOrFail();
        $peneliti = User::role('Peneliti')->firstOrFail();

        // Masih disusun PD.
        $this->buat($alur, $pd, 'Keputusan Bupati', 'Penetapan Penerima Hibah', self::SK);

        // Sedang diteliti, sebagian baris sudah diperiksa.
        $diteliti = $this->buat($alur, $pd, 'Peraturan Bupati', 'Tata Cara Pembagian dan Penetapan Rincian Alokasi Dana Desa', self::PERBUP);
        $this->sampaiPenelitian($alur, $diteliti, $pd, $admin, $verifikator, $peneliti);

        $baris = $diteliti->baris()->get()->keyBy(fn (NaskahBaris $b) => $b->label ?? "{$b->bagian}-{$b->id}");
        $diteliti->baris()->whereIn('bagian', ['judul', 'menimbang'])->get()->each(fn ($b) => $alur->tetapkan($b, $peneliti));
        $alur->perbaiki(
            $baris['Pasal 2'],
            $peneliti,
            "(1) Penyaluran dilakukan paling lambat 14 hari kerja setelah permohonan diterima.\n(2) Penyaluran dilakukan melalui rekening kas Desa.",
            'Menyesuaikan batas waktu pada peraturan di atasnya.',
        );

        // Giliran PD memperbaiki.
        $perbaikan = $this->buat($alur, $pd, 'Peraturan Bupati', 'Pengelolaan Barang Milik Daerah', self::PERBUP);
        $this->sampaiPenelitian($alur, $perbaikan, $pd, $admin, $verifikator, $peneliti);
        $pasal = $perbaikan->baris()->where('label', 'Pasal 1')->firstOrFail();
        $perbaikan->baris()->whereKeyNot($pasal->id)->get()->each(fn ($b) => $alur->tetapkan($b, $peneliti));
        $alur->mintaPerbaikan($pasal, $peneliti, 'Tambahkan definisi Pemerintah Desa.');
        $alur->jalankan($perbaikan, $peneliti, 'kirim_ke_pd');

        // Sudah bernota dinas dan sedang beredar untuk ditandatangani.
        if ($ttd = TandaTangan::where('status', 'Aktif')->first()) {
            $ttdNaskah = $this->buat($alur, $pd, 'Peraturan Bupati', 'Pedoman Pemberian Hibah Daerah', self::PERBUP);
            $this->sampaiPenelitian($alur, $ttdNaskah, $pd, $admin, $verifikator, $peneliti);
            $ttdNaskah->baris()->get()->each(fn ($b) => $alur->tetapkan($b, $peneliti));
            $alur->jalankan($ttdNaskah, $peneliti, 'teruskan_ke_verifikator');
            $alur->jalankan($ttdNaskah, $verifikator, 'setujui_rancangan');
            $alur->jalankan($ttdNaskah, $pd, 'ajukan_fasilitasi');
            $alur->jalankan($ttdNaskah, $peneliti, 'setujui_fasilitasi');
            $alur->jalankan($ttdNaskah, $verifikator, 'finalkan');
            $alur->terbitkanNota($ttdNaskah, $admin, (string) random_int(500, 999), 'Sekretaris Daerah', $ttd->id);
            $alur->tandaiTahapan($ttdNaskah, $admin);
        }
    }

    private function buat(AlurNaskah $alur, User $pd, string $jenis, string $tentang, array $baris): Naskah
    {
        return Naskah::denganNomorTerkunci(function () use ($alur, $pd, $jenis, $tentang, $baris) {
            $naskah = Naskah::create([
                'jenis' => $jenis,
                'tentang' => $tentang,
                'pemrakarsa_id' => $pd->id,
                'nomor_nota' => '180/' . random_int(100, 999) . '/ND/' . now()->year,
                'tanggal_nota' => now(),
            ]);

            $alur->isiBaris($naskah, TemplateNaskah::denganPenutup($baris, $jenis));

            $path = "naskah/{$naskah->slug}/nota-dinas.pdf";
            Storage::disk('local')->put($path, '%PDF-1.4 contoh');
            $naskah->berkas()->create(['jenis' => 'nota_dinas_pd', 'path' => $path, 'nama_asli' => 'nota-dinas.pdf', 'diunggah_oleh' => $pd->id]);

            return $naskah;
        });
    }

    private function sampaiPenelitian(AlurNaskah $alur, Naskah $naskah, User $pd, User $admin, User $verifikator, User $peneliti): void
    {
        $alur->jalankan($naskah, $pd, 'ajukan');
        $alur->jalankan($naskah, $admin, 'setujui_berkas');
        $alur->jalankan($naskah, $verifikator, 'tugaskan_peneliti', null, $peneliti->id);
    }
}
