<?php

namespace App\Livewire\Verifikator\Fasilitasi;

use Livewire\Component;

class ValidasiFasilitasi extends Component
{
    public $activeTab = 'menunggu-validasi'; // Tab default

    protected $queryString = ['activeTab']; // Masukkan 'tab' ke dalam query string

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }
    public function render()
    {
        return view('livewire.verifikator.fasilitasi.validasi-fasilitasi')->layout('layouts.app');
    }
}
