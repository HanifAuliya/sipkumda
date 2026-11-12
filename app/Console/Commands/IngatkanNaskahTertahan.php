<?php

namespace App\Console\Commands;

use App\Enums\TahapNaskah as T;
use App\Notifications\NaskahNotification;
use App\Services\AlurNaskah;
use App\Support\LaporanNaskah;
use App\Support\PesanNotifikasi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Mengingatkan pemegang giliran naskah yang tertahan di satu tahap. Dijalankan setiap pagi; pengingat
 * dikirim saat lamanya tepat kelipatan batas hari (7, 14, 21, ...), jadi paling sering seminggu sekali
 * untuk naskah yang sama. Batasnya diatur SIFKUMDA_HARI_TERTAHAN di .env.
 */
class IngatkanNaskahTertahan extends Command
{
    protected $signature = 'naskah:ingatkan';

    protected $description = 'Kirim pengingat kepada pemegang giliran naskah yang tertahan';

    public function handle(AlurNaskah $alur): int
    {
        $batas = LaporanNaskah::batasTertahan();
        $dikirim = 0;

        foreach (LaporanNaskah::baris() as $b) {
            $naskah = $b['naskah'];
            $hari = $b['lama_tahap'];

            // Penandatanganan berjalan di luar sistem dan ditunggu sesuai jadwal pejabat; tidak diingatkan.
            if ($naskah->tahap === T::Penandatanganan || $hari === null || $hari < $batas || $hari % $batas !== 0) {
                continue;
            }

            $penerima = $alur->pemegangGiliran($naskah);

            if ($penerima->isNotEmpty()) {
                Notification::send($penerima, new NaskahNotification(PesanNotifikasi::pengingat($naskah, $hari)));
                $dikirim++;
            }
        }

        $this->info("Pengingat dikirim untuk {$dikirim} naskah.");

        return self::SUCCESS;
    }
}
