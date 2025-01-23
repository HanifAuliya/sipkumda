<?php

namespace App\Livewire\Peneliti\Rancangan;

use App\Models\RancanganProdukHukum;
use Livewire\Component;
use App\Models\Revisi;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RiwayatRevisi extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $search = '';
    public $perPage = 5;
    public $selectedRevisi;

    public $listeners = [
        'resetRevisiConfirmed' => 'resetRevisi',
    ];

    public function loadDetailRevisi($id)
    {
        $this->selectedRevisi = Revisi::with('rancangan.user')->find($id);
        if (!$this->selectedRevisi) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Kesalahan',
                'message' => 'Detail revisi tidak ditemukan.',
            ]);
            return;
        }

        // Emit untuk membuka modal
        $this->dispatch('openDetailRevisiModal');
    }


    public function resetRevisi($id)
    {
        $revisi = Revisi::findOrFail($id);

        // Hapus file dari storage jika ada
        if ($revisi->revisi_rancangan) {
            Storage::disk('public')->delete($revisi->revisi_rancangan);
        }

        if ($revisi->revisi_matrik) {
            Storage::disk('public')->delete($revisi->revisi_matrik);
        }

        // Reset data revisi
        $revisi->update([
            'revisi_rancangan' => null,
            'revisi_matrik' => null,
            'catatan_revisi' => null,
            'tanggal_revisi' => null,
            'status_validasi' => 'Belum Tahap Validasi',
        ]);

        $this->dispatch('swal:reset', [
            'type' => 'success',
            'title' => 'Revisi Direset',
            'message' => 'Revisi berhasil direset dan file dihapus dari storage.',
        ]);
    }

    public function render()
    {

        // Dapatkan ID user yang sedang login
        $penelitiId = Auth::id();

        $revisi = RancanganProdukHukum::with(['revisi.peneliti', 'user'])
            ->whereHas('revisi', function ($query) use ($penelitiId) {
                $query->where('status_validasi', 'Menunggu Validasi')
                    ->where('status_revisi', 'Proses Revisi')
                    ->where('status_rancangan', 'Dalam Proses')
                    ->where('id_user', $penelitiId); // Filter berdasarkan peneliti yang sedang login
            })
            ->where(function ($query) {
                $query->where('id_rancangan', 'like', "%{$this->search}%")
                    ->orWhere('tentang', 'like', "%{$this->search}%");
            })
            ->orderByDesc(
                Revisi::select('tanggal_revisi')
                    ->whereColumn('rancangan_produk_hukum.id_rancangan', 'revisi.id_rancangan')
                    ->latest('tanggal_revisi')
            )
            ->paginate($this->perPage);


        return view('livewire.peneliti.rancangan.riwayat-revisi', compact('revisi'));
    }
}
