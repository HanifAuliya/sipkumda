<?php

namespace App\Console\Commands;

use App\Models\PerangkatDaerah;
use App\Models\User;
use Database\Seeders\PerangkatDaerahSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Command;

/**
 * Data dasar yang dibutuhkan aplikasi baru, aman dijalankan berulang (dipanggil setiap rilis oleh
 * sifkumda:selaraskan): peran, daftar Perangkat Daerah bila masih kosong, Bahan Penting bawaan, dan
 * Super Admin pertama dari SIFKUMDA_SUPERADMIN_* di .env bila belum ada Super Admin sama sekali.
 */
class SiapkanAplikasi extends Command
{
    protected $signature = 'sifkumda:siapkan';

    protected $description = 'Isi data dasar (peran, Perangkat Daerah, Bahan Penting, Super Admin pertama)';

    public function handle(): int
    {
        $this->callSilently('db:seed', ['--class' => RoleSeeder::class, '--force' => true]);
        $this->line('Peran: siap.');

        if (! PerangkatDaerah::query()->exists()) {
            $this->callSilently('db:seed', ['--class' => PerangkatDaerahSeeder::class, '--force' => true]);
            $this->line('Perangkat Daerah: daftar bawaan diisi.');
        }

        $this->callSilently('bahan-penting:bawaan');
        $this->line('Bahan Penting bawaan: diperbarui.');

        $nip = trim((string) config('sifkumda.superadmin.nip'));

        if ($nip !== '' && ! User::role('Super Admin')->exists()) {
            // Kata sandi sementaranya tercetak di keluaran (log rilis); wajib diganti saat pertama masuk.
            return $this->call('akun:super-admin', [
                'nip' => $nip,
                '--nama' => config('sifkumda.superadmin.nama'),
                '--email' => config('sifkumda.superadmin.email'),
            ]);
        }

        return self::SUCCESS;
    }
}
