<?php

use App\Support\FormatDokumen;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Keputusan Bupati yang diarsipkan sebelum ada nomor berformat masih tampil "Nomor 2 Tahun 2025",
 * sama dengan Peraturan Bupati. Pola dari Format Dokumen dipasang supaya tampil
 * "Nomor 140/2/412.1.6/TAHUN 2025". Nomor urut dan tahunnya tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        $pola = FormatDokumen::naskah(FormatDokumen::KEPBUP)['nomor_arsip'] ?: '140/{nomor}/412.1.6/TAHUN {tahun}';

        DB::table('dokumentasi_produk_hukum')
            ->where('jenis_dokumentasi', FormatDokumen::KEPBUP)
            ->where(fn ($q) => $q->whereNull('pola_nomor')->orWhere('pola_nomor', ''))
            ->update(['pola_nomor' => $pola]);
    }

    public function down(): void
    {
        // Tidak dibalik: pola yang dipasang tidak bisa dibedakan dari yang diisi pengguna.
    }
};
