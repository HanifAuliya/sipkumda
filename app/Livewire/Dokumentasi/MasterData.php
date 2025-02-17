<?php

namespace App\Livewire\Dokumentasi;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\RancanganProdukHukum;
use App\Models\Revisi;
use App\Models\FasilitasiProdukHukum;
use App\Models\DokumentasiProdukHukum;

use App\Exports\MasterDataExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;


class MasterData extends Component
{
    use WithPagination;

    public $tahun;
    public $jenis; // Pastikan default berupa array
    public $dataType = 'Rancangan';
    public $search = '';

    public function export(Request $request)
    {
        $tahun = $request->tahun ?? "";
        $jenis = $request->jenis ?? "all";

        return Excel::download(new MasterDataExport($tahun, $jenis), 'MasterData.xlsx');
    }



    public function mount()
    {
        $this->tahun = date('Y'); // Set default tahun sekarang
        $this->jenis = "all"; // Simpan "all" sebagai string, bukan array
    }

    public function loadData($type)
    {
        $this->dataType = $type;
        $this->resetPage(); // Reset pagination setiap kali data diubah
    }

    public function updatedJenis($value)
    {
        $this->jenis = $value;
        $this->resetPage();
    }

    public function getSelectedDataProperty()
    {
        // Pastikan jenis selalu dalam bentuk array
        $jenisFilter = $this->jenis === "all" ? ["Peraturan Bupati", "Surat Keputusan"] : [$this->jenis];

        if ($this->dataType === 'Rancangan') {
            return RancanganProdukHukum::with('user')
                ->when($this->tahun, function ($query) {
                    if ($this->tahun !== "") { // Hanya filter jika tahun dipilih
                        return $query->whereYear('tanggal_pengajuan', $this->tahun);
                    }
                })
                ->whereIn('jenis_rancangan', $jenisFilter)
                ->where(function ($query) {
                    $query->where('tentang', 'like', '%' . $this->search . '%')
                        ->orWhere('no_rancangan', 'like', '%' . $this->search . '%');
                })
                ->paginate(10);
        } elseif ($this->dataType === 'Revisi') {
            return Revisi::with('rancangan.user')
                ->whereHas('rancangan', function ($query) use ($jenisFilter) {
                    if ($this->tahun !== "") { // Hanya filter jika tahun dipilih
                        $query->whereYear('tanggal_pengajuan', $this->tahun);
                    }
                    $query->whereIn('jenis_rancangan', $jenisFilter)
                        ->where(function ($query) {
                            $query->where('tentang', 'like', '%' . $this->search . '%')
                                ->orWhere('no_rancangan', 'like', '%' . $this->search . '%');
                        });
                })->paginate(10);
        } elseif ($this->dataType === 'Fasilitasi') {
            return FasilitasiProdukHukum::with('rancangan.user')
                ->whereHas('rancangan', function ($query) use ($jenisFilter) {
                    if ($this->tahun !== "") { // Hanya filter jika tahun dipilih
                        $query->whereYear('tanggal_pengajuan', $this->tahun);
                    }
                    $query->whereIn('jenis_rancangan', $jenisFilter)
                        ->where(function ($query) {
                            $query->where('tentang', 'like', '%' . $this->search . '%')
                                ->orWhere('no_rancangan', 'like', '%' . $this->search . '%');
                        });
                })->paginate(10);
        } elseif ($this->dataType === 'Dokumentasi') {
            return DokumentasiProdukHukum::with('rancangan.user')
                ->whereHas('rancangan', function ($query) use ($jenisFilter) {
                    if ($this->tahun !== "") { // Hanya filter jika tahun dipilih
                        $query->whereYear('tanggal_pengajuan', $this->tahun);
                    }
                    $query->whereIn('jenis_rancangan', $jenisFilter)
                        ->where(function ($query) {
                            $query->where('tentang', 'like', '%' . $this->search . '%')
                                ->orWhere('no_rancangan', 'like', '%' . $this->search . '%');
                        });
                })->paginate(10);
        }
    }



    public function render()
    {
        // Pastikan jenis selalu dalam bentuk array
        $jenisFilter = $this->jenis === "all" ? ["Peraturan Bupati", "Surat Keputusan"] : [$this->jenis];

        return view('livewire.dokumentasi.master-data', [
            'countRancangan' => RancanganProdukHukum::when($this->tahun, function ($query) {
                if ($this->tahun !== "") { // Hanya filter jika tahun dipilih
                    return $query->whereYear('tanggal_pengajuan', $this->tahun);
                }
            })
                ->whereIn('jenis_rancangan', $jenisFilter)
                ->count(),
            'countRevisi' => Revisi::whereHas('rancangan', function ($query) use ($jenisFilter) {
                if ($this->tahun !== "") { // Hanya filter jika tahun dipilih
                    $query->whereYear('tanggal_pengajuan', $this->tahun);
                }
                $query->whereIn('jenis_rancangan', $jenisFilter);
            })->count(),
            'countFasilitasi' => FasilitasiProdukHukum::whereHas('rancangan', function ($query) use ($jenisFilter) {
                if ($this->tahun !== "") { // Hanya filter jika tahun dipilih
                    $query->whereYear('tanggal_pengajuan', $this->tahun);
                }
                $query->whereIn('jenis_rancangan', $jenisFilter);
            })->count(),
            'countDokumentasi' => DokumentasiProdukHukum::whereHas('rancangan', function ($query) use ($jenisFilter) {
                if ($this->tahun !== "") { // Hanya filter jika tahun dipilih
                    $query->whereYear('tanggal_pengajuan', $this->tahun);
                }
                $query->whereIn('jenis_rancangan', $jenisFilter);
            })->count(),
            'selectedData' => $this->selectedData,
        ])->layout('layouts.app');
    }
}
