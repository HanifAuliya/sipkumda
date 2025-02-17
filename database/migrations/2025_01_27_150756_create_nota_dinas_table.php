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
        Schema::create('nota_dinas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fasilitasi_id'); // Foreign key ke tabel fasilitasi_produk_hukum
            $table->string('nomor_inputan', 3);
            $table->string('nomor_nota', 100); // Nomor nota dinas
            $table->date('tanggal_nota'); // Tanggal pembuatan nota dinas
            $table->string('file_nota_dinas', 255)->nullable(); // Path file nota dinas
            $table->unsignedBigInteger('tanda_tangan_id'); // Foreign key ke tabel tanda_tangan
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('fasilitasi_id')->references('id')->on('fasilitasi_produk_hukum')->onDelete('cascade');
            $table->foreign('tanda_tangan_id')->references('id')->on('tanda_tangan')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nota_dinas');
    }
};
