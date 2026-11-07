<?php

namespace App\Livewire\V2;

use App\Livewire\V2\Concerns\BukaNotifikasi;
use App\Support\PesanNotifikasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Semua notifikasi pengguna, karena lonceng hanya memuat yang terbaru. */
#[Layout('layouts.app')]
#[Title('Notifikasi')]
class Notifikasi extends Component
{
    use BukaNotifikasi, WithPagination;

    private const FILTER = ['semua', 'belum', 'tindakan'];

    #[Url(except: 'semua')]
    public string $filter = 'semua';

    public function pilihFilter(string $filter): void
    {
        abort_unless(in_array($filter, self::FILTER, true), 404);

        $this->filter = $filter;
        $this->resetPage();
    }

    public function tandaiSemua(): void
    {
        Auth::user()->notifikasiNaskah()->whereNull('read_at')->update(['read_at' => now()]);
        $this->dispatch('notifikasi-berubah');
    }

    #[On('notifikasi-berubah')]
    public function segarkan(): void
    {
    }

    /** Yang belum dibaca dan masih menunggu tindakan penerimanya. */
    private function perluTindakan(Builder $query): Builder
    {
        return $query->whereNull('read_at')->whereIn('data->kategori', [PesanNotifikasi::TUGAS, PesanNotifikasi::KEMBALI]);
    }

    public function render()
    {
        $notifikasi = fn () => Auth::user()->notifikasiNaskah()->getQuery();

        $query = match ($this->filter) {
            'belum' => $notifikasi()->whereNull('read_at'),
            'tindakan' => $this->perluTindakan($notifikasi()),
            default => $notifikasi(),
        };

        return view('v2.notifikasi', [
            'halaman' => $query->paginate(15),
            'jumlahBelum' => $notifikasi()->whereNull('read_at')->count(),
            'jumlahTindakan' => $this->perluTindakan($notifikasi())->count(),
        ]);
    }
}
