<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\DokumentasiProdukHukum;
use App\Models\RancanganProdukHukum;
use App\Models\PerangkatDaerah;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DokumentasiProdukHukum>
 */
class DokumentasiProdukHukumFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    protected $model = DokumentasiProdukHukum::class;

    public function definition(): array
    {
        return [
            'rancangan_id' => RancanganProdukHukum::factory(), // Relasi ke RancanganProdukHukum (satu rancangan, satu dokumentasi)
            'nomor' => $this->faker->unique()->numerify('###'), // Nomor unik (3 digit angka)
            'tahun' => $this->faker->year(), // Tahun dari input
            'tanggal' => $this->faker->date(), // Tanggal publikasi
            'file_produk_hukum' => $this->faker->optional()->word() . '.pdf', // Path file produk hukum opsional
            'nomor_berita_daerah' => $this->faker->numerify('###') . '/BD/' . now()->year,
            'tanggal_berita_daerah' => $this->faker->date(), // Tahun berita daerah
            'perangkat_daerah_id' => PerangkatDaerah::inRandomOrder()->first()->id ?? PerangkatDaerah::first()->id,

            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
