<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Alur lama (rancangan, revisi, fasilitasi, nota dinas) tidak dipakai lagi; datanya hanya data uji.
 * Kode dan skema lamanya tersimpan di cabang v1/cadangan.
 *
 * - Tabel alur lama dan kolom dokumentasi_produk_hukum.rancangan_id dihapus.
 * - Nama jenis lama "Surat Keputusan" pada dokumentasi diseragamkan menjadi "Keputusan Bupati".
 * - Notifikasi alur lama dihapus; yang tersisa hanya notifikasi naskah dan dokumentasi.
 *
 * Berkas unggahan alur lama di storage/app/private/{rancangan,revisi,fasilitasi} tidak disentuh;
 * hapus manual bila sudah tidak diperlukan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('dokumentasi_produk_hukum', 'rancangan_id')) {
            Schema::table('dokumentasi_produk_hukum', function (Blueprint $table) {
                $table->dropForeign(['rancangan_id']);
                $table->dropColumn('rancangan_id');
            });
        }

        Schema::dropIfExists('nota_dinas');
        Schema::dropIfExists('fasilitasi_produk_hukum');
        Schema::dropIfExists('revisi');
        Schema::dropIfExists('rancangan_produk_hukum');

        DB::table('dokumentasi_produk_hukum')->where('jenis_dokumentasi', 'Surat Keputusan')->update(['jenis_dokumentasi' => 'Keputusan Bupati']);

        DB::table('notifications')
            ->whereNotIn('type', ['App\Notifications\NaskahNotification', 'App\Notifications\DokumentasiNotification'])
            ->delete();
    }

    /** Data alur lama tidak bisa dikembalikan; skemanya ada di cabang v1/cadangan. */
    public function down(): void
    {
        Schema::table('dokumentasi_produk_hukum', function (Blueprint $table) {
            $table->unsignedBigInteger('rancangan_id')->nullable()->after('id');
        });
    }
};
