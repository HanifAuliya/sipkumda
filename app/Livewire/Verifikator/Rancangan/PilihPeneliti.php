<?php

namespace App\Livewire\Verifikator\Rancangan;

use App\Models\User;
use Livewire\Component;

class PilihPeneliti extends Component
{
    public $activeTab = 'menunggu-peneliti'; // Default tab

    protected $queryString = ['activeTab']; // Masukkan 'tab' ke dalam query string\

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }


    public function render()
    {
        // Dapatkan daftar user dengan role "peneliti"
        $listPeneliti = User::role('peneliti')->pluck('nama_user', 'id');

        return view('livewire.verifikator.rancangan.pilih-peneliti', compact('listPeneliti'))->layout('layouts.app');
    }
}
