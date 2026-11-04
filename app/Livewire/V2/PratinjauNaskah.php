<?php

namespace App\Livewire\V2;

use App\Models\Naskah;
use App\Support\BandingNaskah;
use App\Support\TataLetakNaskah;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Pratinjau naskah di samping lembar kerja. Dipisah dari layar kerja dan baru dimuat saat panelnya
 * dibuka, karena naskah bisa ratusan baris dan tidak perlu ikut dikirim bila panelnya tertutup.
 */
class PratinjauNaskah extends Component
{
    #[Locked]
    public Naskah $naskah;

    #[Locked]
    public bool $aktif = false;

    /** Menampilkan perubahan dibanding rumusan awal. */
    #[Locked]
    public bool $banding = false;

    public function mount(Naskah $naskah): void
    {
        Gate::authorize('view', $naskah);
        $this->naskah = $naskah;
    }

    public function aktifkan(): void
    {
        $this->aktif = true;
        $this->dispatch('pratinjau-dimuat');
    }

    #[On('pratinjau-banding')]
    public function aturBanding(bool $aktif): void
    {
        $this->banding = $aktif;
        $this->dispatch('pratinjau-dimuat');
    }

    #[On('baris-berubah')]
    #[On('struktur-berubah')]
    public function segarkan(): void
    {
        if (! $this->aktif) {
            $this->skipRender();
        }
    }

    public function render()
    {
        $baris = $this->aktif ? $this->naskah->baris()->get() : collect();

        return view('v2.pratinjau-naskah', [
            'blok' => match (true) {
                ! $this->aktif => [],
                $this->banding => BandingNaskah::susun($this->naskah, $baris),
                default => TataLetakNaskah::susun($this->naskah, $baris),
            },
        ]);
    }
}
