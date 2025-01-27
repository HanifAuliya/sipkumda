<?php

namespace App\Livewire\NotificationModal\Admin;

use Livewire\Component;
use App\Models\RancanganProdukHukum;
use App\Models\User;
use Illuminate\Support\Carbon;
use App\Notifications\PersetujuanRancanganNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Gate;

class ModalPersetujuanAdmin extends Component
{
    public $rancangan;
    public $catatan;
    public $statusBerkas;

    protected $listeners = ['openAdminPersetujuanModal' => 'loadRancangan'];

    public function loadRancangan($slug)
    {
        $this->rancangan = RancanganProdukHukum::where('slug', $slug)->with('user', 'user.perangkatDaerah')->first();

        if (!$this->rancangan || Gate::denies('view', $this->rancangan)) {
            abort(403, 'Unauthorized access to rancangan');
        }
        $this->statusBerkas = $this->rancangan->status_berkas ?? 'Menunggu Persetujuan';
        $this->catatan = $this->rancangan->catatan_berkas ?? '';
    }

    public function updateStatus()
    {
        $this->validate([
            'statusBerkas' => 'required|in:Disetujui,Ditolak',
            'catatan' => 'required|string',
        ], [
            'statusBerkas.required' => 'Status harus dipilih.',
            'catatan.required' => 'Catatan wajib diisi.',
            'catatan.min' => 'Catatan minimal 5 karakter.',
            'catatan.max' => 'Catatan maksimal 255 karakter.',
        ]);

        if ($this->rancangan) {
            $this->rancangan->status_berkas = $this->statusBerkas;
            $this->rancangan->catatan_berkas = $this->catatan;

            // Set tanggal_berkas_disetujui jika status disetujui
            if ($this->statusBerkas === 'Disetujui') {
                $this->rancangan->tanggal_berkas_disetujui = Carbon::now();

                // Perbarui status_revisi di tabel Revisi
                $revisi = $this->rancangan->revisi()->first(); // Ambil revisi terkait
                if ($revisi) {
                    $revisi->update([
                        'status_revisi' => 'Menunggu Peneliti',
                    ]);
                }
                // Kirim notifikasi ke verifikator
                $verifikator = User::role('Verifikator')->get(); // Ambil semua user dengan role Verifikator
                Notification::send(
                    $verifikator, // Semua verifikator
                    new PersetujuanRancanganNotification([
                        'title' => "Berkas Rancangan nomor {$this->rancangan->no_rancangan} Disetujui, Silahkan Pilih Peneliti !",
                        'message' => "Berkas Rancangan dengan nomor {$this->rancangan->no_rancangan} telah berhasil disetujui. Harap segera memilih peneliti untuk melanjutkan proses berikutnya.",
                        'slug' => $this->rancangan->slug, // Slug untuk memuat modal detail
                        'type' => 'pilih_peneliti', // Tipe notifikasi untuk verifikator
                    ])
                );
            } else {
                $this->rancangan->tanggal_berkas_disetujui = null; // Reset jika status bukan "Disetujui"
            }

            $this->rancangan->save();

            // Kirim notifikasi ke user
            Notification::send(
                $this->rancangan->user, // User yang mengajukan rancangan
                new PersetujuanRancanganNotification([
                    'title' => "Berkas Rancangan Anda {$this->statusBerkas}",
                    'message' => $this->statusBerkas === 'Disetujui'
                        ? "Selamat! Berkas Rancangan Anda dengan nomor {$this->rancangan->no_rancangan} telah disetujui. Proses selanjutnya adalah penugasan Peneliti. Mohon menunggu pemelihan peneliti."
                        : "Mohon maaf, Berkas Rancangan Anda dengan nomor {$this->rancangan->no_rancangan} Di Tolak. Silakan periksa catatan yang diberikan untuk melakukan perbaikan dan ajukan kembali jika diperlukan.",
                    'slug' => $this->rancangan->slug, // Slug untuk memuat modal detail
                    'type' => $this->statusBerkas === 'Disetujui' ? 'persetujuan_diterima' : 'persetujuan_ditolak', // Tentukan tipe
                    // 'url' => route('user.rancangan.detail', $this->rancangan->id), // URL detail rancangan
                ])
            );

            // Emit notifikasi sukses ke pengguna
            $this->dispatch('refreshNotifications');
            // refresh halaman
            $this->dispatch('rancanganDiperbarui');


            $this->dispatch('swal:modalPersetujuan', [
                'type' => 'success',
                'message' => "Status Berkas Rancangan berhasil di {$this->statusBerkas}! "
            ]);
        }
        $this->reset();
    }

    // public function resetStatus()
    // {
    //     if ($this->rancangan) {
    //         $this->rancangan->status_berkas = 'Menunggu Persetujuan';
    //         $this->rancangan->catatan_berkas = null;
    //         $this->rancangan->tanggal_berkas_disetujui = null; // Reset tanggal jika status direset
    //         $this->rancangan->save();

    //         $this->statusBerkas = 'Menunggu Persetujuan';
    //         $this->catatan = null;

    //         // Kirim notifikasi ke user
    //         Notification::send(
    //             $this->rancangan->user, // User yang mengajukan rancangan
    //             new PersetujuanRancanganNotification([
    //                 'title' => "Rancangan Anda {$this->statusBerkas}",
    //                 'message' => "Rancangan Anda dengan nomor {$this->rancangan->no_rancangan} telah {$this->statusBerkas}. Silahkan Periksa !",
    //                 'slug' => $this->rancangan->slug, // Slug untuk memuat modal detail
    //                 'type' => 'persetujuan_menunggu', // Tipe notifikasi
    //                 // 'url' => route('user.rancangan.detail', $this->rancangan->id), // URL detail rancangan
    //             ])
    //         );

    //         // Emit notifikasi sukses ke pengguna
    //         $this->dispatch('refreshNotifications');

    //         $this->dispatch('swal:modalPersetujuan', [
    //             'type' => 'info',
    //             'message' => 'Status rancangan dikembalikan ke Menunggu Persetujuan.',
    //         ]);
    //     }
    // }

    public function resetForm()
    { // Atur ulang semua properti ke nilai default

        $this->resetErrorBag(); // Reset error validasi
        $this->resetValidation(); // Reset tampilan error validasi
    }

    public function render()
    {
        return view('livewire.notification-modal.admin.modal-persetujuan-admin');
    }
}
