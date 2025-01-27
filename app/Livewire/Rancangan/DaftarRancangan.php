<?php

namespace App\Livewire\Rancangan;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\RancanganProdukHukum;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;

class DaftarRancangan extends Component
{
    use WithPagination;

    public $search = '';
    public $jenisFilter = '';
    public $perPage = 5;
    public $sortField = 'id_rancangan';
    public $sortDirection = 'asc';
    public $selectedRancangan;

    public $isAdmin = false; // Menandai apakah user adalah Admin
    public $isVerifier = false; // Menandai apakah user adalah Verifikator

    protected $queryString = [
        'search' => ['except' => ''],
        'jenisFilter' => ['except' => ''],
        'sortField' => ['except' => 'id_rancangan'],
        'sortDirection' => ['except' => 'asc'],
    ];


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



    public function render()
    {
        $rancanganProdukHukum = RancanganProdukHukum::with(['user.perangkatDaerah', 'revisi'])
            ->when(auth()->user()->hasRole(['Perangkat Daerah']), function ($query) {
                // Perangkat Daerah hanya dapat melihat rancangan miliknya
                // $query->where('id_user', auth()->id());
            })
            ->when(auth()->user()->hasRole(['Admin', 'Verifikator', 'Peneliti']), function ($query) {
                // Admin, Verifikator, dan Peneliti dapat melihat semua rancangan
            })
            ->when($this->search, function ($query) {
                $query->where('tentang', 'like', '%' . $this->search . '%')
                    ->orWhere('no_rancangan', 'like', '%' . $this->search . '%');
            })
            ->when($this->jenisFilter, function ($query) {
                $query->where('jenis_rancangan', $this->jenisFilter);
            })
            ->orderBy('created_at', 'desc') // Urutkan berdasarkan waktu terbaru
            ->orderBy($this->sortField, $this->sortDirection) // Urutkan berdasarkan field tambahan
            ->paginate($this->perPage);

        return view('livewire.rancangan.daftar-rancangan', compact('rancanganProdukHukum'))->layout('layouts.app');
    }
}
