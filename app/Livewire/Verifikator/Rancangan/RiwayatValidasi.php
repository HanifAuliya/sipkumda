<?php

namespace App\Livewire\Verifikator\Rancangan;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use App\Models\RancanganProdukHukum;
use App\Models\Revisi;

use App\Notifications\ValidationResultNotification;
use Illuminate\Support\Facades\Storage;

class RiwayatValidasi extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $search = '';
    public $perPage = 5;
    public $selectedRancangan;

    public function loadDetailValidasi($id)
    {
        // Cari data rancangan berdasarkan ID
        $this->selectedRancangan = RancanganProdukHukum::with(['user.perangkatDaerah', 'revisi'])
            ->where('id_rancangan', $id)
            ->first();

        if (!$this->selectedRancangan) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Kesalahan',
                'message' => 'Detail rancangan tidak ditemukan.',
            ]);
            return;
        }

        // Emit event untuk membuka modal
        $this->dispatch('openModal', 'detailValidasiModal');
    }

    public function resetDetail()
    {
        $this->selectedRancangan = null;
    }

    protected $listeners = [
        'resetValidasiConfirmed' => 'resetValidasi',
    ];

    public function resetValidasi($id)
    {
        // Cari data revisi
        $revisi = Revisi::with('rancangan', 'peneliti')->findOrFail($id);

        if (!$revisi) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Kesalahan',
                'message' => 'Revisi tidak ditemukan.',
            ]);
            return;
        }

        // Hapus file lama jika ada
        if ($revisi->revisi_rancangan) {
            Storage::disk('local')->delete($revisi->revisi_rancangan);
        }
        if ($revisi->revisi_matrik) {
            Storage::disk('local')->delete($revisi->revisi_matrik);
        }

        // Reset data revisi
        $revisi->update([
            'status_validasi' => 'Menunggu Validasi',
            'catatan_validasi' => null,
            'status_revisi' => 'Proses Revisi',
            'tanggal_revisi' => null,
            'tanggal_validasi' => null,
        ]);

        // Update status_rancangan
        $rancangan = $revisi->rancangan;
        $rancangan->update([
            'status_rancangan' => 'Dalam Proses',
        ]);

        // Kirim notifikasi ke user
        $user = $rancangan->user;
        $user->notify(new ValidationResultNotification([
            'title' => 'Validasi Revisi Rancangan Anda telah direset!',
            'message' => 'Validasi Revisi Rancangan Anda untuk rancangan nomor ' . $rancangan->no_rancangan . ' telah direset. Silakan unggah ulang berkas revisi.',
            'slug' => $rancangan->slug,
            'type' => 'detail_validasi',
        ]));

        // Kirim notifikasi ke peneliti jika tersedia
        $peneliti = $revisi->peneliti; // Peneliti diambil melalui relasi di tabel revisi
        if ($peneliti) {
            $peneliti->notify(new ValidationResultNotification([
                'title' => 'Validasi Revisi Rancangan Telah Direset',
                'message' => 'Validasi Revisi Rancangan untuk rancangan nomor ' . $rancangan->no_rancangan . ' telah direset. Periksa dan lakukan upload ulang revisi.',
                'slug' => $rancangan->slug,
                'type' => 'reset_validasi',
            ]));
        }

        // Kirim pesan sukses
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'message' => 'Revisi berhasil direset dan status rancangan dikembalikan.',
        ]);

        // Refresh daftar revisi
        $this->resetPage(); // Reset ke halaman pertama
    }


    public function render()
    {
        // Ambil data rancangan dengan filter pencarian dan status validasi
        $riwayatValidasi = RancanganProdukHukum::with(['revisi', 'user.perangkatDaerah',])
            ->whereHas('revisi', function ($query) {
                $query->whereIn('status_revisi', ['Proses Revisi', 'Direvisi'])
                    ->whereIn('status_validasi', ['Diterima', 'Ditolak']);
            })
            ->where(function ($query) {
                $query->where('tentang', 'like', "%{$this->search}%")
                    ->orWhere('no_rancangan', 'like', "%{$this->search}%");
            })
            ->orderBy(
                Revisi::select('tanggal_revisi')
                    ->whereColumn('rancangan_produk_hukum.id_rancangan', 'revisi.id_rancangan')
                    ->latest('tanggal_revisi')
                    ->take(1), // Ambil tanggal revisi terbaru
                'desc'
            )
            ->paginate($this->perPage);

        return view('livewire.verifikator.rancangan.riwayat-validasi', compact('riwayatValidasi'));
    }
}
