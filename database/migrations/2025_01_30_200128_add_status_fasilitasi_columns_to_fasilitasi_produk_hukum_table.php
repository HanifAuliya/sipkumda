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
        Schema::table('fasilitasi_produk_hukum', function (Blueprint $table) {
            $table->enum('status_paraf_koordinasi', ['Belum', 'Selesai'])->default('Belum');
            $table->date('tanggal_paraf_koordinasi')->nullable();
            $table->enum('status_asisten', ['Belum', 'Selesai'])->default('Belum');
            $table->date('tanggal_asisten')->nullable();
            $table->enum('status_sekda', ['Belum', 'Selesai'])->default('Belum');
            $table->date('tanggal_sekda')->nullable();
            $table->enum('status_bupati', ['Belum', 'Selesai'])->default('Belum');
            $table->date('tanggal_bupati')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fasilitasi_produk_hukum', function (Blueprint $table) {
            $table->dropColumn([
                'status_paraf_koordinasi',
                'tanggal_paraf_koordinasi',
                'status_asisten',
                'tanggal_asisten',
                'status_sekda',
                'tanggal_sekda',
                'status_bupati',
                'tanggal_bupati',
            ]);
        });
    }
};
