<?php

namespace App\Livewire\Peneliti\Fasilitasi;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\FasilitasiProdukHukum;

class RiwayatFasilitasi extends Component
{
    use WithPagination;
    public $search = ''; // Pencarian
    public $selectedFasilitasi;

    public function openDetailRiwayatFasilitasi($fasilitasiId)
    {
        $this->selectedFasilitasi = FasilitasiProdukHukum::with('rancangan')->findOrFail($fasilitasiId);

        // Dispatch event untuk membuka modal di JavaScript
        $this->dispatch('openModalDetailRiwayatFasilitasi');
    }

    public function resetDetail()
    {
        $this->selectedFasilitasi = null;
    }

    public function updatingSearch()
    {
        $this->resetPage(); // Reset halaman ke 1 saat pencarian berubah
    }

    public function render()
    {
        $riwayatFasilitasi = FasilitasiProdukHukum::where('status_berkas_fasilitasi', 'Disetujui',)
            ->whereHas('rancangan', function ($query) {
                $query->where('tentang', 'like', "%{$this->search}%")
                    ->orWhere('no_rancangan', 'like', "%{$this->search}%");
            })
            ->orderBy('tanggal_validasi_fasilitasi', 'desc') // Urutkan berdasarkan tanggal fasilitasi (terbaru di atas)
            ->paginate(10);

        return view('livewire.peneliti.fasilitasi.riwayat-fasilitasi', compact('riwayatFasilitasi'));
    }
}
