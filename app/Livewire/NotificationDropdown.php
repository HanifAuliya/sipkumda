<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class NotificationDropdown extends Component
{
    /** Jumlah notifikasi terbaru yang ditampilkan di dropdown. */
    private const BATAS = 20;

    // Tidak ada state publik: daftar diambil ulang saat render sehingga snapshot Livewire tetap kecil
    // walau polling berjalan untuk banyak pengguna sekaligus.
    protected $listeners = ['refreshNotifications' => '$refresh'];

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);
    }


    public function markAsRead($notificationId)
    {
        $user = Auth::user();
        $notification = $user->notifications()->find($notificationId);

        if ($notification) {
            $notification->markAsRead();

            // Emit event berdasarkan tipe notifikasi
            $typeToEventMap = [
                'admin_persetujuan' => 'openModalDetailRancangan',
                'verifikator_detail' => 'openModalDetailRancangan',

                'persetujuan_diterima' => 'openModalDetailRancangan', // Tambahan untuk User
                'persetujuan_ditolak' => 'openModalDetailRancangan', // Tambahan untuk User
                'persetujuan_menunggu' => 'openModalDetailRancangan', // Tambahan untuk User
                'pilih_peneliti' => 'openModalDetailRancangan',

                'peneliti_dibatalkan' => 'openModalDetailRancangan',
                'peneliti_dipilih' => 'openModalDetailRancangan',
                'upload_revisi' => 'openModalDetailRancangan',
                'revisi_dikirim' => 'openModalDetailRancangan',
                'reset_validasi' => 'openModalDetailRancangan',

                'detail_validasi' => 'openModalDetailRancangan',
                'validasi_revisi' => 'openModalDetailRancangan',
                'revisi_ulang' => 'openModalDetailRancangan',
                'rancangan_selesai' => 'openModalDetailRancangan',

                'uploadUlang_revisi' => 'openModalDetailRancangan',
            ];

            $type = $notification->data['type'] ?? null;
            $slug = $notification->data['slug'] ?? null;

            if ($type && isset($typeToEventMap[$type]) && $slug) {
                $this->dispatch($typeToEventMap[$type], $slug);
            }
        }
    }


    public function render()
    {
        $user = Auth::user();

        return view('livewire.notification-dropdown', [
            'notifications' => $user->notifications()->latest()->limit(self::BATAS)->get(),
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }
}
