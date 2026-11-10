<?php

namespace App\Notifications\Channels;

use App\Jobs\KirimAntreanWa;
use App\Models\PesanWa;
use App\Models\User;
use App\Notifications\NaskahNotification;
use App\Support\Wa\AntreanWa;
use App\Support\Wa\NomorWa;

/**
 * Hanya memasukkan pesan ke antrean; pengirimannya di KirimAntreanWa / wa:kirim,
 * jadi halaman tidak menunggu WhatsApp dan pesan tidak hilang saat sambungan putus.
 */
class WhatsAppChannel
{
    public function send(object $notifiable, NaskahNotification $notification): void
    {
        $data = $notification->data();

        if (! $notifiable instanceof User || ! AntreanWa::perluDikirim($notifiable, $data)) {
            return;
        }

        PesanWa::create([
            'user_id' => $notifiable->id,
            'nomor' => NomorWa::normal($notifiable->no_wa),
            'notification_id' => $notification->id,
            'isi' => AntreanWa::susunIsi($data, $notifiable),
        ]);

        KirimAntreanWa::dispatch()->afterCommit();
    }
}
