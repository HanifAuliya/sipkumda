<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class NotificationDropdown extends Component
{
    public $notifications;
    public $unreadCount;

    protected $listeners = ['refreshNotifications' => 'loadNotifications'];


    public function loadNotifications()
    {
        $user = auth()->user();

        $this->notifications = $user->notifications()->latest()->get()->map(function ($notification) {
            $data = $notification->data; // Ambil salinan data
            $data['type'] = $data['type'] ?? 'default'; // Set default type jika tidak ada
            $notification->data = $data; // Tetapkan kembali data yang telah dimodifikasi
            return $notification;
        });

        $this->unreadCount = $user->unreadNotifications()->count();
    }


    public function markAsRead($notificationId)
    {
        $user = Auth::user();
        $notification = $user->notifications()->find($notificationId);

        if ($notification) {
            $notification->markAsRead();

            // Emit event berdasarkan tipe notifikasi
            $typeToEventMap = [
                'admin_persetujuan' => 'openAdminPersetujuanModal',
                'verifikator_detail' => 'openModalDetailRancangan',

                'persetujuan_diterima' => 'openModalDetailRancangan', // Tambahan untuk User
                'persetujuan_ditolak' => 'openModalDetailRancangan', // Tambahan untuk User
                'persetujuan_menunggu' => 'openModalDetailRancangan', // Tambahan untuk User
                'pilih_peneliti' => 'openNotificationPilihPeneliti',

                'peneliti_dibatalkan' => 'openModalDetailRancangan',
                'peneliti_dipilih' => 'openModalDetailRancangan',
                'upload_revisi' => 'openModalNotificationRevisi',

                'detail_validasi' => 'openModalDetailRancangan',
                'validasi_revisi' => 'openModalDetailRancangan',

            ];

            $type = $notification->data['type'] ?? null;
            $slug = $notification->data['slug'] ?? null;

            if ($type && isset($typeToEventMap[$type]) && $slug) {
                $this->dispatch($typeToEventMap[$type], $slug);
            }
        }

        $this->loadNotifications();
    }


    public function render()
    {
        $this->loadNotifications();
        return view('livewire.notification-dropdown');
    }
}
