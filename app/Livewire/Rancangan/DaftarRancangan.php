<?php

namespace App\Livewire\Rancangan;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\RancanganProdukHukum;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class DaftarRancangan extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $jenisRancangan = '';
    public $tahun = ''; // Menyimpan tahun yang dipilih
    public $perPage = 5;
    public $sortField = 'id_rancangan';
    public $sortDirection = 'asc';
    public $selectedRancangan;

    public $isAdmin = false; // Menandai apakah user adalah Admin
    public $isVerifier = false; // Menandai apakah user adalah Verifikator

    protected $queryString = [
        'search' => ['except' => ''],
        'jenisRancangan' => ['except' => ''],
        'tahun' => ['except' => ''],
        'sortField' => ['except' => 'id_rancangan'],
        'sortDirection' => ['except' => 'asc'],
    ];

    protected $listeners = ['refreshTable' => '$refresh', 'deleteRancangan' => 'delete'];


    // Memuat data opsi tahun dari database
    public function getTahunOptionsProperty()
    {
        return RancanganProdukHukum::selectRaw('YEAR(tanggal_pengajuan) as tahun')
            ->groupBy('tahun')
            ->orderByDesc('tahun')
            ->pluck('tahun');
    }


    public function mount()
    {
        $this->isAdmin = Auth::user()->hasRole('Admin');
        $this->isVerifier = Auth::user()->hasRole('Verifikator');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingJenisFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusBerkasFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusRevisiFilter()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function showDetail($id)
    {
        $rancangan = RancanganProdukHukum::with(['user.perangkatDaerah', 'revisi'])->find($id);

        if (!$rancangan) {
            $this->dispatch('swal:error', [
                'type' => 'error',
                'title' => 'Tidak Ditemukan',
                'message' => 'Rancangan tidak ditemukan.',
            ]);
            return;
        }

        // Gunakan policy untuk memeriksa akses
        if (auth()->user()->cannot('view', $rancangan)) {
            $this->dispatch('swal:error', [
                'type' => 'error',
                'title' => 'Akses Ditolak',
                'message' => 'Anda tidak memiliki izin untuk melihat rincian rancangan ini.',
            ]);
            return;
        }

        // Jika lolos policy, simpan ke `selectedRancangan`
        $this->selectedRancangan = $rancangan;

        // Tampilkan modal
        $this->dispatch('show-modal', ['modalId' => 'detailModal']);
    }

    public function resetDetail()
    {
        $this->selectedRancangan = null;
    }

    public function delete($id)
    {
        // Pastikan hanya Admin atau Verifikator yang dapat menghapus
        if (!$this->isAdmin && !$this->isVerifier) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus rancangan.');
        }

        // Cari rancangan
        $rancangan = RancanganProdukHukum::find($id);

        // Jika rancangan tidak ditemukan
        if (!$rancangan) {
            $this->dispatch('swal:error', [
                'type' => 'error',
                'title' => 'Tidak Ditemukan',
                'message' => 'Rancangan tidak ditemukan.',
            ]);
            return;
        }

        // Hapus rancangan
        $rancangan->delete();

        // Kirim event ke frontend untuk notifikasi sukses
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Dihapus',
            'message' => 'Rancangan berhasil dihapus!',
        ]);
    }


    public function exportPDF()
    {
        $query = RancanganProdukHukum::with(['user.perangkatDaerah', 'revisi.peneliti']);

        // Filter pencarian
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('tentang', 'like', "%{$this->search}%")
                    ->orWhere('no_rancangan', 'like', "%{$this->search}%");
            });
        }

        // Filter jenis rancangan
        if (!empty($this->jenisRancangan)) {
            $query->where('jenis_rancangan', $this->jenisRancangan);
        }

        // Filter tahun pengajuan
        if (!empty($this->tahun)) {
            $query->whereYear('tanggal_pengajuan', $this->tahun);
        }

        $rancanganList = $query->orderBy('created_at', 'desc')->get();

        // Generate nama file berdasarkan filter
        $tahun = $this->tahun ? "_{$this->tahun}" : "";
        $jenis = $this->jenisRancangan ? "_" . Str::slug($this->jenisRancangan) : "";
        $fileName = "RancanganProdukHukum{$tahun}{$jenis}.pdf";

        // Load view ke dalam PDF
        $pdf = Pdf::loadView('pdf.rancangan', compact('rancanganList'))
            ->setPaper('A4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName);
    }

    public function render()
    {
        $query = RancanganProdukHukum::with(['user.perangkatDaerah', 'revisi']);

        // **Filter berdasarkan peran pengguna**
        if (auth()->user()->hasRole('Perangkat Daerah')) {
            $query->where('id_user', auth()->id());
        }

        // **Filter pencarian (tentang & nomor rancangan)**
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('tentang', 'like', "%{$this->search}%")
                    ->orWhere('no_rancangan', 'like', "%{$this->search}%");
            });
        }

        // **Filter berdasarkan tahun**
        if (!empty($this->tahun)) {
            $query->whereYear('tanggal_pengajuan', $this->tahun);
        }

        // **Filter berdasarkan jenis rancangan**
        if (!empty($this->jenisRancangan)) {
            $query->where('jenis_rancangan', $this->jenisRancangan);
        }

        // **Ambil data yang sudah difilter & urutkan**
        $rancanganProdukHukum = $query->orderBy('created_at', 'desc')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        // **Ambil daftar tahun dari database (untuk filter dropdown)**
        $tahunOptions = RancanganProdukHukum::selectRaw('YEAR(tanggal_pengajuan) as tahun')
            ->groupBy('tahun')
            ->orderByDesc('tahun')
            ->pluck('tahun');

        return view('livewire.rancangan.daftar-rancangan', [
            'rancanganProdukHukum' => $rancanganProdukHukum,
            'tahunOptions' => $tahunOptions, // Dropdown tahun
        ])->layout('layouts.app');
    }
}
