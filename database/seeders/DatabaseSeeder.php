<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
            RancanganProdukHukumSeeder::class,
            RevisiSeeder::class,
        ]);
    }
}
