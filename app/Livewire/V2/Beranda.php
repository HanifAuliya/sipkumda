<?php

namespace App\Livewire\V2;

use App\Enums\TahapNaskah;
use App\Models\Naskah;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.v2')]
#[Title('Pekerjaan')]
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
            ->with(['pemrakarsa.perangkatDaerah'])
            ->withCount('baris')
            ->when($this->cari !== '', fn ($q) => $q->where(fn ($s) => $s
                ->where('tentang', 'like', "%{$this->cari}%")
                ->orWhere('nomor', 'like', "%{$this->cari}%")));

        return view('v2.beranda', [
            'halaman' => (clone $semua)
                ->when(TahapNaskah::tryFrom($this->tahap), fn ($q, $tahap) => $q->where('tahap', $tahap))
                ->latest('updated_at')
                ->paginate(20),
            'giliranSaya' => (clone $semua)->menunggu($user)->latest('updated_at')->limit(50)->get(),
            'bisaBuat' => Gate::allows('create', Naskah::class),
        ]);
    }
}
