<?php

namespace App\Livewire\V2;

use App\Models\Pengaturan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Pengaturan')]
class PengaturanNaskah extends Component
{
    #[Validate('required|string|max:150')]
    public string $namaBupati = '';

    #[Validate('required|string|max:150')]
    public string $namaSekda = '';

    public bool $tersimpan = false;

    public function mount(): void
    {
        $this->namaBupati = Pengaturan::ambil(Pengaturan::NAMA_BUPATI);
        $this->namaSekda = Pengaturan::ambil(Pengaturan::NAMA_SEKDA);
    }

    public function simpan(): void
    {
        $this->validate();

        Pengaturan::simpan(Pengaturan::NAMA_BUPATI, $this->namaBupati);
        Pengaturan::simpan(Pengaturan::NAMA_SEKDA, $this->namaSekda);

        $this->tersimpan = true;
    }

    public function render()
    {
        return view('v2.pengaturan');
    }
}
