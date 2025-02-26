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
            $table->unsignedBigInteger('rancangan_id')->nullable(); // Foreign key ke rancangan
            $table->string('nomor', 3); // Nomor (hanya angka, maksimal 3 digit)
            $table->year('tahun'); // Tahun (diambil dari input)
            $table->date('tanggal_pengarsipan'); // Tanggal pengarsipan (sebelumnya 'tanggal')
            $table->string('file_produk_hukum', 255)->nullable(); // Path file produk hukum
            $table->string('nomor_tahun_berita', 10)->nullable(); // Nomor/tahun berita (gabungan nomor dan tahun)
            $table->date('tanggal_penetapan')->nullable(); // Tanggal penetapan (kolom baru)
            $table->unsignedBigInteger('perangkat_daerah_id'); // Foreign key ke perangkat daerah
            $table->string('jenis_dokumentasi')->nullable(); // Kolom baru: jenis dokumentasi
            $table->string('tentang_dokumentasi')->nullable(); // Kolom baru: tentang dokumentasi
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
