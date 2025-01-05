<?php

namespace Database\Factories;

use App\Models\RancanganProdukHukum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RancanganProdukHukum>
 */
class RancanganProdukHukumFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tentang' => $this->faker->sentence(),
            'jenis_rancangan' => $this->faker->randomElement(['Peraturan Bupati', 'Surat Keputusan']), // Jenis rancangan
            'nota_dinas_pd' => $this->faker->word() . '.pdf',
            'rancangan' => $this->faker->word() . '.docx',
            'matrik' => $this->faker->word() . '.xlsx',
            'bahan_pendukung' => $this->faker->word() . '.zip',
            'tanggal_pengajuan' => $this->faker->date(),
            'id_user' => \App\Models\User::factory(), // User ID
            'status_berkas' => $this->faker->randomElement(['Disetujui', 'Ditolak', 'Menunggu Persetujuan']),
            'status_rancangan' => $this->faker->randomElement(['Disetujui', 'Ditolak', 'Dalam Proses']),
            'catatan_berkas' => $this->faker->paragraph(),
            'slug' => Str::uuid(), // Generate UUID sebagai slug
        ];
    }
}
