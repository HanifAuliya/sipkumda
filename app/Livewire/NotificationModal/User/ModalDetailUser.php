<?php

namespace App\Livewire\NotificationModal\User;

use Livewire\Component;
use App\Models\RancanganProdukHukum;

class ModalDetailUser extends Component
{
    public $rancangan;

    protected $listeners = ['openModalDetailUser' => 'loadRancangan'];

    public function loadRancangan($slug)
    {
        $this->rancangan = RancanganProdukHukum::where('slug', $slug)->first();
    }

    public function render()
    {
        return view('livewire.notification-modal.user.modal-detail-user');
    }
}
