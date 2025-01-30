<?php

namespace App\Livewire\PerangkatDaerah\Fasilitasi;

use Livewire\Component;

class Fasilitasiku extends Component
{

    public $activeTab = 'fasilitasi-berlangsung'; // Tab default

    protected $queryString = ['activeTab']; // Masukkan 'tab' ke dalam query string

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.perangkatdaerah.fasilitasi.fasilitasiku')->layout('layouts.app');
    }
}
