<?php

namespace App\Livewire\PerangkatDaerah\Fasilitasi;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\FasilitasiProdukHukum;
use Livewire\WithoutUrlPagination;


class FasilitasiBerlangsung extends Component
{
    use WithPagination, WithoutUrlPagination;


    public $search = ''; // Pencarian
    public $perPage = 10; // Default jumlah item per halaman
    public $selectedFasilitasi = null;


    protected $listeners = ['refresh' => 'refreshFasilitasi'];


    public function openDetailFasilitasi($fasilitasiId)
    {
        $this->selectedFasilitasi = FasilitasiProdukHukum::with('rancangan')->findOrFail($fasilitasiId);

        // Dispatch event untuk membuka modal di JavaScript
        $this->dispatch('openModalDetailFasilitasi');
    }

    public function resetDetail()
    {
        $this->selectedFasilitasi = null;
    }
    public function updatingSearch()
    {
        // Reset halaman ke halaman pertama saat pencarian berubah
        $this->resetPage();
    }
    public function refreshFasilitasi()
    {
        $this->resetPage(); // Reset ke halaman pertama
    }

    public function render()
    {
        $fasilitasiBerlangsung = FasilitasiProdukHukum::with('rancangan')
            ->whereIn('status_berkas_fasilitasi', ['Menunggu Persetujuan', 'Diterima', 'Ditolak']) // Menampilkan hanya status ini
            ->where('status_validasi_fasilitasi', 'Belum Tahap Validasi')
            ->whereHas('rancangan', function ($query) {
                $query->where('status_rancangan', 'Disetujui') // Filter hanya rancangan yang disetujui
                    ->where(function ($subQuery) {
                        $subQuery->where('tentang', 'like', "%{$this->search}%")
                            ->orWhere('no_rancangan', 'like', "%{$this->search}%");
                    });
            })
            ->paginate($this->perPage); // Tambahkan pagination

        return view('livewire.perangkatdaerah.fasilitasi.fasilitasi-berlangsung', compact('fasilitasiBerlangsung'));
    }
}
