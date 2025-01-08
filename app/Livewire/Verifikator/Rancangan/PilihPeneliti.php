<?php

namespace App\Livewire\Verifikator\Rancangan;

use Livewire\Component;

class PilihPeneliti extends Component
{
    public $activeTab = 'menunggu'; // Default tab

    protected $queryString = ['activeTab']; // Masukkan 'tab' ke dalam query string

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }
    public function render()
    {
        return view('livewire.verifikator.rancangan.pilih-peneliti')->layout('layouts.app');
    }
}
