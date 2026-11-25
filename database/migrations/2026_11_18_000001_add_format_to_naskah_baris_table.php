<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bentuk isi baris lampiran: html (editor seperti Word) atau berkas (PDF yang diunggah; rumusan berisi id
 * NaskahBerkas). Kosong berarti teks biasa seperti baris lain. Lampiran yang masih kosong langsung memakai editor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('naskah_baris', function (Blueprint $table) {
            $table->string('format', 10)->nullable()->after('bagian');
        });

        DB::table('naskah_baris')->where('bagian', 'lampiran')
            ->where(fn ($q) => $q->whereNull('rumusan_awal')->orWhere('rumusan_awal', ''))
            ->where(fn ($q) => $q->whereNull('rumusan_penyempurnaan')->orWhere('rumusan_penyempurnaan', ''))
            ->update(['format' => 'html']);
    }

    public function down(): void
    {
        Schema::table('naskah_baris', function (Blueprint $table) {
            $table->dropColumn('format');
        });
    }
};
