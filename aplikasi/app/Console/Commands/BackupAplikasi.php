<?php

namespace App\Console\Commands;

use App\Notifications\DokumentasiNotification;
use App\Notifications\NaskahNotification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Throwable;

class BackupAplikasi extends Command
{
    protected $signature = 'backup:jalankan';

    protected $description = 'Cadangkan database dan berkas unggahan, lalu hapus cadangan database yang sudah lama';

    public function handle(): int
    {
        try {
            $folder = rtrim(config('backup.folder'), '/\\');

            $dump = $this->cadangkanDatabase("{$folder}/database");
            $disalin = $this->cadangkanBerkas("{$folder}/berkas");
            $dihapus = $this->hapusCadanganLama("{$folder}/database");
        } catch (Throwable $e) {
            report($e);
            $this->kabariAdmin();
            $this->error('Backup gagal: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("Database: {$dump}");
        $this->info("Berkas baru atau berubah yang disalin: {$disalin}");
        $this->info("Cadangan database lama yang dihapus: {$dihapus}");

        return self::SUCCESS;
    }

    /** Dump disimpan dengan nama akhirnya hanya bila lengkap, jadi file .sql.gz selalu bisa dipulihkan. */
    private function cadangkanDatabase(string $folder): string
    {
        $koneksi = config('database.connections.' . config('database.default'));

        if (! in_array($koneksi['driver'], ['mysql', 'mariadb'], true)) {
            throw new RuntimeException("Backup hanya mendukung MySQL atau MariaDB, bukan {$koneksi['driver']}.");
        }

        File::ensureDirectoryExists($folder);

        $nama = $koneksi['database'] . '-' . now()->format('Y-m-d-His');
        $sql = "{$folder}/{$nama}.sql.tmp";
        $gz = "{$folder}/{$nama}.sql.gz";
        $kredensial = $this->tulisKredensial($koneksi);

        try {
            $hasil = Process::timeout(3600)->run([
                config('backup.mysqldump'),
                "--defaults-extra-file={$kredensial}",
                '--single-transaction',
                '--quick',
                '--routines',
                '--triggers',
                '--no-tablespaces',
                '--hex-blob',
                '--default-character-set=utf8mb4',
                "--result-file={$sql}",
                $koneksi['database'],
            ]);

            if ($hasil->failed()) {
                throw new RuntimeException('mysqldump gagal: ' . trim($hasil->errorOutput()));
            }

            $this->pastikanLengkap($sql);
            $this->kompres($sql, $gz);
        } finally {
            File::delete([$kredensial, $sql, "{$gz}.tmp"]);
        }

        return $gz;
    }

    /** Password lewat file sementara, karena argumen perintah bisa dilihat proses lain di server. */
    private function tulisKredensial(array $koneksi): string
    {
        $kutip = fn ($nilai) => '"' . addcslashes((string) $nilai, '\\"') . '"';

        $baris = [
            '[client]',
            'user=' . $kutip($koneksi['username']),
            'password=' . $kutip($koneksi['password']),
            'host=' . $kutip($koneksi['host']),
            'port=' . (int) $koneksi['port'],
        ];

        if (! empty($koneksi['unix_socket'])) {
            $baris[] = 'socket=' . $kutip($koneksi['unix_socket']);
        }

        $file = tempnam(sys_get_temp_dir(), 'bak');
        chmod($file, 0600);
        file_put_contents($file, implode("\n", $baris) . "\n");

        return $file;
    }

    /** mysqldump menulis penanda ini di baris terakhir; tanpanya dump terpotong. */
    private function pastikanLengkap(string $sql): void
    {
        $file = fopen($sql, 'rb');
        fseek($file, -200, SEEK_END);
        $akhir = fread($file, 200);
        fclose($file);

        if (! str_contains($akhir, 'Dump completed')) {
            throw new RuntimeException('Hasil mysqldump terpotong.');
        }
    }

    private function kompres(string $sql, string $gz): void
    {
        $masuk = fopen($sql, 'rb');
        $keluar = gzopen("{$gz}.tmp", 'wb6');

        while (! feof($masuk)) {
            gzwrite($keluar, fread($masuk, 1024 * 1024));
        }

        fclose($masuk);
        gzclose($keluar);
        rename("{$gz}.tmp", $gz);
    }

    /**
     * Hanya menyalin file baru atau berubah, sehingga tetap ringan walau PDF-nya ribuan.
     * Salinan tidak ikut terhapus saat file aslinya dihapus, supaya salah hapus masih bisa dikembalikan.
     */
    private function cadangkanBerkas(string $folder): int
    {
        $disalin = 0;

        foreach (config('backup.sumber_berkas') as $nama => $sumber) {
            if (! is_dir($sumber)) {
                continue;
            }

            foreach (File::allFiles($sumber, true) as $file) {
                $relatif = str_replace('\\', '/', $file->getRelativePathname());

                // File sementara unggahan Livewire tidak perlu dicadangkan.
                if (str_starts_with($relatif, 'livewire-tmp/') || $file->getFilename() === '.gitignore') {
                    continue;
                }

                $tujuan = "{$folder}/{$nama}/{$relatif}";

                if (is_file($tujuan) && filesize($tujuan) === $file->getSize() && filemtime($tujuan) === $file->getMTime()) {
                    continue;
                }

                File::ensureDirectoryExists(dirname($tujuan));

                if (! copy($file->getPathname(), $tujuan)) {
                    throw new RuntimeException("Gagal menyalin {$relatif}.");
                }

                touch($tujuan, $file->getMTime());
                $disalin++;
            }
        }

        return $disalin;
    }

    private function hapusCadanganLama(string $folder): int
    {
        $batas = now()->subDays(config('backup.simpan_hari'))->getTimestamp();

        $lama = collect(File::glob("{$folder}/*.sql.gz"))
            ->sortByDesc(fn ($file) => filemtime($file))
            ->slice(config('backup.simpan_minimal'))
            ->filter(fn ($file) => filemtime($file) < $batas);

        File::delete($lama->all());

        return $lama->count();
    }

    /** Kegagalan pemberitahuan tidak boleh menutupi kegagalan backup yang sudah tercatat di log. */
    private function kabariAdmin(): void
    {
        try {
            $admin = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['Admin', 'Super Admin']))->get();

            $pesan = [
                'title' => 'Backup otomatis gagal',
                'message' => 'Cadangan hari ini tidak terbuat. Periksa storage/logs/laravel.log di server.',
                'type' => 'backup_gagal',
            ];

            // Lonceng v2 dan aplikasi lama membaca jenis notifikasi yang berbeda.
            Notification::send($admin, new NaskahNotification($pesan));
            Notification::send($admin, new DokumentasiNotification($pesan));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
