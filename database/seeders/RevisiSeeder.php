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

        $rancangans = RancanganProdukHukum::all();
        $peneliti = User::role('peneliti')->get(); // Ambil user dengan role "peneliti"

        foreach ($rancangans as $rancangan) {
            $statusRevisi = $rancangan->status_berkas === 'Disetujui'
                ? 'Menunggu Peneliti'
                : 'Belum Tahap Revisi'; // Menentukan status revisi awal

            $tanggalPenelitiDitunjuk = $statusRevisi === 'Menunggu Peneliti'
                ? null
                : fake()->optional()->dateTimeBetween('-1 month', 'now'); // Tanggal peneliti hanya jika revisi dalam status lebih lanjut

            Revisi::create([
                'id_rancangan' => $rancangan->id_rancangan,
                'revisi_rancangan' => null, // Kosong di awal
                'revisi_matrik' => null, // Kosong di awal
                'catatan_revisi' => null, // Kosong di awal
                'id_user' => $statusRevisi === 'Menunggu Peneliti' ? $peneliti->random()->id : null,
                'status_revisi' => $statusRevisi,
                'status_validasi' => 'Belum Tahap Validasi', // Default status validasi
                'catatan_validasi' => null,
                'tanggal_peneliti_ditunjuk' => null,
                'tanggal_revisi' => null,
                'tanggal_validasi' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
