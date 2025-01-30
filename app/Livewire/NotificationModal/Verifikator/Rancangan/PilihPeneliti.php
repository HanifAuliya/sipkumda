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
        $peneliti = User::find($this->selectedPeneliti);
        Notification::send($peneliti, new PilihPenelitiNotification([
            'title' => "Penugasan Baru rancangan {$this->selectedRancangan->no_rancangan}",
            'message' => "Anda telah ditugaskan untuk meneliti rancangan: {$this->selectedRancangan->tentang} Segera periksa dan lakukan revisi !",
            'slug' => $this->selectedRancangan->slug,
            'type' => 'upload_revisi',
        ]));

        $this->dispatch('closeModalPilihPeneliti');

        $this->dispatch('refreshData');
        // refresh halaman
        $this->dispatch('refresh');

        $this->dispatch('swal:notif', [
            'type' => 'success',
            'message' => 'Peneliti berhasil ditugaskan untuk revisi rancangan.',
        ]);
    }

    public function refreshData()
    {
        $this->reset(['selectedRancangan']);
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
