<?php

namespace App\Livewire\PerangkatDaerah\Rancangan;

use Livewire\Component;

class Rancanganku extends Component
{
    public $activeTab = 'sedang_diajukan'; // Default tab

    protected $queryString = ['activeTab']; // Masukkan 'tab' ke dalam query string

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.perangkatdaerah.rancangan.rancanganku')->layout('layouts.app');
    }
}
