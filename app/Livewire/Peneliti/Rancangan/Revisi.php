<?php

namespace App\Livewire\Peneliti\Rancangan;

use Livewire\Component;

class Revisi extends Component
{
    public $activeTab = 'menunggu'; // Tab default
    protected $queryString = ['activeTab']; // Masukkan 'tab' ke dalam query string


    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.peneliti.rancangan.revisi')->layout('layouts.app');
    }
}
