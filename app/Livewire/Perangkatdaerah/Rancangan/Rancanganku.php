<?php

namespace App\Livewire\Perangkatdaerah\Rancangan;

use Livewire\Component;

class Rancanganku extends Component
{
    public $tab = 'sedang_diajukan'; // Default tab

    protected $queryString = ['tab']; // Masukkan 'tab' ke dalam query string


    public function render()
    {
        return view('livewire.perangkatdaerah.rancangan.rancanganku')->layout('layouts.app');
    }
}
