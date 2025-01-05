<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The default password used by the factory.
     */
    protected static ?string $defaultPassword = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'NIP' => $this->faker->unique()->numerify('#########'), // NIP unik
            'nama_user' => $this->faker->name(),                  // Nama pengguna
            'email' => $this->faker->unique()->safeEmail(),       // Email unik
            'email_verified_at' => now(),                        // Email terverifikasi secara default
            'password' => self::$defaultPassword ?: bcrypt('password'), // Password default
            'perangkat_daerah_id' => $this->faker->numberBetween(1, 20), // Angka acak 1-20 
            'remember_token' => Str::random(10),                 // Token remember me
            'created_at' => now(),                               // Waktu pembuatan
            'updated_at' => now(),                               // Waktu pembaruan
        ];
    }

    /**
     * Set a custom password for the factory.
     */
    public static function withPassword(string $password): void
    {
        self::$defaultPassword = bcrypt($password);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null, // Email tidak terverifikasi
        ]);
    }

    /**
     * Indicate that the model is an admin.
     */
    public function asAdmin(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'Admin', // Tetapkan role sebagai Admin
        ]);
    }

    /**
     * Indicate that the model is a verifikator.
     */
    public function asVerifikator(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'Verifikator', // Tetapkan role sebagai Verifikator
        ]);
    }

    /**
     * Indicate that the model is a peneliti.
     */
    public function asPeneliti(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'Peneliti', // Tetapkan role sebagai Peneliti
        ]);
    }

    /**
     * Indicate that the model is a perangkat daerah.
     */
    public function asPerangkatDaerah(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'Perangkat_Daerah', // Tetapkan role sebagai Perangkat Daerah
        ]);
    }

    /**
     * Set the perangkat daerah for the user.
     */
    public function withPerangkatDaerah(int $perangkatDaerahId): static
    {
        return $this->state(fn(array $attributes) => [
            'perangkat_daerah_id' => $perangkatDaerahId, // Tetapkan perangkat daerah
        ]);
    }
}
