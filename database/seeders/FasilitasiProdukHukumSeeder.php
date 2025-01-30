<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FasilitasiProdukHukum;

class FasilitasiProdukHukumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seeder untuk data awal fasilitasi_produk_hukum
        FasilitasiProdukHukum::create([
            'rancangan_id' => 1, // ID rancangan (pastikan rancangan dengan ID ini sudah ada di tabel rancangan_produk_hukum)
            'tanggal_fasilitasi' => now()->toDateString(), // Tanggal hari ini
            'file_rancangan' => 'path/to/file_rancangan.pdf', // File rancangan dummy
            'status_berkas_fasilitasi' => null, // Status belum diproses
            'tanggal_persetujuan_berkas' => null,
            'status_validasi_fasilitasi' => 'Belum Tahap Validasi',
            'tanggal_validasi_fasilitasi' => null,
        ]);

        FasilitasiProdukHukum::create([
            'rancangan_id' => 2,
            'tanggal_fasilitasi' => now()->toDateString(),
            'file_rancangan' => 'path/to/file_rancangan_2.pdf',
            'status_berkas_fasilitasi' => null,
            'tanggal_persetujuan_berkas' => null,
            'status_validasi_fasilitasi' => 'Belum Tahap Validasi',
            'tanggal_validasi_fasilitasi' => null,
        ]);
    }
}
