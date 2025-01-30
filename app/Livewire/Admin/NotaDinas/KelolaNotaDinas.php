<?php

namespace App\Livewire\Admin\NotaDinas;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\NotaDinas;
use App\Models\FasilitasiProdukHukum;
use App\Models\TandaTangan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

class KelolaNotaDinas extends Component
{
    use WithPagination;
    public $search = ''; // Pencarian
    public $perPage = 10; // Jumlah data per halaman

    public $fasilitasiId;
    public $nomorNota;
    public $tandaTanganId;
    public $tanggalNota;
    public $nomorInputan;
    public $fasilitasiList = [];
    public $tandaTanganList = [];

    public function updatingSearch()
    {
        $this->resetPage(); // Reset pagination ke halaman pertama saat pencarian berubah
    }

    public function updatingPerPage()
    {
        $this->resetPage(); // Reset pagination jika perPage berubah
    }
    public function mount()
    {
        $this->loadData();
    }
    public function loadData()
    {
        if (empty($this->fasilitasiList)) {
            $this->fasilitasiList = FasilitasiProdukHukum::where('status_validasi_fasilitasi', 'Diterima')
                ->with('rancangan')
                ->get();
        }

        if (empty($this->tandaTanganList)) {
            $this->tandaTanganList = TandaTangan::where('status', 'Aktif')->get();
        }
    }


    public function store()
    {
        $this->validate([
            'fasilitasiId' => 'required|exists:fasilitasi_produk_hukum,id',
            'nomorInputan' => 'required|numeric|digits_between:1,10', // Validasi nomor inputan
            'tandaTanganId' => 'required|exists:tanda_tangan,id',
        ]);

        $fasilitasi = FasilitasiProdukHukum::find($this->fasilitasiId);
        $jenisRancangan = $fasilitasi->rancangan->jenis_rancangan ?? '';

        // Menentukan format kode jenis rancangan (NDPB atau NDSK)
        $kodeJenis = ($jenisRancangan === "Peraturan Bupati") ? "NDPB" : "NDSK";

        // Generate nomor nota dinas
        $this->nomorNota = "180/{$this->nomorInputan}/{$kodeJenis}/KUM/" . now()->year;

        // Simpan ke database
        NotaDinas::create([
            'fasilitasi_id' => $this->fasilitasiId,
            'nomor_inputan' => $this->nomorInputan, // Simpan nomor inputan
            'nomor_nota' => $this->nomorNota,
            'tanggal_nota' => now(),
            'tanda_tangan_id' => $this->tandaTanganId,
        ]);

        // Reset input setelah penyimpanan
        $this->reset(['fasilitasiId', 'nomorInputan', 'tandaTanganId']);

        // Tutup modal dan tampilkan notifikasi sukses
        $this->dispatch('closeModalTambahNotaDinas');
        $this->dispatch('swal:nota', [
            'type' => 'success',
            'title' => 'Nota Dinas Berhasil Disimpan',
            'message' => "Nota Dinas dengan Nomor {$this->nomorNota} berhasil dibuat!",
        ]);
    }

    public function generatePDF($notaId)
    {

        $notaDinas = NotaDinas::with('fasilitasi.rancangan.user.perangkatDaerah', 'tandaTangan')->findOrFail($notaId);
        $pdf = Pdf::loadView('pdf.nota-dinas', compact('notaDinas'))->setPaper('A4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, "Nota-Dinas-{$notaDinas->nomor_inputan}.pdf");
    }

    public function render()
    {
        $notaDinasList = NotaDinas::with('fasilitasi.rancangan', 'tandaTangan')
            ->whereHas('fasilitasi.rancangan', function ($query) {
                $query->where('no_rancangan', 'like', "%{$this->search}%")
                    ->orWhere('tentang', 'like', "%{$this->search}%");
            })
            ->orWhere('nomor_nota', 'like', "%{$this->search}%")
            ->paginate($this->perPage);

        return view('livewire.admin.nota-dinas.kelola-nota-dinas', [
            'notaDinasList' => $notaDinasList,
            'fasilitasiOptions' => FasilitasiProdukHukum::where('status_validasi_fasilitasi', 'Diterima')->get(),
            'tandaTanganOptions' => TandaTangan::where('status', 'Aktif')->get(),
        ])->layout('layouts.app');
    }
}
