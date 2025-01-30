<?php

namespace App\Livewire\Perangkatdaerah\Rancangan;

use App\Models\RancanganProdukHukum;
use Livewire\Component;
use Livewire\WithPagination;

class RiwayatPengajuan extends Component
{
    use WithPagination;

    public $search = ''; // Untuk pencarian
    public $perPage = 3; // Default perPage adalah 3 data
    public $page = 1; // Halaman saat ini

    protected $queryString = ['search', 'perPage', 'page']; // Untuk mempertahankan state URL
    public $selectedRancangan;

    protected $listeners = ['rancanganDiperbarui' => 'refreshRancangan'];

    public function refreshRancangan()
    {
        $this->resetPage(); // Reset ke halaman pertama
    }

    public function loadDokumenRevisi($id)
    {
        // Cari rancangan berdasarkan ID
        $rancangan = RancanganProdukHukum::with(['revisi'])->find($id);

        if (!$rancangan) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Kesalahan',
                'message' => 'Rancangan tidak ditemukan.',
            ]);
            return;
        }

        // Set data rancangan dan revisi ke properti untuk digunakan di modal
        $this->selectedRancangan = $rancangan;

        // Emit event untuk membuka modal
        $this->dispatch('openModal', 'berkasModal');
    }

    public function resetDetail()
    {
        $this->selectedRancangan = null;
    }

    public function updatingSearch()
    {
        $this->page = 1; // Reset ke halaman pertama jika pencarian diubah
    }

    public function updatingPerPage()
    {
        $this->page = 1; // Reset ke halaman pertama jika perPage diubah
    }

    public function render()
    {
        $query = RancanganProdukHukum::with(['user', 'perangkatDaerah', 'revisi'])
            ->where('id_user', auth()->id())
            ->where('status_rancangan', 'Disetujui')
            ->orderBy('tanggal_rancangan_disetujui', 'desc'); // Urutkan berdasarkan tanggal_rancangan_disetujui terbaru

        // Filter pencarian
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('no_rancangan', 'like', '%' . $this->search . '%')
                    ->orWhere('tentang', 'like', '%' . $this->search . '%');
            });
        }

        // Ambil data berdasarkan jumlah perPage
        $riwayat = $query->paginate($this->perPage, ['*'], 'page', $this->page);

        return view('livewire.perangkatdaerah.rancangan.riwayat-pengajuan', compact('riwayat'));
    }
}
