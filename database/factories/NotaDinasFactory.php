<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\NotaDinas;
use App\Models\FasilitasiProdukHukum;
use App\Models\TandaTangan;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotaDinas>
 */
class NotaDinasFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = NotaDinas::class;

    public function definition(): array
    {
        $fasilitasi = FasilitasiProdukHukum::inRandomOrder()->first();
        $tandaTangan = TandaTangan::inRandomOrder()->first();

        return [
            'fasilitasi_id' => $fasilitasi ? $fasilitasi->id : FasilitasiProdukHukum::factory()->create()->id,
            'nomor_inputan' => str_pad($this->faker->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'nomor_nota' => "180/" . strtoupper(substr($fasilitasi->rancangan->jenis_rancangan ?? 'ND', 0, 2)) . "/KUM/" . now()->year,
            'tanggal_nota' => now(),
            'file_nota_dinas' => 'path/to/file_nota_' . $this->faker->unique()->numberBetween(1, 100) . '.pdf',
            'tanda_tangan_id' => $tandaTangan ? $tandaTangan->id : TandaTangan::factory()->create()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
