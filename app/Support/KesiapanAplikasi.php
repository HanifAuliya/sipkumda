<?php

namespace App\Support;

use App\Models\Pengaturan;
use App\Models\TandaTangan;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/**
 * Hal yang harus siap supaya SIFKUMDA berjalan tanpa bantuan pengembang, ditampilkan sebagai
 * kartu di Dashboard Bagian Hukum selama masih ada yang belum beres.
 *
 * Tiap butir: judul, keterangan (apa akibatnya dan cara membetulkan), siap (bool), tautan (menu
 * untuk membetulkan bila pengguna punya akses), dan oleh (siapa yang bisa membetulkan).
 */
class KesiapanAplikasi
{
    public const PERAN = ['Admin', 'Super Admin', 'Verifikator'];

    /** Cadangan lebih tua dari ini berarti penjadwal kemungkinan tidak berjalan. */
    public const BATAS_HARI_CADANGAN = 2;

    public static function untuk(User $user): array
    {
        if (! $user->hasAnyRole(self::PERAN)) {
            return [];
        }

        $tautan = fn (string $rute, array $peran) => Route::has($rute) && $user->hasAnyRole($peran) ? route($rute) : null;
        $bupati = trim(Pengaturan::ambil(Pengaturan::NAMA_BUPATI));
        $sekda = trim(Pengaturan::ambil(Pengaturan::NAMA_SEKDA));
        $cadangan = self::cadanganTerakhir();
        $umurCadangan = $cadangan ? (int) floor($cadangan->diffInDays(now())) : null;
        $mailer = (string) config('mail.default');

        return [
            [
                'judul' => 'Nama Bupati dan Sekretaris Daerah',
                'keterangan' => $bupati !== '' && $sekda !== ''
                    ? 'Sudah diisi.'
                    : 'Belum diisi, sehingga penutup naskah tercetak "…". Isi di Pengaturan Naskah.',
                'siap' => $bupati !== '' && $sekda !== '',
                'tautan' => $tautan('pengaturan', ['Admin', 'Verifikator', 'Super Admin']),
                'oleh' => 'Admin atau Verifikator',
            ],
            [
                'judul' => 'Pejabat penanda tangan nota dinas',
                'keterangan' => TandaTangan::where('status', 'Aktif')->exists()
                    ? 'Ada tanda tangan aktif.'
                    : 'Belum ada tanda tangan aktif, sehingga nota dinas tidak bisa diterbitkan. Tambahkan di Kelola TTD.',
                'siap' => TandaTangan::where('status', 'Aktif')->exists(),
                'tautan' => $tautan('kelola-ttd.main', ['Verifikator']),
                'oleh' => 'Verifikator',
            ],
            [
                'judul' => 'Pengiriman email',
                'keterangan' => in_array($mailer, ['log', 'array'], true)
                    ? "Email belum dikirim sungguhan (MAIL_MAILER={$mailer}), jadi tautan aktivasi dan lupa kata sandi tidak sampai. Minta pengelola server mengatur MAIL_* di .env; sementara itu pakai kata sandi sementara."
                    : 'Email dikirim lewat ' . $mailer . '.',
                'siap' => ! in_array($mailer, ['log', 'array'], true),
                'tautan' => null,
                'oleh' => 'pengelola server',
            ],
            [
                'judul' => 'Cadangan otomatis',
                'keterangan' => match (true) {
                    $cadangan === null => 'Belum pernah ada cadangan. Pastikan penjadwal (schedule:run) berjalan, lalu jalankan php artisan backup:jalankan sekali.',
                    $umurCadangan > self::BATAS_HARI_CADANGAN => "Cadangan terakhir {$umurCadangan} hari lalu. Penjadwal kemungkinan berhenti; laporkan ke pengelola server.",
                    default => 'Cadangan terakhir ' . $cadangan->translatedFormat('j M Y, H.i') . '.',
                },
                'siap' => $cadangan !== null && $umurCadangan <= self::BATAS_HARI_CADANGAN,
                'tautan' => null,
                'oleh' => 'pengelola server',
            ],
        ];
    }

    /** Butir yang belum siap; kosong berarti kartu kesiapan tidak perlu ditampilkan. */
    public static function belumSiap(User $user): array
    {
        return array_values(array_filter(self::untuk($user), fn ($b) => ! $b['siap']));
    }

    private static function cadanganTerakhir(): ?\Illuminate\Support\Carbon
    {
        $folder = rtrim((string) config('backup.folder'), '/\\') . '/database';
        $berkas = File::isDirectory($folder) ? File::glob("{$folder}/*.sql.gz") : [];

        if (! $berkas) {
            return null;
        }

        return \Illuminate\Support\Carbon::createFromTimestamp(max(array_map('filemtime', $berkas)));
    }
}
