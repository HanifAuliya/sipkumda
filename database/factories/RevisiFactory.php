<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Revisi;
use App\Models\RancanganProdukHukum;
use App\Models\User;
use carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Revisi>
 */
class RevisiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Revisi::class;

    public function definition()
    {
        // Ambil rancangan dengan tanggal yang sudah disetujui
        $rancangan = RancanganProdukHukum::whereNotNull('tanggal_rancangan_disetujui')
            ->inRandomOrder()
            ->first();

        // Konversi tanggal ke Carbon sebelum menggunakan copy()
        $tanggalPenelitiDitunjuk = $rancangan ? Carbon::parse($rancangan->tanggal_rancangan_disetujui)->addDays(rand(1, 5)) : now();

        return [
            'id_rancangan' => $rancangan->id_rancangan,
            'revisi_rancangan' => $this->faker->optional()->word() . '.pdf',
            'revisi_matrik' => $this->faker->optional()->word() . '.pdf',
            'id_user' => User::factory(),
            'status_revisi' => 'Menunggu Peneliti',
            'status_validasi' => 'Menunggu Validasi',
            'catatan_revisi' => $this->faker->optional()->sentence(),
            'catatan_validasi' => $this->faker->optional()->sentence(),
            'tanggal_peneliti_ditunjuk' => $tanggalPenelitiDitunjuk,
            'tanggal_revisi' => $tanggalPenelitiDitunjuk->copy()->addDays(rand(3, 7)), // Revisi selesai dalam 3-7 hari
            'tanggal_validasi' => $tanggalPenelitiDitunjuk->copy()->addDays(rand(7, 14)), // Validasi selesai dalam 1-2 minggu
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
