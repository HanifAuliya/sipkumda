<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanda tangan yang dibubuhkan per dokumen:
 * - naskah.ttd_peneliti: tanda tangan peneliti di matriks, dibubuhkan saat ia menyelesaikan
 *   pemeriksaan fasilitasi (tersimpan atau digambar langsung). Kosong = pakai yang di Profil.
 * - naskah_nota_dinas.file_ttd: tanda tangan Kepala Bagian Hukum yang digambar langsung saat
 *   menerbitkan. Kosong = pakai gambar penanda tangan yang dipilih.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('naskah', function (Blueprint $table) {
            $table->string('ttd_peneliti')->nullable();
        });
        Schema::table('naskah_nota_dinas', function (Blueprint $table) {
            $table->string('file_ttd')->nullable()->after('tanda_tangan_id');
        });
    }

    public function down(): void
    {
        Schema::table('naskah', function (Blueprint $table) {
            $table->dropColumn('ttd_peneliti');
        });
        Schema::table('naskah_nota_dinas', function (Blueprint $table) {
            $table->dropColumn('file_ttd');
        });
    }
};
