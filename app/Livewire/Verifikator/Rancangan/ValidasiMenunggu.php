<?php

namespace App\Livewire\Verifikator\Rancangan;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use App\Models\RancanganProdukHukum;

use App\Notifications\ValidationResultNotification;


class ValidasiMenunggu extends Component
{
    use WithPagination, WithoutUrlPagination;


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

    public function validasiRevisi()
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
            'status_revisi' => $this->statusValidasi === 'Diterima' ? 'Direvisi' : 'Proses Revisi',
            'tanggal_revisi' => now(),
            'tanggal_validasi' => now(),
        ]);

        // Jika validasi diterima, update status rancangan menjadi "Disetujui"
        if ($this->statusValidasi === 'Diterima') {
            $rancangan = $revisi->rancangan;
            $rancangan->update([
                'status_rancangan' => 'Disetujui',
            ]);

            // Kirim notifikasi ke user pengaju rancangan
            $user = $rancangan->user;
            if ($user) {
                $user->notify(new ValidationResultNotification([
                    'title' => 'Rancangan Anda  dengan nomor ' . $rancangan->no_rancangan . ' Telah Disetujui',
                    'message' => "Keseluruhan rancangan Anda telah disetujui. Silakan cek di menu 'Rancangan Saya' untuk melihat file revisi yang telah selesai. Ajukan fasilitasi menggunakan catatan revisi dan validasi yang ada.",
                    'type' => 'rancangan_selesai',
                    'slug' => $rancangan->slug,
                ]));
            }
        }

        // Jika validasi ditolak, kirim notifikasi ke peneliti untuk melakukan upload ulang
        if ($this->statusValidasi === 'Ditolak') {
            $peneliti = $revisi->peneliti;
            if ($peneliti) {
                $peneliti->notify(new ValidationResultNotification([
                    'title' => "Revisi Rancangan No '{$revisi->rancangan->no_rancangan} 'Anda Ditolak",
                    'message' => "Revisi untuk rancangan '{$revisi->rancangan->tentang}' telah ditolak. Silakan unggah ulang revisi berdasarkan catatan validasi yang diberikan.",
                    'slug' => $revisi->rancangan->slug,
                    'type' => 'revisi_ulang',
                ]));
            }
        }

        // Kirim pesan sukses
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'message' => "Rancangan berhasil divalidasi sebagai '{$this->statusValidasi}'.",
        ]);

        // Reset form dan tutup modal
        $this->resetFormValidasi();
        $this->dispatch('closeModalValidasiRancangan');
    }

    public function resetDetail()
    {
        $this->selectedRancangan = null;
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
