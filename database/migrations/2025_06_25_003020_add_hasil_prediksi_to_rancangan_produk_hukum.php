<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rancangan_produk_hukum', function (Blueprint $table) {
            $table->enum('hasil_prediksi_kelengkapan', ['Lengkap', 'Tidak Lengkap'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rancangan_produk_hukum', function (Blueprint $table) {
            $table->dropColumn('hasil_prediksi_kelengkapan');
        });
    }
};
