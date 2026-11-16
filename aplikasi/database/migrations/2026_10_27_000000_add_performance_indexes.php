<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indeks untuk kolom yang dipakai filter, urutan, dan pencarian di daftar-daftar
 * aplikasi serta halaman JDIH publik. Hanya menambah indeks, tidak mengubah data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rancangan_produk_hukum', function (Blueprint $table) {
            $table->index(['status_berkas', 'status_rancangan'], 'rancangan_status_index');
            $table->index(['jenis_rancangan', 'tanggal_pengajuan'], 'rancangan_jenis_tanggal_index');
            $table->index('tanggal_pengajuan', 'rancangan_tanggal_pengajuan_index');
        });

        Schema::table('revisi', function (Blueprint $table) {
            $table->index(['status_revisi', 'status_validasi'], 'revisi_status_index');
        });

        Schema::table('fasilitasi_produk_hukum', function (Blueprint $table) {
            $table->index('status_berkas_fasilitasi', 'fasilitasi_status_berkas_index');
            $table->index('status_validasi_fasilitasi', 'fasilitasi_status_validasi_index');
            $table->index('tanggal_fasilitasi', 'fasilitasi_tanggal_index');
        });

        Schema::table('dokumentasi_produk_hukum', function (Blueprint $table) {
            $table->index(['tahun', 'nomor'], 'dokumentasi_tahun_nomor_index');
            $table->index(['jenis_dokumentasi', 'tahun'], 'dokumentasi_jenis_tahun_index');
            $table->index('tanggal_pengarsipan', 'dokumentasi_tanggal_pengarsipan_index');
            $table->index('tanggal_penetapan', 'dokumentasi_tanggal_penetapan_index');

            // Pencarian kata kunci JDIH (MySQL/MariaDB).
            if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'])) {
                $table->fullText('tentang_dokumentasi', 'dokumentasi_tentang_fulltext');
            }
        });

        Schema::table('nota_dinas', function (Blueprint $table) {
            $table->index('tanggal_nota', 'nota_dinas_tanggal_index');
        });

        // Lonceng notifikasi menghitung notifikasi belum dibaca per pengguna.
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_unread_index');
        });
    }

    public function down(): void
    {
        Schema::table('rancangan_produk_hukum', function (Blueprint $table) {
            $table->dropIndex('rancangan_status_index');
            $table->dropIndex('rancangan_jenis_tanggal_index');
            $table->dropIndex('rancangan_tanggal_pengajuan_index');
        });

        Schema::table('revisi', fn (Blueprint $table) => $table->dropIndex('revisi_status_index'));

        Schema::table('fasilitasi_produk_hukum', function (Blueprint $table) {
            $table->dropIndex('fasilitasi_status_berkas_index');
            $table->dropIndex('fasilitasi_status_validasi_index');
            $table->dropIndex('fasilitasi_tanggal_index');
        });

        Schema::table('dokumentasi_produk_hukum', function (Blueprint $table) {
            $table->dropIndex('dokumentasi_tahun_nomor_index');
            $table->dropIndex('dokumentasi_jenis_tahun_index');
            $table->dropIndex('dokumentasi_tanggal_pengarsipan_index');
            $table->dropIndex('dokumentasi_tanggal_penetapan_index');

            if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'])) {
                $table->dropFullText('dokumentasi_tentang_fulltext');
            }
        });

        Schema::table('nota_dinas', fn (Blueprint $table) => $table->dropIndex('nota_dinas_tanggal_index'));
        Schema::table('notifications', fn (Blueprint $table) => $table->dropIndex('notifications_unread_index'));
    }
};
