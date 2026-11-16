<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

/**
 * Deploy lewat Git di hosting tanpa SSH (Hostinger Web Hosting): hPanel menarik branch `produksi`,
 * lalu cron menjalankan perintah ini tiap menit. Bila versi (file REVISION) atau isi .env berubah,
 * migrasi, data dasar, dan cache dijalankan. Selain itu langsung selesai tanpa menyentuh apa pun.
 *
 * Setiap langkah berjalan sebagai proses tersendiri supaya membaca .env dan kode yang terbaru.
 * Jejaknya di storage/logs/rilis.log.
 */
class SelaraskanRilis extends Command
{
    protected $signature = 'sifkumda:selaraskan {--paksa : Jalankan walau versi dan .env tidak berubah}';

    protected $description = 'Setelah kode baru ditarik: migrasi, data dasar, dan cache (deploy tanpa SSH)';

    public function handle(): int
    {
        $berkasRevisi = base_path('REVISION');

        if (! is_file($berkasRevisi)) {
            $this->line('Bukan paket produksi (tidak ada file REVISION); dilewati.');

            return self::SUCCESS;
        }

        // Cron berikutnya bisa mulai sebelum yang ini selesai.
        $kunci = fopen(storage_path('framework/selaraskan.lock'), 'c');

        if (! $kunci || ! flock($kunci, LOCK_EX | LOCK_NB)) {
            return self::SUCCESS;
        }

        try {
            return $this->selaraskan(trim((string) file_get_contents($berkasRevisi)));
        } finally {
            flock($kunci, LOCK_UN);
            fclose($kunci);
        }
    }

    private function selaraskan(string $revisi): int
    {
        $env = base_path('.env');

        if (! is_file($env)) {
            $this->catat('Belum ada file .env di ' . base_path() . '; buat dulu dari .env.example.');

            return self::FAILURE;
        }

        $berkasCatatan = storage_path('app/selaraskan.json');
        $terpasang = is_file($berkasCatatan) ? (array) json_decode((string) file_get_contents($berkasCatatan), true) : [];

        if (! $this->option('paksa') && ($terpasang['revisi'] ?? null) === $revisi && ($terpasang['env'] ?? null) === md5_file($env)) {
            return self::SUCCESS;
        }

        $this->catat("Mulai menyelaraskan revisi {$revisi}.");

        $langkah = [['config:clear']];
        if (! preg_match('/^APP_KEY=\S+/m', (string) file_get_contents($env))) {
            $langkah[] = ['key:generate', '--force'];
        }
        $langkah = [...$langkah, ['migrate', '--force'], ['sifkumda:siapkan'], ['optimize']];

        foreach ($langkah as $perintah) {
            $hasil = Process::path(base_path())->timeout(300)
                ->run([PHP_BINARY, 'artisan', ...$perintah, '--no-interaction', '--no-ansi']);

            $this->catat('> php artisan ' . implode(' ', $perintah) . PHP_EOL . trim($hasil->output() . $hasil->errorOutput()));

            if ($hasil->failed()) {
                $this->catat('GAGAL. Dicoba lagi pada menit berikutnya.');

                return self::FAILURE;
            }
        }

        clearstatcache();
        file_put_contents($berkasCatatan, json_encode(['revisi' => $revisi, 'env' => md5_file($env), 'waktu' => now()->toIso8601String()]));
        $this->catat('Selesai.');

        return self::SUCCESS;
    }

    private function catat(string $teks): void
    {
        file_put_contents(storage_path('logs/rilis.log'), '[' . now()->format('Y-m-d H:i:s') . '] ' . $teks . PHP_EOL, FILE_APPEND);
        $this->line($teks);
    }
}
