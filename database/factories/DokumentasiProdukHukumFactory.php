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
        $nomor = $this->faker->unique()->numberBetween(1, 999); // Nomor unik (3 digit)
        $tahun = $this->faker->year; // Tahun acak

        return [
            'rancangan_id' => RancanganProdukHukum::factory(), // Foreign key ke rancangan
            'nomor' => $nomor, // Nomor unik (3 digit)
            'tahun' => $tahun, // Tahun
            'tanggal_pengarsipan' => $this->faker->date, // Tanggal pengarsipan
            'file_produk_hukum' => $this->faker->filePath(), // Path file dummy
            'nomor_tahun_berita' => sprintf('%03d/%04d', $nomor, $tahun), // Format: 012/2025
            'tanggal_penetapan' => $this->faker->date, // Tanggal penetapan
            'perangkat_daerah_id' => PerangkatDaerah::inRandomOrder()->first()->id ?? PerangkatDaerah::factory(),
            'jenis_dokumentasi' => $this->faker->randomElement(['Peraturan Bupati', 'Surat Keputusan', 'Dokumen Pendukung']),
            'tentang_dokumentasi' => $this->faker->sentence(6), // Kalimat acak tentang dokumentasi
        ];
    }
}
