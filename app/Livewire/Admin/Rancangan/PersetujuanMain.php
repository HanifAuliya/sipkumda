<?php

namespace App\Livewire\Admin\Rancangan;

use Livewire\Component;

class PersetujuanMain extends Component
{
    public $activeTab = 'menunggu'; // Default tab

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.admin.rancangan.persetujuan-main')->layout('layouts.app');
    }
}
