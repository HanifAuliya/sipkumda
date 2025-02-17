<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TandaTangan;
use App\Models\User;

class TandaTanganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat user dengan role "Verifikator" jika belum ada
        $verifikator = User::role('Verifikator')->inRandomOrder()->first() ?? User::factory()->create()->assignRole('Verifikator');

        // Buat tanda tangan utama
        TandaTangan::factory()->create([
            'nama_ttd' => 'Tanda Tangan Resmi',
            'file_ttd' => 'tanda_tangan/ttd_resmi.png',
            'dibuat_oleh' => $verifikator->id,
            'status' => 'Aktif',
        ]);
        TandaTangan::factory(5)->create();
    }
}
