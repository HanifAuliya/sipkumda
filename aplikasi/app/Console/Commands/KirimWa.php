<?php

namespace App\Console\Commands;

use App\Support\Wa\AntreanWa;
use Illuminate\Console\Command;

/** Dijalankan penjadwal setiap menit: mengirim pesan WhatsApp yang masih menunggu. */
class KirimWa extends Command
{
    protected $signature = 'wa:kirim {--batas=20 : Pesan paling banyak per jalan}';

    protected $description = 'Kirim antrean notifikasi WhatsApp';

    public function handle(AntreanWa $antrean): int
    {
        $terkirim = $antrean->proses((int) $this->option('batas'));
        $this->info("Terkirim: {$terkirim}");

        return self::SUCCESS;
    }
}
