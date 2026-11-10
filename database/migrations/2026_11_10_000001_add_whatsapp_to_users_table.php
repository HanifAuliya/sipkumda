<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Nomor WhatsApp untuk notifikasi; disimpan dalam bentuk 62xxx. Pengguna bisa mematikan WA sendiri. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('no_wa', 20)->nullable()->after('email');
            $table->boolean('wa_aktif')->default(true)->after('no_wa');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['no_wa', 'wa_aktif']);
        });
    }
};
