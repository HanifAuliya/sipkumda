<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DokumentasiProdukHukum;

class DokumentasiProdukHukumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DokumentasiProdukHukum::factory()->count(10)->create(); // Generate 10 data dummy
    }
}
