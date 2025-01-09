<?php

namespace App\Livewire\Verifikator\Rancangan;

use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;
use App\Models\RancanganProdukHukum;
use App\Models\Revisi;


class PenelitiDitugaskan extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $search = ''; // Pencarian
    public $perPage = 5; // Jumlah data per halaman
    public $selectedRevisi = null; // Untuk melihat detail revisi

    protected $listeners = [
        'refreshPenelitiDitugaskan' => '$refresh', // Listener untuk refresh data
    ];

    public function updatedSearch()
    {
        $this->resetPage(); // Reset halaman saat pencarian diubah
    }

    public function openModal($id)
    {
        // Ambil data revisi berdasarkan ID
        $this->selectedRevisi = Revisi::with(['peneliti', 'rancangan'])->find($id);
        $this->dispatch('openModalPilihPeneliti');
    }

    public function render()
    {
        // Query Rancangan dengan Filter Revisi
        $rancangans = RancanganProdukHukum::where('status_berkas', 'Disetujui')
            ->whereHas('revisi', function ($query) {
                $query->where('status_revisi', 'Menunggu Revisi')
                    ->whereHas('peneliti', function ($subQuery) {
                        $subQuery->role('peneliti'); // Filter hanya user dengan role "peneliti"
                    });
            })
            ->with(['revisi' => function ($query) {
                $query->where('status_revisi', 'Menunggu Revisi')
                    ->with(['peneliti' => function ($subQuery) {
                        $subQuery->role('peneliti'); // Pastikan hanya user dengan role "peneliti"
                    }]);
            }])
            ->where(function ($query) {
                $query->where('tentang', 'like', "%{$this->search}%")
                    ->orWhere('no_rancangan', 'like', "%{$this->search}%");
            })
            ->paginate($this->perPage);

        return view('livewire.verifikator.rancangan.peneliti-ditugaskan', compact('rancangans'));
    }
}
