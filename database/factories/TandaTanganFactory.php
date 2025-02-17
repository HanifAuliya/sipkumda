<?php

namespace Database\Factories;

use App\Models\TandaTangan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TandaTangan>
 */
class TandaTanganFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = TandaTangan::class;

    public function definition(): array
    {
        return [
            'nama_ttd' => 'Tanda Tangan ' . $this->faker->word,
            'file_ttd' => 'tanda_tangan/' . $this->faker->uuid . '.png',
            'dibuat_oleh' => User::role('Verifikator')->inRandomOrder()->first()->id ?? User::factory()->create()->assignRole('Verifikator')->id,
            'status' => $this->faker->randomElement(['Aktif', 'Tidak Aktif']),
        ];
    }
}
