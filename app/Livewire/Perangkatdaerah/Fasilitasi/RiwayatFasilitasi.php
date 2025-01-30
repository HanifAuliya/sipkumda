<?php

namespace App\Livewire\PerangkatDaerah\Fasilitasi;

use Livewire\Component;
use App\Models\FasilitasiProdukHukum;


class RiwayatFasilitasi extends Component
{
    public function render()
    {
        $riwayatFasilitasi = FasilitasiProdukHukum::with('rancangan')
            ->whereIn('status_berkas_fasilitasi', ['Disetujui', 'Ditolak'])
            ->get();

        return view('livewire.perangkatdaerah.fasilitasi.riwayat-fasilitasi', compact('riwayatFasilitasi'));
    }
}
