<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Kapan pengguna menyelesaikan atau melewati tur pengenalan; kosong berarti tur muncul di Dashboard. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('tur_selesai_at')->nullable()->after('wajib_ganti_sandi');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tur_selesai_at');
        });
    }
};
