<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pola nomor lengkap produk hukum, mis. "140/{nomor}/412.1.6/TAHUN {tahun}" untuk Keputusan Bupati.
 * Kolom nomor tetap berisi nomor urutnya saja, jadi pemeriksaan nomor ganda, urutan, dan nama berkas
 * tidak berubah; nomor lengkap disusun dari pola ini saat ditampilkan. Kosong = "Nomor 1 Tahun 2025".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dokumentasi_produk_hukum', function (Blueprint $table) {
            $table->string('pola_nomor', 100)->nullable()->after('tahun');
        });
    }

    public function down(): void
    {
        Schema::table('dokumentasi_produk_hukum', function (Blueprint $table) {
            $table->dropColumn('pola_nomor');
        });
    }
};
