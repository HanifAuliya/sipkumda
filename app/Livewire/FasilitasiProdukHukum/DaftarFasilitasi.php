<?php

namespace App\Livewire\FasilitasiProdukHukum;

use App\Models\FasilitasiProdukHukum;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class DaftarFasilitasi extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $tahun = '';
    public $jenisRancangan = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'jenisRancangan' => ['except' => ''],
        'tahun' => ['except' => ''],
    ];

    public function updating($property)
    {
        if (in_array($property, ['search', 'perPage', 'tahun', 'jenisRancangan'])) {
            $this->resetPage();
        }
    }

    /** Query fasilitasi sesuai filter; Perangkat Daerah hanya melihat miliknya. */
    private function query()
    {
        $user = auth()->user();

        return FasilitasiProdukHukum::with('rancangan.user.perangkatDaerah')
            ->when($user->hasRole('Perangkat Daerah'), fn ($query) => $query->milik($user->id))
            ->when($this->search, fn ($query) => $query->whereHas('rancangan', fn ($q) => $q
                ->where('tentang', 'like', "%{$this->search}%")
                ->orWhere('no_rancangan', 'like', "%{$this->search}%")))
            ->when($this->tahun, fn ($query) => $query->whereYear('tanggal_fasilitasi', $this->tahun))
            ->when($this->jenisRancangan, fn ($query) => $query->whereHas(
                'rancangan',
                fn ($q) => $q->where('jenis_rancangan', $this->jenisRancangan)
            ))
            ->orderByDesc('tanggal_fasilitasi');
    }

    public function deleteFasilitasi($id)
    {
        $fasilitasi = FasilitasiProdukHukum::findOrFail($id);
        $this->authorize('delete', $fasilitasi);

        DB::transaction(function () use ($fasilitasi) {
            if ($fasilitasi->file_rancangan && Storage::exists($fasilitasi->file_rancangan)) {
                Storage::delete($fasilitasi->file_rancangan);
            }

            $fasilitasi->delete();
        });

        $this->dispatch('swal:toast', [
            'type' => 'success',
            'message' => 'Fasilitasi berhasil dihapus.',
        ]);
    }

    public function exportPDF()
    {
        $fasilitasiList = $this->query()->get();

        $fileName = 'fasilitasi-produk-hukum'
            . ($this->tahun ? '-' . $this->tahun : '')
            . ($this->jenisRancangan ? '-' . str($this->jenisRancangan)->slug() : '')
            . '.pdf';

        $pdf = Pdf::loadView('pdf.fasilitasi', compact('fasilitasiList'))->setPaper('A4', 'landscape');

        $this->dispatch('hideLoadingSwal');

        return response()->streamDownload(fn () => print($pdf->output()), $fileName);
    }

    public function render()
    {
        $tahunOptions = FasilitasiProdukHukum::selectRaw('YEAR(tanggal_fasilitasi) as tahun')
            ->whereNotNull('tanggal_fasilitasi')
            ->groupBy('tahun')
            ->orderByDesc('tahun')
            ->pluck('tahun');

        return view('livewire.fasilitasi-produk-hukum.daftar-fasilitasi', [
            'fasilitasiList' => $this->query()->paginate($this->perPage),
            'tahunOptions' => $tahunOptions,
        ])->layout('layouts.app');
    }
}
