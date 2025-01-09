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
        Schema::create('revisi', function (Blueprint $table) {
            $table->id('id_revisi');
            $table->unsignedBigInteger('id_rancangan'); // Tetap gunakan unsignedBigInteger untuk kolom ini
            $table->string('revisi_rancangan', 255)->nullable();
            $table->string('revisi_matrik', 255)->nullable();
            $table->text('catatan_revisi')->nullable();
            $table->unsignedBigInteger('id_user')->nullable();
            $table->enum('status_revisi', ['Direvisi', 'Belum Tahap Direvisi', 'Menunggu Revisi']);
            $table->timestamp('tanggal_revisi')->nullable();
            $table->timestamps();

            $table->foreign('id_rancangan')->references('id_rancangan')->on('rancangan_produk_hukum')->onDelete('cascade');
            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revisi');
    }
};
