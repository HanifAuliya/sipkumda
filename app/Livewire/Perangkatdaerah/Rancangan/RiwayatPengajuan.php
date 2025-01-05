<?php

namespace App\Livewire\Perangkatdaerah\Rancangan;

use App\Models\RancanganProdukHukum;
use Livewire\Component;
use Livewire\WithPagination;

class RiwayatPengajuan extends Component
{
    use WithPagination;

    public $search = ''; // Untuk pencarian
    public $perPage = 3; // Default perPage adalah 3 data
    public $page = 1; // Halaman saat ini

    protected $queryString = ['search', 'perPage', 'page']; // Untuk mempertahankan state URL

    public function updatingSearch()
    {
        $this->page = 1; // Reset ke halaman pertama jika pencarian diubah
    }

    public function updatingPerPage()
    {
        $this->page = 1; // Reset ke halaman pertama jika perPage diubah
    }

    public function render()
    {
        $query = RancanganProdukHukum::with(['user', 'perangkatDaerah', 'revisi'])
            ->where('id_user', auth()->id())
            ->where('status_rancangan', 'Disetujui')
            ->orderBy('tanggal_pengajuan', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter pencarian
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('no_rancangan', 'like', '%' . $this->search . '%')
                    ->orWhere('tentang', 'like', '%' . $this->search . '%');
            });
        }

        // Ambil data berdasarkan jumlah perPage
        $riwayat = $query->paginate($this->perPage, ['*'], 'page', $this->page);

        return view('livewire.perangkatdaerah.rancangan.riwayat-pengajuan', compact('riwayat'));
    }
}
