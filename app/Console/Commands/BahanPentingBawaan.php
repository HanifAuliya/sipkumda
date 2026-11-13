<?php

namespace App\Console\Commands;

use App\Models\ImportantMaterial;
use App\Support\BahanPentingBawaan as Pembuat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Mengisi tombol "Bahan Penting" dengan berkas bawaan: template matriks, contoh nota dinas pengajuan,
 * dan daftar periksa. Aman dijalankan ulang: berkas bawaan dibuat ulang (mengikuti versi aplikasi),
 * berkas yang diunggah Admin tidak disentuh. Bawaan yang sengaja dihapus Admin tidak dibuat lagi,
 * kecuali dengan --pulihkan.
 *
 *   php artisan bahan-penting:bawaan
 *   php artisan bahan-penting:bawaan --pulihkan
 */
class BahanPentingBawaan extends Command
{
    protected $signature = 'bahan-penting:bawaan {--pulihkan : Buat juga bawaan yang pernah dihapus Admin}';

    protected $description = 'Isi Bahan Penting dengan template matriks, contoh nota dinas pengajuan, dan daftar periksa';

    public function handle(): int
    {
        $pertamaKali = ImportantMaterial::where('file_path', 'like', 'public/templates/bawaan-%')->doesntExist();

        foreach (Pembuat::DAFTAR as $nama => [$berkas, $metode]) {
            $path = "public/templates/{$berkas}";
            $ada = ImportantMaterial::where('file_path', $path)->first();

            if (! $ada && ! $pertamaKali && ! $this->option('pulihkan')) {
                $this->line("  dilewati (pernah dihapus Admin): {$nama}");

                continue;
            }

            $sementara = Pembuat::buat($metode);
            Storage::put($path, file_get_contents($sementara));
            @unlink($sementara);

            ImportantMaterial::updateOrCreate(['file_path' => $path], ['button_name' => $ada->button_name ?? $nama]);
            $this->info("  {$nama}");
        }

        $this->info('Bahan Penting bawaan siap.');

        return self::SUCCESS;
    }
}
