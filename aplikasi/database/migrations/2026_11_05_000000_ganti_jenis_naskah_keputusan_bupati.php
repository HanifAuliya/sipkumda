<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Aplikasi v2 memakai nama resmi "Keputusan Bupati". Tabel alur lama dan arsip
 * dokumentasi tidak diubah: keduanya masih boleh memuat "Surat Keputusan".
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('naskah')->where('jenis', 'Surat Keputusan')->update(['jenis' => 'Keputusan Bupati']);
    }

    public function down(): void
    {
        DB::table('naskah')->where('jenis', 'Keputusan Bupati')->update(['jenis' => 'Surat Keputusan']);
    }
};
