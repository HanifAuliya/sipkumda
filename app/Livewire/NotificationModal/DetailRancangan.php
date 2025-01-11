<?php

namespace App\Livewire\NotificationModal;

use Livewire\Component;
use App\Models\RancanganProdukHukum;

class DetailRancangan extends Component
{
    public $rancangan;

    protected $listeners = ['openModalDetailRancangan' => 'loadRancangan'];

    public function loadRancangan($slug)
    {
        $this->rancangan = RancanganProdukHukum::where('slug', $slug)->first();
    }
    public function render()
    {
        return view('livewire.notification-modal.detail-rancangan');
    }
}
