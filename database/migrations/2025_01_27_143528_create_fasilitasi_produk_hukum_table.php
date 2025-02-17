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
        Schema::create('fasilitasi_produk_hukum', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rancangan_id'); // Foreign key ke rancangan_produk_hukum
            $table->date('tanggal_fasilitasi'); // Tanggal pengajuan fasilitasi
            $table->string('file_rancangan', 255); // Path file rancangan
            $table->enum('status_berkas_fasilitasi', ['Menunggu Persetujuan', 'Disetujui', 'Ditolak'])->nullable(); // Status berkas fasilitasi
            $table->text('catatan_persetujuan_fasilitasi')->nullable();
            $table->date('tanggal_persetujuan_berkas')->nullable(); // Tanggal persetujuan admin
            $table->enum('status_validasi_fasilitasi', ['Belum Tahap Validasi', 'Menunggu Validasi', 'Diterima', 'Ditolak'])->default('Belum Tahap Validasi'); // Status validasi fasilitasi
            $table->text('catatan_validasi_fasilitasi')->nullable();
            $table->date('tanggal_validasi_fasilitasi')->nullable(); // Tanggal validasi

            $table->enum('status_paraf_koordinasi', ['Belum', 'Selesai'])->default('Belum');
            $table->date('tanggal_paraf_koordinasi')->nullable();
            $table->enum('status_asisten', ['Belum', 'Selesai'])->default('Belum');
            $table->date('tanggal_asisten')->nullable();
            $table->enum('status_sekda', ['Belum', 'Selesai'])->default('Belum');
            $table->date('tanggal_sekda')->nullable();
            $table->enum('status_bupati', ['Belum', 'Selesai'])->default('Belum');
            $table->date('tanggal_bupati')->nullable();

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('rancangan_id')->references('id_rancangan')->on('rancangan_produk_hukum')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fasilitasi_produk_hukum');
    }
};
