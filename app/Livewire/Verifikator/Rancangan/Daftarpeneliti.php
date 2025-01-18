<?php

namespace App\Livewire\Verifikator\Rancangan;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Revisi;


class Daftarpeneliti extends Component
{
    use WithPagination;

    public $search = ''; // Pencarian
    public $perPage = 10; // Jumlah data per halaman

    public function updatedSearch()
    {
        $this->resetPage(); // Reset halaman saat pencarian diubah
    }

    public function render()
    {
        // Ambil user dengan role peneliti
        $peneliti = User::role('peneliti')
            ->with(['revisi' => function ($query) {
                $query->where('status_revisi', 'Menunggu Revisi')
                    ->with('rancangan'); // Eager load rancangan terkait
            }])
            ->where('nama_user', 'like', "%{$this->search}%") // Filter pencarian
            ->paginate($this->perPage);

        return view('livewire.verifikator.rancangan.daftarpeneliti', compact('peneliti'));
    }
}
