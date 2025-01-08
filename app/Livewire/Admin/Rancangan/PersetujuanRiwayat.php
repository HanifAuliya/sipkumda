<?php

namespace App\Livewire\Admin\Rancangan;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\RancanganProdukHukum;

use App\Notifications\PersetujuanRancanganNotification;
use Illuminate\Support\Facades\Notification;

class PersetujuanRiwayat extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedRancangan;
    public $perPage = 5; // Default perPage

    public $catatan;
    public $statusBerkas;

    public function openModal($id)
    {
        $this->selectedRancangan = RancanganProdukHukum::with(['user', 'perangkatDaerah'])->find($id);
        $this->dispatch('openModalPersetujuan');
    }

    public function resetStatus($id)
    {
        $rancangan = RancanganProdukHukum::findOrFail($id);
        $rancangan->update([
            'status_berkas' => 'Menunggu Persetujuan',
            'catatan_berkas' => null,
            'tanggal_berkas_disetujui' => null, // Reset tanggal jika status direset
        ]);

        $this->statusBerkas = 'Menunggu Persetujuan';
        $this->catatan = '';

        // Kirim notifikasi ke user
        Notification::send(
            $this->selectedRancangan->user, // User yang mengajukan rancangan
            new PersetujuanRancanganNotification([
                'title' => "Rancangan Anda {$this->statusBerkas}",
                'message' => "Rancangan Anda dengan nomor {$this->selectedRancangan->no_rancangan} telah {$this->statusBerkas}.",
                'slug' => $this->selectedRancangan->slug, // Slug untuk memuat modal detail
                'type' => 'persetujuan_menunggu', // Tipe notifikasi
                // 'url' => route('user.rancangan.detail', $this->rancangan->id), // URL detail rancangan
            ])
        );

        // Emit notifikasi sukses ke pengguna
        $this->dispatch('refreshNotifications');

        $this->dispatch('swal:modal', [
            'type' => 'info',
            'title' => 'Status Berhasil Direset',
            'message' => 'Status rancangan telah direset ke Menunggu Persetujuan.',
        ]);
    }

    public function render()
    {
        $riwayatRancangan = RancanganProdukHukum::where('status_berkas', 'Disetujui')
            ->where(function ($query) {
                $query->where('no_rancangan', 'like', "%{$this->search}%")
                    ->orWhere('tentang', 'like', "%{$this->search}%");
            })
            ->orderBy('tanggal_berkas_disetujui', 'desc') // Urutkan berdasarkan tanggal_berkas_disetujui terbaru
            ->paginate($this->perPage);

        return view('livewire.admin.rancangan.persetujuan-riwayat', compact('riwayatRancangan'));
    }
}
