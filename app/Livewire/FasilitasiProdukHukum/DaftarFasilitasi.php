<?php

namespace App\Livewire\FasilitasiProdukHukum;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\FasilitasiProdukHukum;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class DaftarFasilitasi extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $perPage = 10;
    public $tahun = '';
    public $jenisRancangan;

    protected $queryString = [
        'search' => ['except' => ''],
        'jenisRancangan' => ['except' => ''],
        'tahun' => ['except' => '']
    ];



    protected $listeners = ['refreshTable' => '$refresh', 'deleteFasilitasi' => 'deleteFasilitasi'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function deleteFasilitasi($id)
    {
        $fasilitasi = FasilitasiProdukHukum::findOrFail($id);

        // Gunakan Policy untuk validasi hak akses
        $this->authorize('delete', $fasilitasi);

        DB::transaction(function () use ($fasilitasi) {
            // Hapus file dari storage jika ada
            if ($fasilitasi->file_rancangan && Storage::exists($fasilitasi->file_rancangan)) {
                Storage::delete($fasilitasi->file_rancangan);
            }

            // Hapus data fasilitasi dari database
            $fasilitasi->delete();
        });

        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Fasilitasi berhasil dihapus.',
        ]);

        $this->dispatch('refreshTable');
    }


    public function exportPDF()
    {
        $query = FasilitasiProdukHukum::with(['rancangan']);

        if ($this->search) {
            $query->whereHas('rancangan', function ($q) {
                $q->where('tentang', 'like', "%{$this->search}%")
                    ->orWhere('no_rancangan', 'like', "%{$this->search}%");
            });
        }

        if ($this->tahun) {
            $query->whereYear('tanggal_fasilitasi', $this->tahun);
        }

        if ($this->jenisRancangan) {
            $query->whereHas('rancangan', function ($q) {
                $q->where('jenis_rancangan', $this->jenisRancangan);
            });
        }

        $fasilitasiList = $query->orderByDesc('tanggal_fasilitasi')->get();

        // Buat nama file berdasarkan filter
        $fileName = 'fasilitasi-produk-hukum';
        $fileName .= $this->tahun ? '-' . $this->tahun : ''; // Tambah tahun jika dipilih
        $fileName .= $this->jenisRancangan ? '-' . str_replace(' ', '-', strtolower($this->jenisRancangan)) : ''; // Tambah jenis jika dipilih
        $fileName .= '.pdf'; // Tambahkan ekstensi

        // Load view PDF
        $pdf = Pdf::loadView('pdf.fasilitasi', compact('fasilitasiList'))
            ->setPaper('A4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName);
    }



    public function render()
    {
        $query = FasilitasiProdukHukum::with('rancangan');

        // **Filter Pencarian**
        if ($this->search) {
            $query->whereHas('rancangan', function ($query) {
                $query->where('tentang', 'like', "%{$this->search}%")
                    ->orWhere('no_rancangan', 'like', "%{$this->search}%");
            });
        }

        if ($this->tahun) {
            $query->whereYear('tanggal_fasilitasi', $this->tahun);
        }

        if ($this->jenisRancangan) {
            $query->whereHas('rancangan', function ($query) {
                $query->where('jenis_rancangan', $this->jenisRancangan);
            });
        }

        // Ambil data berdasarkan filter dan pagination
        $fasilitasiList = $query->orderByDesc('tanggal_fasilitasi')
            ->paginate($this->perPage);

        $tahunOptions = FasilitasiProdukHukum::selectRaw('YEAR(tanggal_fasilitasi) as tahun')
            ->groupBy('tahun')
            ->orderByDesc('tahun')
            ->pluck('tahun');

        return view('livewire.fasilitasi-produk-hukum.daftar-fasilitasi', compact('fasilitasiList', 'tahunOptions'))
            ->layout('layouts.app');
    }
}
