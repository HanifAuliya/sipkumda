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
        Schema::create('dokumentasi_produk_hukum', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rancangan_id'); // Foreign key ke rancangan
            $table->string('nomor', 3)->unique(); // Nomor (hanya angka, maksimal 3 digit)
            $table->year('tahun'); // Tahun (diambil dari input)
            $table->date('tanggal'); // Tanggal publikasi
            $table->string('file_produk_hukum', 255)->nullable(); // Path file produk hukum
            $table->string('nomor_berita_daerah'); // Nomor berita daerah (maks 3 digit)
            $table->date('tanggal_berita_daerah'); // Tahun berita daerah
            $table->unsignedBigInteger('perangkat_daerah_id'); // Foreign key ke perangkat daerah
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('rancangan_id')->references('id_rancangan')->on('rancangan_produk_hukum')->onDelete('cascade');
            $table->foreign('perangkat_daerah_id')->references('id')->on('perangkat_daerah')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumentasi_produk_hukum');
    }
};
