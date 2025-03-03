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
    public $lineChartData = [];
    public $selectedYear;

    public function mount()
    {
        $this->jumlahRancangan = RancanganProdukHukum::count();
        $this->jumlahRevisi = Revisi::count();
        $this->jumlahFasilitasi = FasilitasiProdukHukum::count();
        $this->jumlahDokumentasi = DokumentasiProdukHukum::count();

        $this->updateChartData();
        $this->selectedYear = now()->year;
        $this->updateLineChartData($this->selectedYear);
    }

    public function updateChartData()
    {
        $tahunIni = now()->year;

        // Data untuk Pie Chart (Total Jenis Rancangan)
        $jenisRancanganCounts = RancanganProdukHukum::selectRaw('jenis_rancangan, COUNT(*) as total')
            ->groupBy('jenis_rancangan')
            ->pluck('total', 'jenis_rancangan')->toArray();

        // Data untuk Bar Chart (Pengajuan Rancangan per Bulan, 12 Bulan)
        $months = collect(range(1, 12))->map(fn($m) => Carbon::create($tahunIni, $m, 1)->translatedFormat('F'))->toArray();

        // Menghitung jumlah pengajuan berdasarkan jenis_rancangan
        $pengajuanCounts = RancanganProdukHukum::whereYear('tanggal_pengajuan', $tahunIni)
            ->selectRaw('MONTH(tanggal_pengajuan) as month, jenis_rancangan, COUNT(*) as count')
            ->groupBy('month', 'jenis_rancangan')
            ->orderByRaw('MONTH(tanggal_pengajuan)')
            ->get()
            ->groupBy('month');

        // Pastikan semua bulan memiliki nilai untuk kedua kategori
        $rancanganBupatiData = collect(range(1, 12))->map(fn($m) => isset($pengajuanCounts[$m]) ? $pengajuanCounts[$m]->where('jenis_rancangan', 'Peraturan Bupati')->sum('count') : 0)->toArray();
        $keputusanBupatiData = collect(range(1, 12))->map(fn($m) => isset($pengajuanCounts[$m]) ? $pengajuanCounts[$m]->where('jenis_rancangan', 'Surat Keputusan')->sum('count') : 0)->toArray();

        $this->chartData = [
            'labels' => $months,
            'rancangan_bupati' => $rancanganBupatiData,
            'keputusan_bupati' => $keputusanBupatiData,
            'pie' => [
                'labels' => array_keys($jenisRancanganCounts),
                'data' => array_values($jenisRancanganCounts)
            ]
        ];

        // Debugging data untuk memastikan data dikirim dengan benar
        // dd($this->chartData);

        // Kirim event ke front-end untuk update chart
        $this->dispatch('refreshChart', $this->chartData);
    }

    public function updateLineChartData($tahun = null)
    {
        $tahun = $tahun ?? now()->year;

        // logger()->info("🚀 Memulai updateLineChartData untuk tahun: {$tahun}");

        // Label bulan (Januari - Desember)
        $months = collect(range(1, 12))->map(fn($m) => Carbon::create($tahun, $m, 1)->translatedFormat('F'))->toArray();

        // 🟢 Debugging Awal: Pastikan Fungsi Dijalankan
        // dd("Fungsi updateLineChartData() berjalan", $tahun, $months);

        // Hitung lama proses tiap tahap dalam hari
        $rancanganDurasi = RancanganProdukHukum::whereYear('tanggal_pengajuan', $tahun)
            ->whereNotNull('tanggal_rancangan_disetujui')
            ->selectRaw('MONTH(tanggal_pengajuan) as month, ROUND(AVG(DATEDIFF(tanggal_rancangan_disetujui, tanggal_pengajuan))) as avg_days')
            ->groupBy('month')
            ->pluck('avg_days', 'month')->toArray();


        $revisiDurasi = Revisi::whereYear('tanggal_peneliti_ditunjuk', $tahun)
            ->whereNotNull('tanggal_revisi')
            ->selectRaw('MONTH(tanggal_peneliti_ditunjuk) as month, AVG(DATEDIFF(tanggal_revisi, tanggal_peneliti_ditunjuk)) as avg_days')
            ->groupBy('month')
            ->pluck('avg_days', 'month')->toArray();

        $fasilitasiDurasi = FasilitasiProdukHukum::whereYear('tanggal_fasilitasi', $tahun)
            ->whereNotNull('tanggal_validasi_fasilitasi')
            ->selectRaw('MONTH(tanggal_fasilitasi) as month, AVG(DATEDIFF(tanggal_validasi_fasilitasi, tanggal_fasilitasi)) as avg_days')
            ->groupBy('month')
            ->pluck('avg_days', 'month')->toArray();

        // 🟢 Perbaiki Dokumentasi → Pantau `tanggal_pengarsipan` Saja
        $dokumentasiDurasi = DokumentasiProdukHukum::whereYear('tanggal_pengarsipan', $tahun)
            ->selectRaw('MONTH(tanggal_pengarsipan) as month, COUNT(*) as total')
            ->groupBy('month')
            ->pluck('total', 'month')->toArray();

        // Pastikan semua bulan memiliki nilai (default 0 jika tidak ada data)
        $this->lineChartData = [
            'labels' => $months,
            'rancangan' => collect(range(1, 12))->map(fn($m) => min($rancanganDurasi[$m] ?? 0, 100))->toArray(), // Batasi ke max 100
            'revisi' => collect(range(1, 12))->map(fn($m) => min($revisiDurasi[$m] ?? 0, 100))->toArray(),
            'fasilitasi' => collect(range(1, 12))->map(fn($m) => min($fasilitasiDurasi[$m] ?? 0, 100))->toArray(),
            'dokumentasi' => collect(range(1, 12))->map(fn($m) => min($dokumentasiDurasi[$m] ?? 0, 100))->toArray(),
        ];


        // 🟢 Debugging: Pastikan Data Tersedia
        logger()->info("📊 Data Line Chart:", $this->lineChartData);
        // dd($this->lineChartData);

        // Kirim event ke front-end untuk update chart
        $this->dispatch('refreshLineChart', $this->lineChartData);
    }




    public function render()
    {
        return view('livewire.dashboard')->layout('layouts.app');
    }
}
