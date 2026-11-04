<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PerangkatDaerah>
 */
class PerangkatDaerahFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_perangkat_daerah' => 'Dinas ' . $this->faker->unique()->words(2, true),
        ];
    }
}
