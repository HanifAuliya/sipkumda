<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Keterangan berkas naskah, misalnya alasan nota dinas diterbitkan ulang. Versi yang diganti
 * tetap tersimpan dengan aktif = false, jadi versi sebelum dan sesudah perubahan bisa dibandingkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('naskah_berkas', function (Blueprint $table) {
            $table->string('keterangan', 500)->nullable()->after('aktif');
        });
    }

    public function down(): void
    {
        Schema::table('naskah_berkas', function (Blueprint $table) {
            $table->dropColumn('keterangan');
        });
    }
};
