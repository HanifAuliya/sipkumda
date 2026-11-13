<?php

namespace Database\Seeders;

use App\Enums\TahapNaskah as T;
use App\Models\Naskah;
use App\Models\NaskahRiwayat;
use App\Models\PerangkatDaerah;
use App\Models\TandaTangan;
use App\Models\User;
use App\Services\AlurNaskah;
use App\Support\FormatDokumen;
use App\Support\TemplateNaskah;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Naskah contoh di tahap lanjut, supaya setiap layar terisi saat demo (lokal saja):
 * - menunggu nota dinas (tahap Final) dan Penandatanganan,
 * - Menunggu Arsip (tab di Dokumentasi, tombol Arsipkan dari Sistem),
 * - Selesai dan tertaut ke arsip (kolom "Dokumen terdahulu" berganti tautan naskah),
 * - tertahan lama di satu tahap (menu Laporan, pengingat otomatis).
 * Waktunya dimundurkan beberapa minggu, sehingga Laporan menampilkan lama proses yang wajar.
 *
 *   php artisan db:seed --class=DemoAlurSeeder
 *
 * Membutuhkan RoleSeeder, UserSeeder, dan DemoSeeder (akun Perangkat Daerah demo dan tanda tangan).
 * Hanya menambah data; naskah yang judulnya sudah ada dilewati.
 */
class DemoAlurSeeder extends Seeder
{
    private User $admin;

    private User $verifikator;

    private User $peneliti;

    public function run(AlurNaskah $alur): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoAlurSeeder tidak boleh dijalankan di production.');
        }

        $this->admin = User::where('NIP', 'admin')->first() ?? User::role('Admin')->firstOrFail();
        $this->verifikator = User::where('NIP', 'verifikator')->first() ?? User::role('Verifikator')->firstOrFail();
        $this->peneliti = User::where('NIP', 'peneliti')->first() ?? User::role('Peneliti')->firstOrFail();

        if (! TandaTangan::where('status', 'Aktif')->exists()) {
            throw new RuntimeException('Belum ada tanda tangan aktif. Jalankan DemoSeeder terlebih dahulu.');
        }

        // [akun PD, jenis, tentang, sampai tahap, diajukan berapa hari lalu, sudah berapa hari di tahap terakhir]
        $daftar = [
            ['demo_pd_dinkes', 'Peraturan Bupati', 'Pedoman Pelaksanaan Imunisasi Rutin', T::Selesai, 46, 0],
            ['demo_pd_disdik', 'Keputusan Bupati', 'Penetapan Sekolah Penggerak Tingkat Kabupaten', T::Selesai, 38, 0],
            ['demo_pd_dlh', 'Peraturan Bupati', 'Pengurangan Sampah Plastik Sekali Pakai', T::Arsip, 30, 3],
            ['demo_pd_dinkes', 'Keputusan Bupati', 'Pembentukan Tim Percepatan Penurunan Stunting', T::Arsip, 26, 1],
            ['demo_pd_disdik', 'Peraturan Bupati', 'Tata Cara Penerimaan Peserta Didik Baru', T::Penandatanganan, 24, 4],
            ['demo_pd_dlh', 'Peraturan Bupati', 'Pengelolaan Bank Sampah', T::Final, 21, 2],
            ['demo_pd_dlh', 'Keputusan Bupati', 'Penetapan Kawasan Ruang Terbuka Hijau', T::Penelitian, 20, 12],
            ['demo_pd_dinkes', 'Peraturan Bupati', 'Standar Pelayanan Minimal Puskesmas', T::PersetujuanBerkas, 9, 9],
        ];

        foreach ($daftar as [$nip, $jenis, $tentang, $sampai, $hari, $diam]) {
            if (Naskah::where('tentang', $tentang)->exists()) {
                $this->command?->warn("Sudah ada, dilewati: {$tentang}");

                continue;
            }

            $pd = User::where('NIP', $nip)->first() ?? User::where('NIP', 'perangkatdaerah')->firstOrFail();
            $naskah = $this->buat($alur, $pd, $jenis, $tentang);
            $naskah = $this->jalankanSampai($alur, $naskah, $pd, $sampai);
            $this->mundurkanWaktu($naskah, $hari, $diam);

            $this->command?->info("{$naskah->nomor}  {$naskah->tahap->label()}  {$tentang}");
        }
    }

    private function buat(AlurNaskah $alur, User $pd, string $jenis, string $tentang): Naskah
    {
        return DB::transaction(function () use ($alur, $pd, $jenis, $tentang) {
            $naskah = Naskah::create([
                'jenis' => $jenis,
                'tentang' => $tentang,
                'pemrakarsa_id' => $pd->id,
                'nomor_nota' => '180/' . random_int(100, 899) . '/ND/' . now()->year,
                'tanggal_nota' => now(),
            ]);

            $alur->isiBaris($naskah, $this->baris($jenis, $tentang));

            $path = "naskah/{$naskah->slug}/nota-dinas.pdf";
            Storage::disk('local')->put($path, $this->pdf("Nota Dinas {$pd->perangkatDaerah?->nama_perangkat_daerah}"));
            $naskah->berkas()->create(['jenis' => 'nota_dinas_pd', 'path' => $path, 'nama_asli' => 'nota-dinas.pdf', 'diunggah_oleh' => $pd->id]);

            return $naskah;
        });
    }

    /** Kerangka resmi dengan isi singkat yang masuk akal untuk judulnya. */
    private function baris(string $jenis, string $tentang): array
    {
        $kepbup = $jenis === FormatDokumen::KEPBUP;
        $isi = [
            'menimbang' => [
                "bahwa dalam rangka {$this->kecil($tentang)} perlu diatur secara jelas dan terpadu;",
                "bahwa berdasarkan pertimbangan sebagaimana dimaksud dalam huruf a, perlu menetapkan {$jenis} tentang {$tentang};",
            ],
            'mengingat' => [
                'Undang-Undang Nomor 23 Tahun 2014 tentang Pemerintahan Daerah (Lembaran Negara Republik Indonesia Tahun 2014 Nomor 244, Tambahan Lembaran Negara Republik Indonesia Nomor 5587);',
            ],
        ];

        if ($kepbup) {
            $isi['diktum'] = [
                "Menetapkan {$this->kecil($tentang)} sebagaimana tercantum dalam Lampiran yang merupakan bagian tidak terpisahkan dari Keputusan Bupati ini.",
                'Segala biaya yang timbul akibat ditetapkannya Keputusan Bupati ini dibebankan pada Anggaran Pendapatan dan Belanja Daerah.',
                'Keputusan Bupati ini mulai berlaku pada tanggal ditetapkan.',
            ];
        } else {
            $isi['batang_tubuh'] = [
                ['BAB I', 'KETENTUAN UMUM'],
                ['Pasal 1', "Dalam Peraturan Bupati ini yang dimaksud dengan:\n1. Daerah adalah Kabupaten Hulu Sungai Tengah.\n2. Bupati adalah Bupati Hulu Sungai Tengah."],
                ['BAB II', 'PELAKSANAAN'],
                ['Pasal 2', "(1) Perangkat Daerah terkait melaksanakan {$this->kecil($tentang)}.\n(2) Pelaksanaan sebagaimana dimaksud pada ayat (1) dilaporkan kepada Bupati setiap tahun."],
                ['BAB III', 'KETENTUAN PENUTUP'],
                ['Pasal 3', "Peraturan Bupati ini mulai berlaku pada tanggal diundangkan.\nAgar setiap orang mengetahuinya, memerintahkan pengundangan Peraturan Bupati ini dengan penempatannya dalam Berita Daerah Kabupaten Hulu Sungai Tengah."],
            ];
        }

        $kerangka = collect(TemplateNaskah::kerangka($jenis, $tentang));
        $hasil = [];

        foreach (array_keys(TemplateNaskah::bagian($jenis)) as $bagian) {
            if (! isset($isi[$bagian])) {
                array_push($hasil, ...$kerangka->where('bagian', $bagian)->values()->all());

                continue;
            }

            foreach ($isi[$bagian] as $item) {
                [$label, $teks] = is_array($item) ? $item : [null, $item];
                $hasil[] = ['bagian' => $bagian, 'label' => $label, 'teks' => $teks];
            }
        }

        return $hasil;
    }

    private function jalankanSampai(AlurNaskah $alur, Naskah $naskah, User $pd, T $sampai): Naskah
    {
        $langkah = [
            T::PersetujuanBerkas->value => fn () => $alur->jalankan($naskah, $pd, 'ajukan'),
            T::PilihPeneliti->value => fn () => $alur->jalankan($naskah, $this->admin, 'setujui_berkas'),
            T::Penelitian->value => fn () => $alur->jalankan($naskah, $this->verifikator, 'tugaskan_peneliti', null, $this->peneliti->id),
            T::ValidasiRancangan->value => function () use ($alur, $naskah) {
                $naskah->baris()->get()->each(fn ($b) => $alur->tetapkan($b, $this->peneliti));
                $alur->jalankan($naskah, $this->peneliti, 'teruskan_ke_verifikator');
            },
            T::Disetujui->value => fn () => $alur->jalankan($naskah, $this->verifikator, 'setujui_rancangan'),
            T::PersetujuanFasilitasi->value => fn () => $alur->jalankan($naskah, $pd, 'ajukan_fasilitasi'),
            T::ValidasiFasilitasi->value => fn () => $alur->jalankan($naskah, $this->peneliti, 'setujui_fasilitasi'),
            T::Final->value => fn () => $alur->jalankan($naskah, $this->verifikator, 'finalkan'),
            T::Penandatanganan->value => fn () => $this->terbitkanNota($alur, $naskah),
            T::Arsip->value => function () use ($alur, $naskah) {
                foreach (AlurNaskah::tahapanTtd() as $_) {
                    $alur->tandaiTahapan($naskah->refresh(), $this->admin);
                }
            },
            T::Selesai->value => fn () => $this->arsipkan($alur, $naskah),
        ];

        foreach ($langkah as $tahap => $jalan) {
            if ($naskah->refresh()->tahap === $sampai) {
                break;
            }
            $jalan();
        }

        return $naskah->refresh();
    }

    /** Nomor nota dinas bisa sudah terpakai oleh data lain, jadi dicari yang masih kosong. */
    private function terbitkanNota(AlurNaskah $alur, Naskah $naskah): void
    {
        $ttd = TandaTangan::where('status', 'Aktif')->firstOrFail();

        foreach (range(800, 899) as $nomor) {
            try {
                $alur->terbitkanNota($naskah->refresh(), $this->admin, (string) $nomor, 'Sekretaris Daerah', $ttd->id);

                return;
            } catch (ValidationException) {
                continue;
            }
        }

        throw new RuntimeException('Tidak ada nomor nota dinas yang kosong untuk data demo.');
    }

    /** Nomor arsip diambil yang belum terpakai pada tahun ini. */
    private function arsipkan(AlurNaskah $alur, Naskah $naskah): void
    {
        $sumber = tempnam(sys_get_temp_dir(), 'arsip');
        file_put_contents($sumber, $this->pdf("{$naskah->jenis} tentang {$naskah->tentang}"));
        $tanggal = now()->subDays(2)->toDateString();
        $sk = $naskah->jenis === FormatDokumen::KEPBUP;

        foreach (range(30, 99) as $nomor) {
            try {
                $alur->arsipkan($naskah->refresh(), $this->verifikator, (string) $nomor, $tanggal,
                    $sk ? null : (string) $nomor, $sk ? null : (string) now()->year,
                    new UploadedFile($sumber, 'produk-hukum.pdf', 'application/pdf', null, true));

                return;
            } catch (ValidationException) {
                continue;
            }
        }

        throw new RuntimeException('Tidak ada nomor arsip yang kosong untuk data demo.');
    }

    /**
     * Riwayat perpindahan tahap dibuat "sekarang"; di sini disebar mundur: diajukan $hari hari lalu,
     * dan tahap terakhir dimasuki $diam hari lalu. Dipakai menu Laporan dan pengingat.
     */
    private function mundurkanWaktu(Naskah $naskah, int $hari, int $diam): void
    {
        $riwayat = NaskahRiwayat::where('naskah_id', $naskah->id)->where('aksi', 'tahap')->orderBy('id')->get();
        $awal = now()->subDays($hari)->setTime(9, 0);
        $akhir = now()->subDays($diam)->setTime(10, 30);
        $langkah = max(1, $riwayat->count() - 1);

        foreach ($riwayat->values() as $i => $r) {
            $waktu = $awal->copy()->addSeconds((int) ($awal->diffInSeconds($akhir) * $i / $langkah));
            $r->forceFill(['created_at' => $waktu, 'updated_at' => $waktu])->saveQuietly();
        }

        // Catatan lain (perubahan baris dan sebagainya) ikut mundur supaya urutan riwayat tetap masuk akal.
        NaskahRiwayat::where('naskah_id', $naskah->id)->where('aksi', '!=', 'tahap')
            ->update(['created_at' => $awal, 'updated_at' => $awal]);

        $naskah->forceFill([
            'created_at' => $awal->copy()->subDays(2),
            'diajukan_at' => $awal,
            'tanggal_nota' => $awal->copy()->subDay(),
            'updated_at' => $akhir,
        ])->saveQuietly();

        if ($naskah->dokumentasi) {
            $naskah->dokumentasi->forceFill([
                'tanggal_penetapan' => $akhir->copy()->subDays(2)->toDateString(),
                'tanggal_pengarsipan' => $akhir,
            ])->saveQuietly();
        }
    }

    private function kecil(string $teks): string
    {
        return mb_strtolower(mb_substr($teks, 0, 1)) . mb_substr($teks, 1);
    }

    private function pdf(string $judul): string
    {
        return Pdf::loadHTML('<h3 style="font-family:sans-serif">' . e($judul) . '</h3><p style="font-family:sans-serif">Dokumen contoh untuk demo SIFKUMDA.</p>')->output();
    }
}
