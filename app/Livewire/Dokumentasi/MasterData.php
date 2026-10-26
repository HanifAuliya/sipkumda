<?php

namespace App\Livewire\Dokumentasi;

use App\Exports\MasterDataExport;
use App\Models\DokumentasiProdukHukum;
use App\Models\FasilitasiProdukHukum;
use App\Models\RancanganProdukHukum;
use App\Models\Revisi;
use Illuminate\Http\Request;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Rekap data sistem per tahap (rancangan, revisi, fasilitasi, dokumentasi),
 * difilter tahun pengajuan dan jenis rancangan.
 */
class MasterData extends Component
{
    use WithPagination, WithoutUrlPagination;

    /** Tipe data => model yang terhubung ke rancangan lewat relasi "rancangan". */
    private const TIPE = [
        'Rancangan' => RancanganProdukHukum::class,
        'Revisi' => Revisi::class,
        'Fasilitasi' => FasilitasiProdukHukum::class,
        'Dokumentasi' => DokumentasiProdukHukum::class,
    ];

    public $tahun;
    public $jenis = 'all';
    public $dataType = 'Rancangan';
    public $search = '';

    // Dipanggil sebagai controller oleh route export.masterdata.
    public function export(Request $request)
    {
        return Excel::download(new MasterDataExport($request->tahun ?? '', $request->jenis ?? 'all'), 'MasterData.xlsx');
    }

    public function mount()
    {
        $this->tahun = date('Y');
    }

    public function updating($property)
    {
        if (in_array($property, ['tahun', 'jenis', 'search'])) {
            $this->resetPage();
        }
    }

    public function loadData($type)
    {
        if (array_key_exists($type, self::TIPE)) {
            $this->dataType = $type;
            $this->resetPage();
        }
    }

    /** Kondisi filter pada tabel rancangan. */
    private function filterRancangan($query, bool $denganPencarian = false)
    {
        return $query
            ->when($this->tahun, fn ($q) => $q->whereYear('tanggal_pengajuan', $this->tahun))
            ->whereIn('jenis_rancangan', $this->jenis === 'all' ? ['Peraturan Bupati', 'Surat Keputusan'] : [$this->jenis])
            ->when($denganPencarian && $this->search, fn ($q) => $q->where(fn ($sub) => $sub
                ->where('tentang', 'like', "%{$this->search}%")
                ->orWhere('no_rancangan', 'like', "%{$this->search}%")));
    }

    private function query(string $tipe, bool $denganPencarian = false)
    {
        $model = self::TIPE[$tipe];

        return $model === RancanganProdukHukum::class
            ? $this->filterRancangan(RancanganProdukHukum::query(), $denganPencarian)
            : $model::whereHas('rancangan', fn ($q) => $this->filterRancangan($q, $denganPencarian));
    }

    public function render()
    {
        $tipe = array_key_exists($this->dataType, self::TIPE) ? $this->dataType : 'Rancangan';
        $relasi = $tipe === 'Rancangan' ? ['user.perangkatDaerah'] : ['rancangan.user.perangkatDaerah'];

        return view('livewire.dokumentasi.master-data', [
            'counts' => collect(self::TIPE)->map(fn ($model, $nama) => $this->query($nama)->count()),
            'selectedData' => $this->query($tipe, true)->with($relasi)->latest()->paginate(10),
            'tahunList' => RancanganProdukHukum::selectRaw('YEAR(tanggal_pengajuan) as tahun')
                ->whereNotNull('tanggal_pengajuan')->distinct()->orderByDesc('tahun')->pluck('tahun'),
        ])->layout('layouts.app');
    }
}
