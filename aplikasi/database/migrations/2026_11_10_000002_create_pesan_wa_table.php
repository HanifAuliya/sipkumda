<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Antrean pesan WhatsApp. Pesan disimpan dulu lalu dikirim oleh wa:kirim, sehingga tidak hilang
 * saat sambungan WhatsApp putus: ia menunggu sampai tersambung lagi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesan_wa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nomor', 20);
            // Notifikasi lonceng asalnya: bila sudah dibuka di aplikasi, WA-nya tidak perlu dikirim lagi.
            $table->uuid('notification_id')->nullable()->index();
            $table->text('isi');
            $table->string('status', 12)->default('menunggu')->index();
            $table->unsignedTinyInteger('percobaan')->default(0);
            $table->string('galat', 500)->nullable();
            $table->timestamp('dikirim_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesan_wa');
    }
};
