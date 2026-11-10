<?php

namespace App\Jobs;

use App\Support\Wa\AntreanWa;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Mengirim antrean WhatsApp segera setelah ada pesan baru. Bila queue worker tidak berjalan,
 * wa:kirim di penjadwal tetap mengirimnya paling lambat semenit kemudian.
 */
class KirimAntreanWa implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $uniqueFor = 60;

    public int $tries = 1;

    public function handle(AntreanWa $antrean): void
    {
        $antrean->proses();
    }
}
