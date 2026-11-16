<?php

namespace App\Notifications;

use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Notifikasi alur naskah v2; isinya disusun oleh App\Support\PesanNotifikasi. */
class NaskahNotification extends Notification
{
    use Queueable;

    public function __construct(private array $data)
    {
    }

    /** Lonceng selalu; WhatsApp memutuskan sendiri apakah penerima perlu dikirimi (lihat AntreanWa). */
    public function via(object $notifiable): array
    {
        return ['database', WhatsAppChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [...$this->data, 'type' => 'naskah'];
    }

    public function data(): array
    {
        return $this->data;
    }
}
