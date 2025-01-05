<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RancanganBaruNotification extends Notification
{
    use Queueable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->data['title'] ?? 'Judul tidak tersedia',
            'message' => $this->data['message'] ?? 'Pesan tidak tersedia',
            'slug' => $this->data['slug'] ?? null,
            'type' => $this->data['type'] ?? null,
        ];
    }
    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->data['title']) // Subjek email
            ->line($this->data['message']) // Isi email
            ->action('Lihat Detail', $this->data['url']) // Tombol ke URL
            ->line('Terima kasih telah menggunakan aplikasi kami.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
