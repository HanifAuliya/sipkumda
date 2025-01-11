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
        Schema::create('rancangan_produk_hukum', function (Blueprint $table) {
            $table->id('id_rancangan'); // Primary key
            $table->string('slug')->unique()->nullable();
            $table->string('no_rancangan')->unique();
            $table->enum('jenis_rancangan', ['Peraturan Bupati', 'Surat Keputusan']);
            $table->string('tentang', 255);
            $table->string('nota_dinas_pd', 255)->nullable();
            $table->string('rancangan', 255)->nullable();
            $table->string('matrik', 255)->nullable();
            $table->string('bahan_pendukung', 255)->nullable();
            $table->dateTime('tanggal_pengajuan')->nullable();
            $table->foreignId('id_user')->constrained('users')->onDelete('cascade');
            $table->enum('status_berkas', ['Disetujui', 'Ditolak', 'Menunggu Persetujuan'])->nullable();
            $table->enum('status_rancangan', ['Disetujui', 'Ditolak', 'Dalam Proses'])->nullable();
            $table->text('catatan_berkas')->nullable();
            $table->dateTime('tanggal_berkas_disetujui')->nullable(); // Kolom tanggal disetujui
            $table->dateTime('tanggal_rancangan_disetujui')->nullable(); // Kolom tanggal peneliti dipilih
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rancangan_produk_hukum');
    }
};
