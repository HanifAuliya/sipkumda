<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\PerangkatDaerah;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    public function run()
    { // Buat user dan tetapkan role
        // User::factory()->count(10)->create()->each(function ($user) {
        //     $user->assignRole('Perangkat Daerah'); // Ganti dengan role yang sesuai
        // });

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $verifikatorRole = Role::firstOrCreate(['name' => 'Verifikator']);
        $perangkatDaerahRole = Role::firstOrCreate(['name' => 'Perangkat Daerah']);
        $penelitiRole = Role::firstOrCreate(['name' => 'Peneliti']);
        $tamuRole = Role::firstOrCreate(['name' => 'Tamu']);

        // Buat User Admin
        $superAdmin = User::firstOrCreate([
            'email' => 'superadmin@example.com', // Email unik
        ], [
            'nama_user' => 'Super Admin ',
            'NIP' => 'superadmin',
            'password' => bcrypt('bagianhukumhst'), // Default password
            'email_verified_at' => Carbon::now(), // Set email_verified_at
            'perangkat_daerah_id' => PerangkatDaerah::inRandomOrder()->first()->id ?? null, // Perangkat Daerah Acak
        ]);
        $superAdmin->assignRole($superAdminRole);

        // Buat User Admin
        $admin = User::firstOrCreate([
            'email' => 'admin@example.com', // Email unik
        ], [
            'nama_user' => 'Admin',
            'NIP' => 'admin',
            'password' => bcrypt('password'), // Default password
            'email_verified_at' => Carbon::now(), // Set email_verified_at
            'perangkat_daerah_id' => PerangkatDaerah::inRandomOrder()->first()->id ?? null, // Perangkat Daerah Acak
        ]);
        $admin->assignRole($adminRole);

        // Buat User Verifikator
        $verifikator = User::firstOrCreate([
            'email' => 'verifikator@example.com', // Email unik
        ], [
            'nama_user' => 'Verifikator User',
            'NIP' => 'verifikator',
            'password' => bcrypt('password'), // Default password
            'email_verified_at' => Carbon::now(), // Set email_verified_at
            'perangkat_daerah_id' => PerangkatDaerah::inRandomOrder()->first()->id ?? null, // Perangkat Daerah Acak
        ]);
        $verifikator->assignRole($verifikatorRole);

        // Buat User Perangkat Daerah
        $perangkatDaerah = User::firstOrCreate([
            'email' => 'perangkatdaerah@example.com', // Email unik
        ], [
            'nama_user' => 'Perangkat Daerah User',
            'NIP' => 'perangkatdaerah',
            'password' => bcrypt('password'), // Default password
            'email_verified_at' => Carbon::now(), // Set email_verified_at
            'perangkat_daerah_id' => PerangkatDaerah::inRandomOrder()->first()->id ?? null, // Perangkat Daerah Acak
        ]);
        $perangkatDaerah->assignRole($perangkatDaerahRole);

        // Buat User Peneliti
        $peneliti = User::firstOrCreate([
            'email' => 'peneliti@example.com', // Email unik
        ], [
            'nama_user' => 'Peneliti User',
            'NIP' => 'peneliti',
            'password' => bcrypt('password'), // Default password
            'email_verified_at' => Carbon::now(), // Set email_verified_at
            'perangkat_daerah_id' => PerangkatDaerah::inRandomOrder()->first()->id ?? null, // Perangkat Daerah Acak
        ]);
        $peneliti->assignRole($penelitiRole);

        // Buat User Tamu
        $tamu = User::firstOrCreate([
            'email' => 'tamu@example.com', // Email unik
        ], [
            'nama_user' => 'Tamu User',
            'NIP' => 'tamu',
            'password' => bcrypt('password'), // Default password
            'email_verified_at' => Carbon::now(), // Set email_verified_at
            'perangkat_daerah_id' => PerangkatDaerah::inRandomOrder()->first()->id ?? null, // Perangkat Daerah Acak
        ]);
        $tamu->assignRole($tamuRole);
    }
}
