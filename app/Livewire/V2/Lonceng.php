<?php

namespace App\Livewire\V2;

use App\Livewire\V2\Concerns\BukaNotifikasi;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/** Lonceng notifikasi v2. State-nya hanya satu angka, supaya polling tetap ringan untuk banyak pengguna. */
class Lonceng extends Component
{
    use BukaNotifikasi;

    private const BATAS = 10;

    /** Jumlah belum dibaca yang terakhir diketahui, untuk mengenali notifikasi baru. */
    #[Locked]
    public int $dikenal = 0;

    public function mount(): void
    {
        $this->dikenal = $this->belumDibaca();
    }

    /** Dipanggil berkala dan saat tab kembali dibuka; notifikasi baru diberi tahu lewat toast. */
    public function periksa(): void
    {
        $belumDibaca = $this->belumDibaca();

        if ($belumDibaca > $this->dikenal) {
            $terbaru = Auth::user()->notifikasiNaskah()->whereNull('read_at')->first();

            $this->dispatch('swal:toast', [
                'type' => 'info',
                'title' => $terbaru->data['title'] ?? 'Ada notifikasi baru',
                'message' => $terbaru->data['message'] ?? '',
            ]);
        }

        $this->dikenal = $belumDibaca;
    }

    #[On('notifikasi-berubah')]
    public function segarkan(): void
    {
        $this->dikenal = $this->belumDibaca();
    }

    public function tandaiSemua(): void
    {
        Auth::user()->notifikasiNaskah()->whereNull('read_at')->update(['read_at' => now()]);
        $this->dispatch('notifikasi-berubah');
    }

    private function belumDibaca(): int
    {
        return Auth::user()->notifikasiNaskah()->whereNull('read_at')->count();
    }

    public function render()
    {
        $belumDibaca = $this->belumDibaca();
        $this->dikenal = min($this->dikenal, $belumDibaca);

        return view('v2.lonceng', [
            'daftar' => Auth::user()->notifikasiNaskah()->limit(self::BATAS)->get(),
            'belumDibaca' => $belumDibaca,
        ]);
    }
}
