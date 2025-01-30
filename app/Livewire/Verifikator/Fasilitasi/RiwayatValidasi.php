<?php

namespace App\Livewire\Verifikator\Fasilitasi;

use Livewire\Component;
use App\Models\FasilitasiProdukHukum;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use App\Models\User;

class RiwayatValidasi extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $search = ''; // Pencarian
    public $perPage = 10; // Default jumlah item per halaman
    public $selectedFasilitasi;

    public function openModalRiwayatFasilitasi($idFasilitasi)
    {
        // Cari fasilitasi berdasarkan ID
        $this->selectedFasilitasi = FasilitasiProdukHukum::with(['rancangan.user', 'rancangan.perangkatDaerah'])
            ->findOrFail($idFasilitasi);

        // Buka modal
        $this->dispatch('openModalRiwayatFasilitasi');
    }

    public function resetData()
    {
        $this->selectedFasilitasi = null;
    }

    public function render()
    {
        $fasilitasiRiwayat = FasilitasiProdukHukum::with('rancangan')
            ->whereIn('status_berkas_fasilitasi', ['Disetujui', 'Ditolak'])
            ->whereIn('status_validasi_fasilitasi', ['Diterima', 'Ditolak'])
            ->whereHas('rancangan', function ($query) {
                $query->where('no_rancangan', 'like', "%{$this->search}%")
                    ->orWhere('tentang', 'like', "%{$this->search}%");
            })
            ->paginate($this->perPage);

        return view('livewire.verifikator.fasilitasi.riwayat-validasi', compact('fasilitasiRiwayat'));
    }
}
