<?php

namespace App\Livewire\NotificationModal\Verifikator;

use Livewire\Component;
use App\Models\RancanganProdukHukum;

class ModalDetailVerifikator extends Component
{
    public $rancangan;

    protected $listeners = ['openVerifikatorModal' => 'loadRancangan'];

    public function loadRancangan($slug)
    {
        $this->rancangan = RancanganProdukHukum::where('slug', $slug)->first();
        dd($slug);
    }

    public function render()
    {
        return view('livewire.notification-modal.verifikator.modal-detail-verifikator');
    }
}
