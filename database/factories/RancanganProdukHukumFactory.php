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
            'slug' => Str::uuid(), // Generate UUID sebagai slug
            'no_rancangan' => $this->faker->unique()->numerify('RANC-#####'), // Nomor rancangan unik
            'jenis_rancangan' => $this->faker->randomElement(['Peraturan Bupati', 'Surat Keputusan']), // Jenis rancangan
            'tentang' => $this->faker->sentence(), // Deskripsi tentang rancangan
            'nota_dinas_pd' =>  $this->faker->optional()->word() . '.pdf', // Default kosong, akan terisi saat rancangan disetujui
            'nomor_nota' => $this->faker->optional()->numerify('NOTA-#####'), // Nomor nota opsional
            'tanggal_nota' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'), // Tanggal nota 

            'rancangan' => $this->faker->optional()->word() . '.pdf', // Opsional file rancangan
            'matrik' => $this->faker->optional()->word() . '.pdf', // Opsional file matrik
            'bahan_pendukung' => $this->faker->optional()->word() . '.pdf', // Opsional bahan pendukung
            'tanggal_pengajuan' => $this->faker->dateTimeBetween('-1 year', 'now'), // Tanggal pengajuan dalam 1 tahun terakhir
            'id_user' => User::factory(), // Relasi ke User
            'status_berkas' => 'Menunggu Persetujuan', // Default status
            'status_rancangan' => 'Dalam Proses', // Default status rancangan
            'catatan_berkas' => null, // Default kosong
            'tanggal_berkas_disetujui' => null, // Akan terisi jika disetujui
            'tanggal_rancangan_disetujui' => null, // Akan terisi jika status rancangan disetujui
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
