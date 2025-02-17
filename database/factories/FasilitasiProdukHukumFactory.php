<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\FasilitasiProdukHukum;
use App\Models\RancanganProdukHukum;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FasilitasiProdukHukum>
 */
class FasilitasiProdukHukumFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    protected $model = FasilitasiProdukHukum::class;


    public function definition(): array
    {
        return [
            'rancangan_id' => RancanganProdukHukum::factory(), // Relasi ke RancanganProdukHukum (satu rancangan, satu fasilitasi)
            'tanggal_fasilitasi' => $this->faker->dateTimeBetween('-6 months', 'now'), // Tanggal fasilitasi dalam 6 bulan terakhir
            'file_rancangan' => $this->faker->word() . '.pdf', // File rancangan

            // Status berkas fasilitasi
            'status_berkas_fasilitasi' => $this->faker->randomElement(['Menunggu Persetujuan', 'Disetujui', 'Ditolak']),
            'catatan_persetujuan_fasilitasi' => $this->faker->optional()->sentence(),
            'tanggal_persetujuan_berkas' => $this->faker->optional()->date(),

            // Status validasi fasilitasi
            'status_validasi_fasilitasi' => $this->faker->randomElement(['Belum Tahap Validasi', 'Menunggu Validasi', 'Diterima', 'Ditolak']),
            'catatan_validasi_fasilitasi' => $this->faker->optional()->sentence(),
            'tanggal_validasi_fasilitasi' => $this->faker->optional()->date(),

            // Status paraf koordinasi dan pejabat terkait
            'status_paraf_koordinasi' => $this->faker->randomElement(['Belum', 'Selesai']),
            'tanggal_paraf_koordinasi' => $this->faker->optional()->date(),
            'status_asisten' => $this->faker->randomElement(['Belum', 'Selesai']),
            'tanggal_asisten' => $this->faker->optional()->date(),
            'status_sekda' => $this->faker->randomElement(['Belum', 'Selesai']),
            'tanggal_sekda' => $this->faker->optional()->date(),
            'status_bupati' => $this->faker->randomElement(['Belum', 'Selesai']),
            'tanggal_bupati' => $this->faker->optional()->date(),

            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
