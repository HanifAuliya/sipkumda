<?php

namespace Database\Factories;

use App\Models\RancanganProdukHukum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Carbon\Carbon;

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
        $bulanRandom = rand(1, 6); // Pilih bulan antara Januari - Juni 2025
        $tanggalPengajuan = Carbon::create(2025, $bulanRandom, rand(1, 28), rand(8, 17), rand(0, 59), rand(0, 59));

        return [
            'slug' => Str::uuid(),
            'no_rancangan' => $this->faker->unique()->numerify('RANC-#####'),
            'jenis_rancangan' => $this->faker->randomElement(['Peraturan Bupati', 'Surat Keputusan']),
            'tentang' => $this->faker->sentence(),
            'nota_dinas_pd' => $this->faker->optional()->word() . '.pdf',
            'nomor_nota' => $this->faker->optional()->numerify('NOTA-#####'),
            'tanggal_nota' => $tanggalPengajuan->copy()->addDays(rand(1, 7)), // Nota keluar dalam 1 minggu setelah pengajuan

            'rancangan' => $this->faker->optional()->word() . '.pdf',
            'matrik' => $this->faker->optional()->word() . '.pdf',
            'bahan_pendukung' => $this->faker->optional()->word() . '.pdf',
            'tanggal_pengajuan' => $tanggalPengajuan, // Tanggal pengajuan dalam 6 bulan pertama tahun 2025
            'id_user' => User::factory(),
            'status_berkas' => 'Menunggu Persetujuan',
            'status_rancangan' => 'Dalam Proses',
            'catatan_berkas' => null,
            'tanggal_berkas_disetujui' => null,
            'tanggal_rancangan_disetujui' => $tanggalPengajuan->copy()->addDays(rand(7, 14)), // Disetujui 1-2 minggu setelah pengajuan
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
