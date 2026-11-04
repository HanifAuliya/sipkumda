<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

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
        return [
            'title' => $this->data['title'],
            'message' => $this->data['message'],
            'slug' => $this->data['slug'] ?? null,
            'type' => 'naskah',
        ];
    }
}
