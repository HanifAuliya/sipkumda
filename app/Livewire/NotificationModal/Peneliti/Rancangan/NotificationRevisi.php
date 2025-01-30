<?php

namespace App\Livewire\NotificationModal\Peneliti\Rancangan;

use App\Models\Revisi;
use App\Models\User;
use App\Models\RancanganProdukHukum;
use App\Notifications\RevisiValidationNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;
use Livewire\WithFileUploads;

use Illuminate\Support\Facades\Log;


class NotificationRevisi extends Component
{
    use WithFileUploads;

    public $selectedRevisiId;
    public $selectedRevisi;
    public $revisiRancangan;
    public $revisiMatrik;
    public $catatanRevisi;

    protected $rules = [
        'revisiRancangan' => 'required|mimes:pdf|max:2048',
        'revisiMatrik' => 'required|mimes:pdf|max:2048',
        'catatanRevisi' => 'required|string|max:1000',
    ];

    protected $listeners = [
        'openModalNotificationRevisi' => 'NotificationRevisi',
    ];

    /**
     * Handle event untuk membuka modal dengan slug.
     */
    public function NotificationRevisi($slug)
    {
        $revisi = Revisi::whereHas('rancangan', function ($query) use ($slug) {
            $query->where('slug', $slug);
        })->with('rancangan')->latest()->first();

        if (!$revisi || !$revisi->rancangan) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Kesalahan',
                'message' => 'Revisi atau rancangan tidak ditemukan berdasarkan slug.',
            ]);
            return;
        }

        // Simpan data untuk modal
        $this->selectedRevisiId = $revisi->id_revisi;
        $this->selectedRevisi = $revisi;
    }


    /**
     * Proses upload revisi.
     */
    public function notifUploadRevisi()
    {
        $this->validate();

        $revisi = Revisi::find($this->selectedRevisiId);

        if (!$revisi) {
            $this->dispatch('swal:notif', [
                'type' => 'error',
                'title' => 'Kesalahan',
                'message' => 'Data revisi tidak ditemukan.',
            ]);
            return;
        }

        // Simpan file ke storage private
        $revisiRancanganPath = $this->revisiRancangan->store('revisi/rancangan', 'local');
        $revisiMatrikPath = $this->revisiMatrik->store('revisi/matrik', 'local');

        // Update data revisi di database
        $revisi->update([
            'revisi_rancangan' => $revisiRancanganPath,
            'revisi_matrik' => $revisiMatrikPath,
            'catatan_revisi' => $this->catatanRevisi,
            'status_validasi' => 'Menunggu Validasi',
            'tanggal_revisi' => now(),
        ]);

        // Kirim notifikasi ke user yang mengajukan rancangan
        $user = $revisi->rancangan->user;

        $user->notify(new RevisiValidationNotification([
            'title' => 'Revisi Dikirim',
            'message' => 'Revisi untuk rancangan Anda telah berhasil diunggah dan menunggu validasi.',
            'slug' => $revisi->rancangan->slug,
            'type' => 'detail_validasi',
        ]));

        // Kirim notifikasi ke semua verifikator
        $verifikators = User::role('verifikator')->get();

        Notification::send($verifikators, new RevisiValidationNotification([
            'title' => 'Revisi Baru Menunggu Validasi',
            'message' => 'Ada revisi baru yang menunggu validasi.',
            'slug' => $revisi->rancangan->slug,
            'type' => 'validasi_revisi',
        ]));

        // Emit notifikasi sukses ke pengguna
        $this->dispatch('refreshNotifications');

        $this->dispatch('swal:notif', [
            'type' => 'success',
            'title' => 'Berhasil',
            'message' => 'Revisi berhasil diunggah dan dikirim untuk validasi.',
        ]);

        // Emit event untuk menutup modal
        $this->dispatch('closeModalNotificationRevisi');

        $this->dispatch('refreshPage');
    }

    public function resetForm()
    {
        $this->reset(['revisiRancangan', 'revisiMatrik', 'catatanRevisi', 'selectedRevisiId', 'selectedRevisi']);
        // Hapus semua error validasi
        $this->resetValidation();
    }

    public function render()
    {
        if ($this->selectedRevisiId) {
            $this->selectedRevisi = Revisi::with('rancangan')
                ->find($this->selectedRevisiId);
        }

        return view('livewire.notification-modal.peneliti.rancangan.notificationrevisi', [
            'selectedRevisi' => $this->selectedRevisi,
        ]);
    }
}
