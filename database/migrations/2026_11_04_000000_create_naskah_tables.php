<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel aplikasi v2: matriks menjadi sumber utama naskah.
 * Berdiri sendiri dari tabel rancangan lama supaya alur lama tetap berjalan sampai v2 siap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('naskah', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->unique();
            $table->uuid('slug')->unique();
            $table->string('jenis');
            $table->text('tentang');
            $table->foreignId('pemrakarsa_id')->constrained('users');
            $table->foreignId('peneliti_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tahap')->default('draf')->index();
            $table->unsignedInteger('giliran')->default(1);
            $table->text('catatan_tahap')->nullable();
            $table->string('nomor_nota')->nullable();
            $table->date('tanggal_nota')->nullable();
            $table->timestamp('diajukan_at')->nullable();
            $table->timestamp('terkunci_at')->nullable();
            $table->timestamps();
        });

        Schema::create('naskah_baris', function (Blueprint $table) {
            $table->id();
            $table->foreignId('naskah_id')->constrained('naskah')->cascadeOnDelete();
            $table->unsignedInteger('urutan');
            $table->string('bagian');
            $table->string('label')->nullable();
            $table->longText('rumusan_awal')->nullable();
            $table->longText('rumusan_penyempurnaan')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('status')->default('belum');
            $table->unsignedInteger('giliran_diubah')->nullable();
            $table->foreignId('diubah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['naskah_id', 'urutan']);
        });

        Schema::create('naskah_berkas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('naskah_id')->constrained('naskah')->cascadeOnDelete();
            $table->string('jenis');
            $table->string('path');
            $table->string('nama_asli');
            $table->boolean('aktif')->default(true);
            $table->foreignId('diunggah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['naskah_id', 'jenis']);
        });

        Schema::create('naskah_riwayat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('naskah_id')->constrained('naskah')->cascadeOnDelete();
            $table->foreignId('baris_id')->nullable()->constrained('naskah_baris')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi');
            $table->text('catatan')->nullable();
            $table->longText('sebelum')->nullable();
            $table->longText('sesudah')->nullable();
            $table->timestamps();

            $table->index(['naskah_id', 'id']);
        });

        // Salinan seluruh baris setiap kali naskah berpindah giliran.
        Schema::create('naskah_versi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('naskah_id')->constrained('naskah')->cascadeOnDelete();
            $table->unsignedInteger('versi');
            $table->string('tahap');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('isi');
            $table->timestamps();

            $table->unique(['naskah_id', 'versi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('naskah_versi');
        Schema::dropIfExists('naskah_riwayat');
        Schema::dropIfExists('naskah_berkas');
        Schema::dropIfExists('naskah_baris');
        Schema::dropIfExists('naskah');
    }
};
