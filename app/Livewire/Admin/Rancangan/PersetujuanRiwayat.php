<?php

namespace App\Livewire\Admin\Rancangan;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use App\Models\RancanganProdukHukum;

use App\Notifications\PersetujuanRancanganNotification;
use Illuminate\Support\Facades\Notification;

class PersetujuanRiwayat extends Component
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
        $this->dispatch('openModalPersetujuan');
    }

    public function setSelectedRancangan($id)
    {
        $this->selectedRancanganId = $id;
        $this->selectedRancangan = RancanganProdukHukum::find($id);
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
                    'revisi_rancangan' => null,
                    'revisi_matrik' => null,
                    'id_user' => null,
                    'status_revisi' => 'Belum Tahap Revisi', // Status awal
                    'status_validasi' => 'Belum Tahap Validasi', // Status awal validasi
                    'catatan_revisi' => null,
                    'catatan_validasi' => null,
                    'tanggal_peneliti_ditunjuk' => null,
                    'tanggal_revisi' => null,
                    'tanggal_validasi' => null,
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
        $riwayatRancangan = RancanganProdukHukum::where('status_berkas', 'Disetujui')
            ->where('status_rancangan', 'Dalam Proses')
            ->where(function ($query) {
                $query->where('no_rancangan', 'like', "%{$this->search}%")
                    ->orWhere('tentang', 'like', "%{$this->search}%");
            })
            ->orderBy('tanggal_berkas_disetujui', 'desc') // Urutkan berdasarkan tanggal_berkas_disetujui terbaru
            ->paginate($this->perPage);

        return view('livewire.admin.rancangan.persetujuan-riwayat', compact('riwayatRancangan'));
    }
}
