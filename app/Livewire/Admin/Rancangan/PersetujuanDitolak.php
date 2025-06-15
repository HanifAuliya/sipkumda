<?php

namespace App\Livewire\Admin\Rancangan;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use App\Models\User;
use App\Models\RancanganProdukHukum;
use Illuminate\Support\Carbon;

use App\Notifications\PersetujuanRancanganNotification;
use Illuminate\Support\Facades\Notification;

class PersetujuanDitolak extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $search = '';
    public $selectedRancangan;
    public $perPage = 5; // Default perPage

    public $catatan;
    public $statusBerkas;
    public $selectedRancanganId;


    public function openModal($id)
    {
        $this->selectedRancangan = RancanganProdukHukum::with(['user', 'perangkatDaerah'])->find($id);
        $this->dispatch('openModal', 'modalDetailPersetujuan');
    }

    public $listeners = [
        'setujuiConfirmed' => 'setujuiBerkas',
    ];

    public function setujuiBerkas($id, $catatan)
    {
        $this->selectedRancangan = RancanganProdukHukum::with(['user', 'perangkatDaerah'])->find($id);

        if (!$this->selectedRancangan) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Kesalahan',
                'message' => 'Data rancangan tidak ditemukan!',
            ]);
            return;
        }

        // Update status berkas dan catatan
        $this->selectedRancangan->update([
            'status_berkas' => 'Disetujui',
            'tanggal_berkas_disetujui' => Carbon::now(),
            'catatan_berkas' => $catatan,
        ]);

        // Update status_revisi di tabel Revisi menjadi 'Menunggu Peneliti'
        $revisi = $this->selectedRancangan->revisi()->first(); // Ambil revisi terkait

        $revisi->update([
            'status_revisi' => 'Menunggu Peneliti',
        ]);

        // Kirim notifikasi ke verifikator
        $verifikator = User::role('Verifikator')->get(); // Ambil semua user dengan role Verifikator
        Notification::send(
            $verifikator, // Semua verifikator
            new PersetujuanRancanganNotification([
                'title' => "Berkas Rancangan nomor {$this->selectedRancangan->no_rancangan} Disetujui, Silahkan Pilih Peneliti",
                'message' => "Berkas Rancangan dengan nomor {$this->selectedRancangan->no_rancangan} telah berhasil disetujui. Harap segera memilih peneliti untuk melanjutkan proses berikutnya.",
                'slug' => $this->selectedRancangan->slug, // Slug untuk memuat modal detail
                'type' => 'pilih_peneliti', // Tipe notifikasi untuk verifikator
            ])


        );

        // Kirim notifikasi ke user
        Notification::send(
            $this->selectedRancangan->user, // User yang mengajukan rancangan
            new PersetujuanRancanganNotification([
                'title' => "Berkas Rancangan Anda {$this->statusBerkas}",
                'message' => "Selamat! Berkas Rancangan Anda dengan nomor {$this->selectedRancangan->no_rancangan} telah disetujui. Proses selanjutnya adalah penugasan Peneliti. Mohon menunggu pemelihan peneliti.",
                'slug' => $this->selectedRancangan->slug, // Slug untuk memuat modal detail
                'type' => $this->statusBerkas === 'Disetujui' ? 'persetujuan_diterima' : 'persetujuan_ditolak', // Tentukan tipe
                // 'url' => route('user.rancangan.detail', $this->rancangan->id), // URL detail rancangan
            ])
        );

        // Emit notifikasi sukses ke pengguna
        $this->dispatch('refreshNotifications');

        $this->dispatch('swal:toast', [
            'type' => 'success',
            'message' => 'Status rancangan berhasil diperbarui!',
        ]);
    }

    public function setSelectedRancangan($id)
    {
        $this->selectedRancanganId = $id; // Simpan ID rancangan yang akan di-reset
    }
    public function confirmResetStatus()
    {
        try {
            // Validasi jika rancangan tidak ditemukan
            $rancangan = RancanganProdukHukum::findOrFail($this->selectedRancanganId);

            // Reset status rancangan
            $rancangan->update([
                'status_berkas' => 'Menunggu Persetujuan',
                'catatan_berkas' => null,
                'tanggal_berkas_disetujui' => null, // Reset tanggal jika status direset
            ]);

            // Reset status revisi terkait rancangan
            $revisi = $rancangan->revisi()->first();
            if ($revisi) {
                $revisi->update([
                    'status_revisi' => 'Belum Tahap Revisi', // Reset status revisi
                ]);
            }

            // Perbarui properti lokal
            $this->statusBerkas = 'Menunggu Persetujuan';
            $this->catatan = '';


            // Kirim notifikasi ke user
            Notification::send(
                $rancangan->user, // User yang mengajukan rancangan
                new PersetujuanRancanganNotification([
                    'title' => "Rancangan Anda {$this->statusBerkas}",
                    'message' => "Rancangan Anda dengan nomor {$rancangan->no_rancangan} telah diubah menjadi {$this->statusBerkas}.",
                    'slug' => $rancangan->slug, // Slug untuk memuat modal detail
                    'type' => 'persetujuan_menunggu', // Tipe notifikasi
                ])
            );

            // Emit notifikasi sukses ke pengguna
            $this->dispatch('refreshNotifications');

            $this->reset();

            // SweetAlert sukses
            $this->dispatch('swal:reset', [
                'type' => 'success',
                'title' => 'Status Berhasil Direset',
                'message' => 'Status rancangan telah direset ke Menunggu Persetujuan.',
            ]);
            $this->dispatch('colse', 'resetStatusModal');
        } catch (\Exception $e) {
            // SweetAlert error jika terjadi kesalahan
            $this->dispatch('swal:reset', [
                'type' => 'error',
                'title' => 'Gagal Mereset Status',
                'message' => 'Terjadi kesalahan saat mereset status rancangan. Silakan coba lagi.',
            ]);
        }
    }


    public function render()
    {
        $berkasRancanganDitolak = RancanganProdukHukum::where('status_berkas', 'Ditolak')
            ->where('status_rancangan', 'Dalam Proses')
            ->where(function ($query) {
                $query->where('no_rancangan', 'like', "%{$this->search}%")
                    ->orWhere('tentang', 'like', "%{$this->search}%");
            })
            ->orderBy('tanggal_pengajuan', 'desc') // Urutkan berdasarkan tangal pengakuan
            ->paginate($this->perPage);

        return view('livewire.admin.rancangan.persetujuan-ditolak', compact('berkasRancanganDitolak'));
    }
}
