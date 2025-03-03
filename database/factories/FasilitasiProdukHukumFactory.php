<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Revisi;
use App\Models\FasilitasiProdukHukum;
use carbon\Carbon;

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
        // Ambil revisi yang sudah selesai
        $revisi = Revisi::whereNotNull('tanggal_revisi')->inRandomOrder()->first();

        // Konversi tanggal ke Carbon sebelum menggunakan copy()
        $tanggalFasilitasi = $revisi ? Carbon::parse($revisi->tanggal_revisi)->addDays(rand(1, 5)) : now();

        return [
            'rancangan_id' => $revisi->id_rancangan,
            'tanggal_fasilitasi' => $tanggalFasilitasi,
            'file_rancangan' => $this->faker->word() . '.pdf',

            // Status berkas fasilitasi
            'status_berkas_fasilitasi' => $this->faker->randomElement(['Menunggu Persetujuan', 'Disetujui', 'Ditolak']),
            'catatan_persetujuan_fasilitasi' => $this->faker->optional()->sentence(),
            'tanggal_persetujuan_berkas' => $tanggalFasilitasi->copy()->addDays(rand(1, 5)),

            // Status validasi fasilitasi
            'status_validasi_fasilitasi' => $this->faker->randomElement(['Belum Tahap Validasi', 'Menunggu Validasi', 'Diterima', 'Ditolak']),
            'catatan_validasi_fasilitasi' => $this->faker->optional()->sentence(),
            'tanggal_validasi_fasilitasi' => $tanggalFasilitasi->copy()->addDays(rand(5, 10)),

            // Status paraf koordinasi dan pejabat terkait
            'status_paraf_koordinasi' => 'Belum',
            'tanggal_paraf_koordinasi' => $tanggalFasilitasi->copy()->addDays(rand(10, 15)),
            'status_asisten' => 'Belum',
            'tanggal_asisten' => $tanggalFasilitasi->copy()->addDays(rand(15, 20)),
            'status_sekda' => 'Belum',
            'tanggal_sekda' => $tanggalFasilitasi->copy()->addDays(rand(20, 25)),
            'status_bupati' => 'Belum',
            'tanggal_bupati' => $tanggalFasilitasi->copy()->addDays(rand(25, 30)),

            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
