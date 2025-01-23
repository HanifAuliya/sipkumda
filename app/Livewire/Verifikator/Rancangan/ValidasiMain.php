<?php

namespace App\Livewire\Verifikator\Rancangan;

use Livewire\Component;

class ValidasiMain extends Component
{
    public $activeTab = 'menunggu'; // Tab aktif default

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.verifikator.rancangan.validasi-main')->layout('layouts.app');
    }
}
