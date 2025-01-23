<?php

namespace App\Livewire\Verifikator\Rancangan;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\RancanganProdukHukum;

class ValidasiMenunggu extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 5;

    public function render()
    {
        $rancanganMenunggu = RancanganProdukHukum::with(['revisi', 'user.perangkatDaerah']) // Load relasi revisi dan user
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
