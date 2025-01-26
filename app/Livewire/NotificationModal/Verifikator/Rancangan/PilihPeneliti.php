<?php

namespace App\Livewire\NotificationModal\Verifikator\Rancangan;

use Livewire\Component;
use App\Models\RancanganProdukHukum;
use App\Models\User;

use Illuminate\Support\Facades\Notification;
use App\Notifications\PilihPenelitiNotification;

class PilihPeneliti extends Component
{
    public $selectedRancangan;
    public $selectedPeneliti;

    // Listener untuk membuka modal
    protected $listeners = ['openNotificationPilihPeneliti' => 'loadRancangan'];

    // Fungsi untuk memuat data rancangan berdasarkan slug
    public function loadRancangan($slug)
    {
        $this->selectedRancangan = RancanganProdukHukum::with(['user', 'revisi.peneliti'])->where('slug', $slug)->first();

        if ($this->selectedRancangan) {
            $this->dispatch('dataLoaded');
        }

        if (!$this->selectedRancangan || $this->selectedRancangan->status_berkas !== 'Disetujui') {
            $this->dispatch('closeNotificationPilihPeneliti');
            $this->dispatch('swal:denied', [
                'type' => 'info',
                'title' => 'Akses Ditolak',
                'message' => 'Rancangan ini belum disetujui. Tidak dapat memilih peneliti.',
            ]);
        }
    }

    public function assignPeneliti()
    {
        $this->validate([
            'selectedPeneliti' => 'required|exists:users,id',
        ]);

        $revisi = $this->selectedRancangan->revisi->first();
        $revisi->update([
            'id_user' => $this->selectedPeneliti,
            'status_revisi' => 'Proses Revisi',
            'tanggal_peneliti_ditunjuk' => now(),
        ]);

        // Emit notifikasi sukses ke pengguna
        $this->dispatch('refreshNotifications');

        // Kirim notifikasi ke user yang mengajukan rancangan
        Notification::send(
            $this->selectedRancangan->user,
            new PilihPenelitiNotification([
                'title' => "Peneliti Telah Ditugaskan untuk Rancangan Nomor {$this->selectedRancangan->no_rancangan}",
                'message' => "Rancangan Anda dengan nomor {$this->selectedRancangan->no_rancangan} telah disetujui, dan peneliti telah ditetapkan untuk melaksanakan revisi. Mohon menunggu proses revisi oleh peneliti yang ditugaskan.",
                'slug' => $this->selectedRancangan->slug,
                'type' => 'peneliti_dipilih',
            ])
        );

        $this->dispatch('closeModalPilihPeneliti');
        $this->dispatch('swal:notif', [
            'type' => 'success',
            'message' => 'Peneliti berhasil ditugaskan untuk revisi rancangan.',
        ]);
    }

    public function checkDataLoaded()
    {
        // Jika data belum tersedia dalam 5 detik
        if (!$this->selectedRancangan || $this->selectedRancangan->status_berkas !== 'Disetujui') {
            $this->dispatch('swal:denied', [
                'type' => 'error',
                'title' => 'Akses Ditolak',
                'message' => 'Rancangan ini belum disetujui. Tidak dapat melakukan tindakan ini.',
            ]);
            $this->reset(); // Reset data
        }
    }


    public function render()
    {
        return view('livewire.notification-modal.verifikator.rancangan.pilih-peneliti', [
            'listPeneliti' => User::role('peneliti')->get(),
        ]);
    }
}
