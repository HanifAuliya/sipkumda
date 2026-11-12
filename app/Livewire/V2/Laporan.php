<?php

namespace App\Livewire\V2;

use App\Exports\LaporanNaskahExport;
use App\Support\LaporanNaskah;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

/** Rekap naskah untuk Bagian Hukum: jumlah, lama proses, sebaran per tahap dan Perangkat Daerah, naskah tertahan. */
#[Layout('layouts.app')]
#[Title('Laporan')]
class Laporan extends Component
{
    #[Url(except: '')]
    public string $tahun = '';

    #[Url(except: '')]
    public string $jenis = '';

    private function filter(): array
    {
        $tahun = ctype_digit($this->tahun) ? (int) $this->tahun : null;
        $jenis = in_array($this->jenis, ['Peraturan Bupati', 'Keputusan Bupati'], true) ? $this->jenis : null;

        return [$tahun, $jenis];
    }

    public function unduhExcel()
    {
        [$tahun, $jenis] = $this->filter();
        $nama = 'Laporan Naskah' . ($tahun ? " {$tahun}" : '') . ($jenis ? " {$jenis}" : '') . '.xlsx';

        return Excel::download(new LaporanNaskahExport($tahun, $jenis), $nama);
    }

    public function render()
    {
        [$tahun, $jenis] = $this->filter();

        return view('v2.laporan', LaporanNaskah::susun($tahun, $jenis) + [
            'daftarTahun' => LaporanNaskah::daftarTahun(),
        ]);
    }
}
