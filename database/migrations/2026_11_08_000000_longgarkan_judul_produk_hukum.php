<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Judul perubahan peraturan bisa melewati 255 karakter. naskah.tentang sudah bertipe text. */
    public function up(): void
    {
        Schema::table('rancangan_produk_hukum', function (Blueprint $table) {
            $table->string('tentang', 1000)->change();
        });

        Schema::table('dokumentasi_produk_hukum', function (Blueprint $table) {
            $table->string('tentang_dokumentasi', 1000)->nullable()->change();
        });
    }

    /** Gagal, bukan memotong judul, bila sudah ada judul yang lebih panjang dari 255 karakter. */
    public function down(): void
    {
        Schema::table('rancangan_produk_hukum', function (Blueprint $table) {
            $table->string('tentang', 255)->change();
        });

        Schema::table('dokumentasi_produk_hukum', function (Blueprint $table) {
            $table->string('tentang_dokumentasi', 255)->nullable()->change();
        });
    }
};
