<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('naskah', function (Blueprint $table) {
            $table->json('tahapan_ttd')->nullable()->after('terkunci_at');
            $table->foreignId('dokumentasi_id')->nullable()->after('tahapan_ttd')
                ->constrained('dokumentasi_produk_hukum')->nullOnDelete();
        });

        Schema::create('naskah_nota_dinas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('naskah_id')->unique()->constrained('naskah')->cascadeOnDelete();
            $table->string('nomor_inputan', 3);
            $table->string('nomor_nota', 100);
            $table->date('tanggal_nota');
            $table->string('kepada');
            // Tanda tangan yang sudah dipakai di dokumen tidak boleh terhapus.
            $table->foreignId('tanda_tangan_id')->constrained('tanda_tangan')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('naskah_nota_dinas');

        Schema::table('naskah', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dokumentasi_id');
            $table->dropColumn('tahapan_ttd');
        });
    }
};
