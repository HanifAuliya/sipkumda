<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RancanganProdukHukum;

class RancanganProdukHukumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        RancanganProdukHukum::factory(10)->create();
    }
}
