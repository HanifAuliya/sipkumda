<?php

namespace App\Livewire\Verifikator\Rancangan;

use Livewire\Component;

class ValidasiMain extends Component
{
    public $activeTab = 'menunggu-validasi'; // Tab aktif default
    protected $queryString = ['activeTab']; // Masukkan 'tab' ke dalam query string


    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.verifikator.rancangan.validasi-main')->layout('layouts.app');
    }
}
