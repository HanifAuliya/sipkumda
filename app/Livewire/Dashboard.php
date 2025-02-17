<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\RancanganProdukHukum;
use App\Models\Revisi;
use App\Models\FasilitasiProdukHukum;
use App\Models\DokumentasiProdukHukum;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Dashboard extends Component
{
    public $jumlahRancangan;
    public $jumlahRevisi;
    public $jumlahFasilitasi;
    public $jumlahDokumentasi;

    public $chartData = [];

    public function mount()
    {
        $this->jumlahRancangan = RancanganProdukHukum::count();
        $this->jumlahRevisi = Revisi::count();
        $this->jumlahFasilitasi = FasilitasiProdukHukum::count();
        $this->jumlahDokumentasi = DokumentasiProdukHukum::count();


        // Dapatkan tahun saat ini
        $tahunIni = Carbon::now()->year;

        // Ambil data dari database untuk tahun ini
        $data = RancanganProdukHukum::select(
            DB::raw("COUNT(id_rancangan) as total"),
            DB::raw("MONTH(tanggal_pengajuan) as bulan")
        )
            ->whereYear('tanggal_pengajuan', $tahunIni)
            ->groupBy('bulan')
            ->orderBy('bulan', 'ASC')
            ->pluck('total', 'bulan')
            ->toArray();

        // Inisialisasi array kosong untuk menampung data bulan yang ada
        $bulanArray = [];

        // Konversi nomor bulan menjadi nama bulan dan hanya simpan yang memiliki data
        foreach ($data as $bulan => $total) {
            if ($total > 0) { // Hanya tambahkan bulan jika memiliki data
                $namaBulan = Carbon::create()->month($bulan)->locale('id')->translatedFormat('F');
                $bulanArray[$namaBulan] = $total;
            }
        }

        // Simpan ke chartData
        $this->chartData = $bulanArray;
    }


    public function render()
    {
        return view('livewire.dashboard')->layout('layouts.app');
    }
}
