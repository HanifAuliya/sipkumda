<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RancanganProdukHukum;
use App\Models\Revisi;
use App\Models\FasilitasiProdukHukum;
use App\Models\DokumentasiProdukHukum;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        // User::factory()->count(50)->create(); // Buat 50 pengguna

        $this->call([
            PerangkatDaerahSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
        ]);

        // RancanganProdukHukum::factory()
        //     ->count(5) // Buat 10 rancangan
        //     ->has(Revisi::factory(), 'revisi') // Setiap rancangan punya 1 revisi
        //     ->has(FasilitasiProdukHukum::factory(), 'fasilitasi') // Setiap rancangan punya 1 fasilitasi
        //     ->has(DokumentasiProdukHukum::factory(), 'dokumentasi') // Setiap rancangan punya 1 dokumentasi
        //     ->create();
    }
}
