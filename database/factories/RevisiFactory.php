<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Revisi;
use App\Models\RancanganProdukHukum;
use App\Models\User;

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
        return [
            'id_rancangan' => RancanganProdukHukum::factory(), // Relasi ke RancanganProdukHukum (satu rancangan, satu revisi)
            'revisi_rancangan' => $this->faker->optional()->word() . '.pdf', // Opsional file revisi rancangan
            'revisi_matrik' => $this->faker->optional()->word() . '.pdf', // Opsional file revisi matrik
            'id_user' => User::factory(), // Relasi ke User (peneliti yang melakukan revisi)
            'status_revisi' => $this->faker->randomElement([
                'Belum Tahap Revisi',
                'Menunggu Peneliti',
                'Proses Revisi',
                'Direvisi',
            ]), // Status revisi
            'status_validasi' => $this->faker->randomElement([
                'Belum Tahap Validasi',
                'Menunggu Validasi',
                'Diterima',
                'Ditolak',
            ]), // Status validasi
            'catatan_revisi' => $this->faker->optional()->sentence(), // Opsional catatan revisi
            'catatan_validasi' => $this->faker->optional()->sentence(), // Opsional catatan validasi
            'tanggal_peneliti_ditunjuk' => $this->faker->optional()->dateTimeBetween('-6 months', 'now'),
            'tanggal_revisi' => $this->faker->optional()->dateTimeBetween('-3 months', 'now'),
            'tanggal_validasi' => $this->faker->optional()->dateTimeBetween('-1 months', 'now'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
