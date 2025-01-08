<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class NotificationDropdown extends Component
{
    public $notifications;
    public $unreadCount;

    protected $listeners = ['refreshUnreadCount' => 'refreshUnreadCount'];

    public function mount()
    {
        $this->loadNotifications();
    }

    public function loadNotifications()
    {
        $userId = auth()->id();

        // Cache notifikasi untuk mengurangi query
        $this->notifications = Cache::remember("user_notifications_{$userId}", 60, function () use ($userId) {
            return auth()->user()->notifications()->latest()->get()->map(function ($notification) {
                $data = $notification->data;
                $data['type'] = $data['type'] ?? 'default';
                $notification->data = $data;
                return $notification;
            });
        });

        // Hitung ulang jumlah notifikasi belum dibaca
        $this->unreadCount = auth()->user()->unreadNotifications()->count();
    }

    public function markAsRead($notificationId)
    {
        $user = Auth::user();
        $notification = $user->notifications()->find($notificationId);

        if ($notification) {
            $notification->markAsRead();

            // Emit event berdasarkan tipe notifikasi
            $typeToEventMap = [
                'admin_persetujuan' => 'openAdminModal',
                'verifikator_detail' => 'openVerifikatorModal',
                'persetujuan_diterima' => 'openModalDetailUser',
                'persetujuan_ditolak' => 'openModalDetailUser',
                'persetujuan_menunggu' => 'openModalDetailUser',
            ];

            $type = $notification->data['type'] ?? null;
            $slug = $notification->data['slug'] ?? null;

            if ($type && isset($typeToEventMap[$type]) && $slug) {
                $this->dispatch($typeToEventMap[$type], $slug);
            }
        }

        $this->refreshUnreadCount();
    }

    public function refreshUnreadCount()
    {
        $this->unreadCount = auth()->user()->unreadNotifications()->count();
    }

    public function render()
    {
        return view('livewire.notification-dropdown');
    }
}
