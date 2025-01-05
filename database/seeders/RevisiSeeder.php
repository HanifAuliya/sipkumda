<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Revisi;
use App\Models\RancanganProdukHukum;

class RevisiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil ID rancangan dari tabel RancanganProdukHukum untuk digunakan
        $rancanganIds = RancanganProdukHukum::pluck('id_rancangan')->toArray();

        // Pastikan ada rancangan yang tersedia
        if (count($rancanganIds) === 0) {
            $this->command->warn("Tabel RancanganProdukHukum kosong. Seeder Revisi tidak akan berjalan.");
            return;
        }

        // Tambahkan data dummy revisi
        foreach ($rancanganIds as $rancanganId) {
            Revisi::create([
                'id_rancangan' => $rancanganId,
                'revisi_rancangan' => 'dummy_revisi_rancangan.pdf',
                'revisi_matrik' => 'dummy_revisi_matrik.pdf',
                'catatan_revisi' => 'Catatan revisi dari peneliti untuk rancangan ID ' . $rancanganId,
                'id_user' => null, // Ubah sesuai ID user yang memiliki role Peneliti
                'status_revisi' => 'Belum Tahap Direvisi',
                'tanggal_revisi' => now(),
            ]);
        }
    }
}
