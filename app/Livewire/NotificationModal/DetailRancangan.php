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
        $rancangan = RancanganProdukHukum::with(['user.perangkatDaerah', 'revisi.peneliti'])
            ->where('slug', $slug)
            ->first();

        if (! $rancangan || auth()->user()->cannot('view', $rancangan)) {
            $this->dispatch('swal:error', [
                'type' => 'error',
                'title' => 'Tidak Dapat Dibuka',
                'message' => 'Rancangan tidak ditemukan atau Anda tidak memiliki akses.',
            ]);

            return;
        }

        $this->rancangan = $rancangan;
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
