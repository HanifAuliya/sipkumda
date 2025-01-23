<?php

namespace App\Livewire\Verifikator\Rancangan;

use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;
use App\Models\RancanganProdukHukum;
use App\Models\User;
use App\Models\Revisi;
use Carbon\Carbon;

class PenelitiDitugaskan extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $search = '';
    public $perPage = 5;

    public $selectedRevisi; // Untuk menyimpan data revisi yang dipilih
    public $revisiId; // ID revisi yang dipilih

    protected $listeners = ['refreshPage' => '$refresh'];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function openModal($idRevisi)
    {
        // Cari revisi dengan eager loading
        $this->selectedRevisi = Revisi::with([
            'rancangan.user', // Jika relasi rancangan memiliki user
            'rancangan.perangkatDaerah', // Jika rancangan memiliki perangkat daerah
            'peneliti', // Peneliti yang terkait dengan revisi
        ])->findOrFail($idRevisi);

        // Dispatch untuk membuka modal
        $this->dispatch('openModalPilihPeneliti');
    }



    public function resetPeneliti($id)
    {
        $revisi = Revisi::where('id_rancangan', $id)->first();

        if ($revisi) {
            $revisi->update([
                'id_user' => null,
                'status_revisi' => 'Menunggu Peneliti',
                'tanggal_peneliti_ditunjuk' => null,
            ]);

            $this->dispatch('swal:modal', [
                'type' => 'success',
                'title' => 'Peneliti Direset',
                'message' => 'Peneliti berhasil direset dan status revisi kembali ke "Menunggu Peneliti".',
            ]);
        } else {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Revisi tidak ditemukan.',
            ]);
        }

        $this->dispatch('refreshPage');
    }

    public function render()
    {
        // Query untuk rancangan dengan status revisi "Menunggu Revisi"
        $rancangan = RancanganProdukHukum::where('status_berkas', 'Disetujui')
            ->where('status_rancangan', 'Dalam Proses')
            ->whereHas('revisi', function ($query) {
                $query->whereIn('status_revisi', [
                    'Proses Revisi',
                    'Menunggu Validasi',
                    'Direvisi'
                ]);
            })
            ->with(['revisi.peneliti']) // Eager loading revisi dan peneliti
            ->where(function ($query) {
                $query->where('tentang', 'like', "%{$this->search}%")
                    ->orWhere('no_rancangan', 'like', "%{$this->search}%");
            })
            ->orderBy('tanggal_berkas_disetujui', 'desc') // Urutkan berdasarkan tangal pengakuan
            ->paginate($this->perPage);

        return view('livewire.verifikator.rancangan.peneliti-ditugaskan', compact('rancangan'));
    }
}
