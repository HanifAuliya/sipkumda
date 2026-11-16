<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanda tangan Kepala Bagian Hukum kini milik akun Verifikator: ia mengaturnya sendiri di Profil.
 * Baris tanpa akun tetap boleh (Kabag lama, Plt/Plh) dan dikelola Admin di Kelola TTD.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tanda_tangan', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->unique()->after('id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tanda_tangan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
