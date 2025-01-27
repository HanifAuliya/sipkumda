<?php

namespace App\Livewire\Peneliti\Rancangan;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Revisi;

class DaftarRevisi extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;

    public function render()
    {
        // Ambil data revisi berdasarkan peneliti yang sedang login dengan status validasi "Diterima"
        $revisiList = Revisi::with(['rancangan.user.perangkatDaerah'])
            ->whereHas('peneliti', function ($query) {
                $query->where('id', auth()->id()); // Hanya revisi yang dibuat oleh peneliti yang sedang login
            })
            ->where('status_validasi', 'Diterima') // Hanya menampilkan status validasi "Diterima"
            ->where(function ($query) {
                $query->whereHas('rancangan', function ($query) {
                    $query->where('tentang', 'like', "%{$this->search}%")
                        ->orWhere('no_rancangan', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('tanggal_revisi', 'desc')
            ->paginate($this->perPage);

        return view('livewire.peneliti.rancangan.daftar-revisi', compact('revisiList'));
    }
}
