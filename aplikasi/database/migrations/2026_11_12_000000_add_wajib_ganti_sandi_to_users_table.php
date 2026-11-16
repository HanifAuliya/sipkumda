<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kata sandi sementara dari Admin (untuk saat email aktivasi tidak bisa dikirim). Selama kolom ini true,
 * pengguna hanya bisa membuka halaman Profil sampai ia mengganti kata sandinya sendiri.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('wajib_ganti_sandi')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('wajib_ganti_sandi');
        });
    }
};
