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
        Schema::create('tanda_tangan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_ttd', 100); // Nama tanda tangan
            $table->string('file_ttd', 255); // Path file tanda tangan
            $table->unsignedBigInteger('dibuat_oleh'); // Foreign key ke tabel users
            $table->enum('status', ['Aktif', 'Tidak Aktif'])->default('Aktif'); // Status tanda tangan
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('dibuat_oleh')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tanda_tangan');
    }
};
