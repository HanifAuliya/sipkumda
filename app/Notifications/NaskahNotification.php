<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Notifikasi alur naskah v2; isinya disusun oleh App\Support\PesanNotifikasi. */
class NaskahNotification extends Notification
{
    use Queueable;

    public function __construct(private array $data)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [...$this->data, 'type' => 'naskah'];
    }
}
