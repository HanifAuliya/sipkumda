<?php

namespace App\Exports;

use App\Models\RancanganProdukHukum;
use Maatwebsite\Excel\Concerns\FromCollection;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MasterDataExport implements WithMultipleSheets
{
    /**
     * @return \Illuminate\Support\Collection
     */
    // public function collection()
    // {
    //     return RancanganProdukHukum::all();
    // }

    use Exportable;

    protected $tahun;
    protected $jenis;

    public function __construct($tahun, $jenis)
    {
        $this->tahun = $tahun;
        $this->jenis = $jenis;
    }

    public function sheets(): array
    {
        $sheets = [];

        if ($this->jenis === "all") {
            $sheets[] = new SingleJenisExport($this->tahun, "Peraturan Bupati");
            $sheets[] = new SingleJenisExport($this->tahun, "Surat Keputusan");
        } else {
            $sheets[] = new SingleJenisExport($this->tahun, $this->jenis);
        }

        return $sheets;
    }
}
