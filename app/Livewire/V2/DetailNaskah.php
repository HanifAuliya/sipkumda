<?php

namespace App\Livewire\V2;

use App\Models\Naskah;
use App\Models\NaskahBaris as B;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/** Modal detail naskah yang dibuka dari daftar dan notifikasi, sebelum masuk ke layar kerja. */
class DetailNaskah extends Component
{
    #[Locked]
    public ?int $naskahId = null;

    #[On('bukaDetailNaskah')]
    public function buka(string $slug): void
    {
        $naskah = Naskah::where('slug', $slug)->firstOrFail();
        Gate::authorize('view', $naskah);

        $naskah->tandaiNotifikasiDibaca(auth()->user());
        $this->dispatch('notifikasi-berubah');

        $this->naskahId = $naskah->id;
        $this->dispatch('openModal', 'modalDetailNaskah');
    }

    public function tutup(): void
    {
        $this->reset('naskahId');
    }

    public function render()
    {
        $naskah = $this->naskahId
            ? Naskah::with(['pemrakarsa.perangkatDaerah', 'peneliti', 'notaDinas'])->find($this->naskahId)
            : null;

        // Hak lihat diperiksa lagi: permintaan ini bisa datang lama setelah modal dibuka.
        if ($naskah && Gate::denies('view', $naskah)) {
            $naskah = null;
        }

        $hitung = $naskah
            ? $naskah->baris()->reorder()->toBase()->selectRaw('status, count(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status')
            : collect();

        return view('v2.detail-naskah', [
            'naskah' => $naskah,
            'total' => $hitung->sum(),
            'belum' => $hitung[B::BELUM] ?? 0,
            'diubah' => collect([B::DIUBAH, B::DIHAPUS, B::PERLU_PD, B::DISESUAIKAN])->sum(fn (string $s) => $hitung[$s] ?? 0),
            'perlu' => $hitung[B::PERLU_PD] ?? 0,
            'berkas' => $naskah ? $naskah->berkas()->where('aktif', true)->get() : collect(),
            'giliranSaya' => $naskah?->giliranMilik(auth()->user()) ?? false,
            'bisaUnduh' => $naskah && Gate::allows('unduh', $naskah),
        ]);
    }
}
