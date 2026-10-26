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
        $this->rancangan = RancanganProdukHukum::with(['user.perangkatDaerah', 'revisi.peneliti'])
            ->where('slug', $slug)
            ->first();

        $this->dispatch('openModal', 'modalDetailRancangan');
    }

    public function refreshData()
    {
        $this->reset(['rancangan']);
    }
    public function render()
    {
        return view('livewire.notification-modal.detail-rancangan');
    }
}
