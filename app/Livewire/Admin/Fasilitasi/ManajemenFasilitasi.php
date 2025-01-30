<?php

namespace App\Livewire\Admin\Fasilitasi;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\FasilitasiProdukHukum;

class ManajemenFasilitasi extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $fasilitasiList = FasilitasiProdukHukum::with('rancangan')
            ->whereHas('rancangan', function ($query) {
                $query->where('tentang', 'like', "%{$this->search}%")
                    ->orWhere('no_rancangan', 'like', "%{$this->search}%");
            })
            ->paginate($this->perPage);

        return view('livewire.admin.fasilitasi.manajemen-fasilitasi', compact('fasilitasiList'))->layout('layouts.app');
    }
}
