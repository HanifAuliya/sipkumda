<?php

namespace App\Livewire\Verifikator\Rancangan;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\RancanganProdukHukum;
use App\Notifications\ValidationResultNotification;


class ValidasiMenunggu extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 5;

    public $selectedRancangan;
    public $catatanValidasi;
    public $statusValidasi;

    public function openModalValidasiRancangan($idRancangan)
    {
        // Cari rancangan berdasarkan ID
        $this->selectedRancangan = RancanganProdukHukum::with(['user', 'perangkatDaerah', 'revisi'])
            ->findOrFail($idRancangan);

        // Reset form jika sebelumnya ada
        $this->resetFormValidasi();

        // Buka modal
        $this->dispatch('openModalValidasiRancangan');
    }

    public function submitValidasi()
    {
        $this->validate([
            'statusValidasi' => 'required|in:Diterima,Ditolak',
            'catatanValidasi' => 'required|string|max:1000',
        ]);

        $revisi = $this->selectedRancangan->revisi->first();

        if (!$revisi) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Kesalahan',
                'message' => 'Revisi tidak ditemukan.',
            ]);
            return;
        }

        // Update status revisi dan validasi
        $revisi->update([
            'status_validasi' => $this->statusValidasi,
            'catatan_validasi' => $this->catatanValidasi,
            'status_revisi' => 'Direvisi',
            'tanggal_revisi' => now(),
            'tanggal_validasi' => now(),
        ]);

        // Update status_rancangan menjadi "Disetujui"
        $rancangan = $revisi->rancangan;
        $rancangan->update([
            'status_rancangan' => 'Disetujui',
        ]);

        // // Kirim notifikasi ke user
        // $user = $this->selectedRancangan->user;
        // // Notification::send($user, new ValidationResultNotification([
        // //     'title' => "Validasi {$this->statusValidasi}",
        // //     'message' => $this->statusValidasi === 'Disetujui'
        // //         ? 'Rancangan Anda telah disetujui.'
        // //         : 'Rancangan Anda ditolak. Silakan perbaiki sesuai catatan.',
        // //     'slug' => $this->selectedRancangan->slug,
        // // ]));

        // Kirim pesan sukses
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Berhasil',
            'message' => "Rancangan berhasil divalidasi sebagai '{$this->statusValidasi}'.",
        ]);

        // Reset form dan tutup modal
        $this->resetFormValidasi();
        $this->dispatch('closeModalValidasiRancangan');
    }

    public function resetFormValidasi()
    {
        $this->reset(['catatanValidasi', 'statusValidasi']);
    }


    public function render()
    {
        $rancanganMenunggu = RancanganProdukHukum::with(['revisi',  'user.perangkatDaerah']) // Load relasi revisi dan user
            ->whereHas('revisi', function ($query) {
                $query->where('status_revisi', 'Proses Revisi')
                    ->where('status_validasi', 'Menunggu Validasi');
            })
            ->where(function ($query) {
                $query->where('tentang', 'like', "%{$this->search}%")
                    ->orWhere('no_rancangan', 'like', "%{$this->search}%");
            })
            ->orderBy('tanggal_pengajuan', 'desc') // Urutkan berdasarkan tanggal pengajuan
            ->paginate($this->perPage);

        return view('livewire.verifikator.rancangan.validasi-menunggu', compact('rancanganMenunggu'));
    }
}
