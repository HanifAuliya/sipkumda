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
        FasilitasiProdukHukum::factory()->count(10)->create(); // Generate 10 data fasilitasi secara acak
    }
}
