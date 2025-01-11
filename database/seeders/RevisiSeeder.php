<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Revisi;
use App\Models\RancanganProdukHukum;
use App\Models\User;
use Carbon\Carbon;

class RevisiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Ambil semua rancangan produk hukum
        $rancangans = RancanganProdukHukum::all();
        $peneliti = User::role('peneliti')->get(); // Ambil user dengan role "peneliti"

        // Tambahkan data dummy revisi
        foreach ($rancangans as $rancangan) {
            // Buat data revisi untuk setiap rancangan
            Revisi::create([
                'id_rancangan' => $rancangan->id_rancangan, // Ambil ID rancangan
                'revisi_rancangan' => 'revisi_' . $rancangan->id_rancangan . '.docx', // File revisi rancangan
                'revisi_matrik' => 'matrik_' . $rancangan->id_rancangan . '.xlsx', // File revisi matrik
                'catatan_revisi' => fake()->paragraph(), // Catatan revisi
                'id_user' => $peneliti->random()->id ?? null, // Pilih peneliti secara acak
                'status_revisi' => fake()->randomElement([
                    'Belum Tahap Revisi',
                    'Menunggu Peneliti',
                    'Menunggu Revisi',
                    'Menunggu Validasi',
                    'Direvisi',
                ]), // Status revisi acak
                'status_validasi' => fake()->randomElement(['Belum Validasi', 'Diterima', 'Ditolak']), // Status validasi acak
                'catatan_validasi' => fake()->optional()->paragraph(), // Catatan validasi
                'tanggal_peneliti_ditunjuk' => fake()->optional()->dateTimeBetween('-1 month', 'now'), // Tanggal peneliti ditunjuk
                'tanggal_revisi' => fake()->optional()->dateTimeBetween('-1 week', 'now'), // Tanggal revisi
                'tanggal_validasi' => fake()->optional()->dateTimeBetween('-1 week', 'now'), // Tanggal validasi
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
