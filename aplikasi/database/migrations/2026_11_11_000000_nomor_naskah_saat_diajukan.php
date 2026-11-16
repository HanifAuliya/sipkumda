<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nomor rancangan (NPB-2026-001) kini diberikan saat pertama kali diajukan, bukan saat draf dibuat,
 * supaya draf yang dibuang tidak meninggalkan lubang atau membuat nomor dipakai ulang.
 *
 * Penghitungnya disimpan di tabel tersendiri dan barisnya dikunci saat nomor diambil, sehingga
 * pengajuan yang bersamaan dari beberapa Perangkat Daerah antre di database dan tidak pernah
 * mendapat nomor yang sama. Penghitung hanya naik: nomor rancangan yang dihapus Admin tidak dipakai lagi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('naskah_nomor_urut', function (Blueprint $table) {
            $table->id();
            $table->string('awalan', 10);
            $table->unsignedSmallInteger('tahun');
            $table->unsignedInteger('terakhir')->default(0);
            $table->timestamps();

            $table->unique(['awalan', 'tahun']);
        });

        Schema::table('naskah', function (Blueprint $table) {
            $table->string('nomor')->nullable()->change();
        });

        // Draf yang belum pernah diajukan melepas nomornya; nomornya diberikan lagi saat diajukan.
        DB::table('naskah')->where('tahap', 'draf')->whereNull('diajukan_at')->update(['nomor' => null]);

        // Penghitung melanjutkan nomor tertinggi yang sudah dipakai per awalan dan tahun.
        $tertinggi = [];

        foreach (DB::table('naskah')->whereNotNull('nomor')->pluck('nomor') as $nomor) {
            if (preg_match('/^([A-Z]+)-(\d{4})-(\d+)$/', $nomor, $m)) {
                $kunci = "{$m[1]}-{$m[2]}";
                $tertinggi[$kunci] = max($tertinggi[$kunci] ?? 0, (int) $m[3]);
            }
        }

        foreach ($tertinggi as $kunci => $terakhir) {
            [$awalan, $tahun] = explode('-', $kunci);
            DB::table('naskah_nomor_urut')->insert([
                'awalan' => $awalan,
                'tahun' => (int) $tahun,
                'terakhir' => $terakhir,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /** Nomor draf yang sudah dilepas tidak bisa dikembalikan; draf tanpa nomor diberi nomor sementara. */
    public function down(): void
    {
        foreach (DB::table('naskah')->whereNull('nomor')->pluck('id') as $id) {
            DB::table('naskah')->where('id', $id)->update(['nomor' => "DRAF-{$id}"]);
        }

        Schema::table('naskah', function (Blueprint $table) {
            $table->string('nomor')->nullable(false)->change();
        });

        Schema::dropIfExists('naskah_nomor_urut');
    }
};
