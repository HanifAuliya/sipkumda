<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Salah ketik dari seeder awal: "Kesejateraan" menjadi "Kesejahteraan". Akun dan arsip tetap tertaut lewat id. */
return new class extends Migration
{
    public function up(): void
    {
        $benar = 'Bagian Kesejahteraan Rakyat';

        if (! DB::table('perangkat_daerah')->where('nama_perangkat_daerah', $benar)->exists()) {
            DB::table('perangkat_daerah')->where('nama_perangkat_daerah', 'Bagian Kesejateraan Rakyat')
                ->update(['nama_perangkat_daerah' => $benar]);
        }
    }

    public function down(): void
    {
        // Tidak dibalik: nama yang benar dipertahankan.
    }
};
