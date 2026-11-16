<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Format dokumen (margin, kop, isi nota dinas) disimpan sebagai JSON, lebih panjang dari 255 karakter. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan', function (Blueprint $table) {
            $table->text('nilai')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan', function (Blueprint $table) {
            $table->string('nilai')->nullable()->change();
        });
    }
};
