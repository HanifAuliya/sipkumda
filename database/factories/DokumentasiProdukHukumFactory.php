<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\DokumentasiProdukHukum;
use App\Models\FasilitasiProdukHukum;
use Carbon\Carbon;

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
        // Ambil fasilitasi yang sudah selesai divalidasi
        $fasilitasi = FasilitasiProdukHukum::whereNotNull('tanggal_validasi_fasilitasi')
            ->inRandomOrder()
            ->first();

        // Konversi tanggal ke Carbon sebelum menggunakan copy()
        $tanggalPengarsipan = $fasilitasi ? Carbon::parse($fasilitasi->tanggal_validasi_fasilitasi)->addDays(rand(1, 7)) : now();

        return [
            'rancangan_id' => $fasilitasi->rancangan_id,
            'nomor' => $this->faker->unique()->numberBetween(1, 999),
            'tahun' => now()->year,
            'tanggal_pengarsipan' => $tanggalPengarsipan,
            'file_produk_hukum' => $this->faker->word() . '.pdf',
            'nomor_tahun_berita' => sprintf('%03d/%04d', $this->faker->unique()->numberBetween(1, 999), now()->year),
            'tanggal_penetapan' => $tanggalPengarsipan->copy()->addDays(rand(1, 5)),
            'perangkat_daerah_id' => PerangkatDaerah::inRandomOrder()->first()->id ?? PerangkatDaerah::factory(),
            'jenis_dokumentasi' => $this->faker->randomElement(['Peraturan Bupati', 'Surat Keputusan', 'Dokumen Pendukung']),
            'tentang_dokumentasi' => $this->faker->sentence(6),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
