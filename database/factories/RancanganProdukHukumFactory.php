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
            'nota_dinas_pd' => $this->faker->word() . '.pdf', // File Nota Dinas
            'rancangan' => $this->faker->word() . '.docx', // File Rancangan
            'matrik' => $this->faker->word() . '.xlsx', // File Matrik
            'bahan_pendukung' => $this->faker->word() . '.zip', // File Bahan Pendukung
            'tanggal_pengajuan' => $this->faker->dateTimeBetween('-1 year', 'now'), // Tanggal pengajuan dalam 1 tahun terakhir
            'id_user' => User::factory(), // ID User, relasi dengan User
            'status_berkas' => $this->faker->randomElement(['Disetujui', 'Ditolak', 'Menunggu Persetujuan']), // Status berkas
            'status_rancangan' => $this->faker->randomElement(['Disetujui', 'Ditolak', 'Dalam Proses']), // Status rancangan
            'catatan_berkas' => $this->faker->paragraph(), // Catatan Berkas
            'tanggal_berkas_disetujui' => $this->faker->optional()->dateTimeBetween('-6 months', 'now'), // Opsional tanggal disetujui
            'tanggal_rancangan_disetujui' => $this->faker->optional()->dateTimeBetween('-3 months', 'now'), // Opsional tanggal peneliti dipilih
            'created_at' => now(), // Tanggal dibuat
            'updated_at' => now(), // Tanggal diperbarui
        ];
    }
}
