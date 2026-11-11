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

    /** Peraturan Bupati | Keputusan Bupati; kosong = semua. */
    #[Url(except: '')]
    public string $jenis = '';

    public function updatedJenis(): void
    {
        $this->resetPage();
    }

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
                // Menunggu nota dinas masih urusan fasilitasi (menu Daftar Nota Dinas ada di kelompok itu).
                'fasilitasi' => $jumlah([...array_filter(TahapNaskah::SETELAH_RANCANGAN, fn (TahapNaskah $t) => ! $t->terkunci()), TahapNaskah::Final]),
                // Sama dengan isi menu Penandatanganan: yang sedang berjalan dan yang menunggu arsip.
                'ttd' => $jumlah([TahapNaskah::Penandatanganan, TahapNaskah::Arsip]),
                'selesai' => $jumlah([TahapNaskah::Selesai]),
            ],
            'halaman' => (clone $semua)
                ->when(TahapNaskah::tryFrom($this->tahap), fn ($q, $tahap) => $q->where('tahap', $tahap))
                ->when(in_array($this->jenis, ['Peraturan Bupati', 'Keputusan Bupati'], true), fn ($q) => $q->where('jenis', $this->jenis))
                ->latest('updated_at')
                ->paginate(20),
            'giliranSaya' => (clone $semua)->menunggu($user)->latest('updated_at')->limit(50)->get(),
        ]);
    }
}
