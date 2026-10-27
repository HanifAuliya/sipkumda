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

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function render()
    {
        $fasilitasiRiwayat = FasilitasiProdukHukum::with(['rancangan.user.perangkatDaerah', 'notaDinas'])
            ->whereIn('status_berkas_fasilitasi', ['Disetujui', 'Ditolak'])
            ->whereIn('status_validasi_fasilitasi', ['Diterima', 'Ditolak'])
            ->whereHas('rancangan', function ($query) {
                $query->where('no_rancangan', 'like', "%{$this->search}%")
                    ->orWhere('tentang', 'like', "%{$this->search}%");
            })
            ->orderBy('tanggal_validasi_fasilitasi', 'desc') // 🔥 Urutkan berdasarkan tanggal terbaru
            ->paginate($this->perPage);

        return view('livewire.verifikator.fasilitasi.riwayat-validasi', compact('fasilitasiRiwayat'));
    }
}
