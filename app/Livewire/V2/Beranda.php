<?php

namespace App\Livewire\V2;

use App\Enums\TahapNaskah;
use App\Models\Naskah;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Beranda extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $cari = '';

    #[Url(except: '')]
    public string $tahap = '';

    public function updatedCari(): void
    {
        $this->resetPage();
    }

    public function updatedTahap(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $semua = Naskah::terlihatOleh($user)
            ->with(['pemrakarsa.perangkatDaerah', 'peneliti'])
            ->when($this->cari !== '', fn ($q) => $q->where(fn ($s) => $s
                ->where('tentang', 'like', "%{$this->cari}%")
                ->orWhere('nomor', 'like', "%{$this->cari}%")));

        $perTahap = Naskah::terlihatOleh($user)->toBase()
            ->selectRaw('tahap, count(*) as jumlah')->groupBy('tahap')->pluck('jumlah', 'tahap');
        $jumlah = fn (array $daftar) => collect($daftar)->sum(fn (TahapNaskah $t) => $perTahap[$t->value] ?? 0);

        return view('v2.beranda', [
            'statistik' => [
                'rancangan' => $jumlah(TahapNaskah::RANCANGAN),
                'fasilitasi' => $jumlah(array_filter(TahapNaskah::SETELAH_RANCANGAN, fn (TahapNaskah $t) => ! $t->terkunci())),
                'ttd' => $jumlah([TahapNaskah::Final, TahapNaskah::Penandatanganan, TahapNaskah::Arsip]),
                'selesai' => $jumlah([TahapNaskah::Selesai]),
            ],
            'halaman' => (clone $semua)
                ->when(TahapNaskah::tryFrom($this->tahap), fn ($q, $tahap) => $q->where('tahap', $tahap))
                ->latest('updated_at')
                ->paginate(20),
            'giliranSaya' => (clone $semua)->menunggu($user)->latest('updated_at')->limit(50)->get(),
        ]);
    }
}
