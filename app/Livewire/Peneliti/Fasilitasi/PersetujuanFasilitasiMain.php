<?php

namespace App\Livewire\Peneliti\Fasilitasi;

use Livewire\Component;

class PersetujuanFasilitasiMain extends Component
{
    public $activeTab = 'menunggu-persetujuan'; // Harus string, bukan array


    protected $queryString = ['activeTab']; // Masukkan 'tab' ke dalam query string


    public function switchTab($tab)
    {
        $this->activeTab = $tab; // Pastikan ini tetap string
    }

    public function render()
    {
        return view('livewire.peneliti.fasilitasi.persetujuan-fasilitasi-main')->layout('layouts.app');
    }
}
