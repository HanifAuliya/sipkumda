<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Revisi;
use App\Models\RancanganProdukHukum;
use App\Models\User;
use Carbon\Carbon;

class RevisiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Revisi::factory()->count(10)->create(); // Sesuaikan jumlah yang ingin di-generate
    }
}
