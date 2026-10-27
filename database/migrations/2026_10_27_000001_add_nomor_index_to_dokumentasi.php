<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pencarian JDIH berdasarkan nomor saja ("12") tidak bisa memakai indeks (tahun, nomor).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dokumentasi_produk_hukum', function (Blueprint $table) {
            $table->index('nomor', 'dokumentasi_nomor_index');
        });
    }

    public function down(): void
    {
        Schema::table('dokumentasi_produk_hukum', function (Blueprint $table) {
            $table->dropIndex('dokumentasi_nomor_index');
        });
    }
};
