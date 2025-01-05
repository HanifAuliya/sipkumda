<?php

namespace App\Livewire\Perangkatdaerah\Rancangan;

use Livewire\Component;
use App\Models\RancanganProdukHukum;
use Livewire\WithPagination;

class SedangDiajukan extends Component
{
    use WithPagination; // Tambahkan trait ini

    public $search = '';
    public $perPage = 3; // Default: 3 data per halaman

    protected $queryString = ['search', 'perPage'];


    protected $listeners = ['rancanganDitambahkan' => 'refreshRancangan'];

    public function refreshRancangan()
    {
        $this->resetPage(); // Reset ke halaman pertama
    }

    public function updatingSearch()
    {
        $this->resetPage(); // Reset ke halaman pertama jika pencarian berubah
    }

    public function updatingPerPage()
    {
        $this->resetPage(); // Reset ke halaman pertama jika jumlah per halaman berubah
    }

    public function render()
    {
        $query = RancanganProdukHukum::with(['user', 'perangkatDaerah', 'revisi'])
            ->where('id_user', auth()->id())
            ->whereIn('status_rancangan', ['Dalam Proses', 'Ditolak'])
            ->orderBy('tanggal_pengajuan', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter pencarian
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('no_rancangan', 'like', '%' . $this->search . '%')
                    ->orWhere('tentang', 'like', '%' . $this->search . '%');
            });
        }

        // Ambil data dengan pagination
        $rancangan = $query->paginate($this->perPage);

        return view('livewire.perangkatdaerah.rancangan.sedang-diajukan', compact('rancangan'));
    }
}
