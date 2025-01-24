<?php

namespace App\Livewire\Verifikator\Rancangan;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Revisi;

class RiwayatValidasi extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 5;

    public function render()
    {
        $riwayatValidasi = Revisi::with(['rancangan.user.perangkatDaerah'])
            ->whereIn('status_revisi', ['Proses Revisi', 'Direvisi'])
            ->whereIn('status_validasi', ['Diterima', 'Ditolak'])
            ->whereHas('rancangan', function ($query) {
                $query->where('tentang', 'like', "%{$this->search}%")
                    ->orWhere('no_rancangan', 'like', "%{$this->search}%");
            })
            ->orderBy('tanggal_revisi', 'desc')
            ->paginate($this->perPage);

        return view('livewire.verifikator.rancangan.riwayat-validasi', compact('riwayatValidasi'));
    }
}
