<?php

namespace App\Livewire\Fasilitasi;

use App\Models\FasilitasiProdukHukum;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modal detail fasilitasi yang dipakai bersama di semua halaman (dimuat di layout).
 * Buka dari Blade: Livewire.dispatch('openDetailFasilitasi', { id: 12 })
 */
class DetailFasilitasi extends Component
{
    public ?FasilitasiProdukHukum $fasilitasi = null;

    #[On('openDetailFasilitasi')]
    public function load(int $id): void
    {
        $fasilitasi = FasilitasiProdukHukum::with(['rancangan.user.perangkatDaerah', 'notaDinas'])->find($id);

        if (! $fasilitasi || auth()->user()->cannot('view', $fasilitasi)) {
            $this->dispatch('swal:error', [
                'type' => 'error',
                'title' => 'Tidak Dapat Dibuka',
                'message' => 'Fasilitasi tidak ditemukan atau Anda tidak memiliki akses.',
            ]);

            return;
        }

        $this->fasilitasi = $fasilitasi;
        $this->dispatch('openModal', 'modalDetailFasilitasi');
    }

    public function close(): void
    {
        $this->fasilitasi = null;
    }

    public function render()
    {
        return view('livewire.fasilitasi.detail-fasilitasi');
    }
}
