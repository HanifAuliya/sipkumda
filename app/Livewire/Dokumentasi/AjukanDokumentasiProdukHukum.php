<?php

namespace App\Livewire\Dokumentasi;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\DokumentasiProdukHukum;
use App\Models\FasilitasiProdukHukum;
use App\Models\PerangkatDaerah;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class AjukanDokumentasiProdukHukum extends Component
{
    use WithFileUploads;

    public $rancanganId;
    public $rancanganList = [];
    public $perangkatList = [];
    public $tahun;
    public $nomor;
    public $tanggal;
    public $fileProdukHukum;
    public $nomorBeritaDaerah;
    public $tanggalBeritaDaerah;


    public function mount()
    {
        // Ambil rancangan yang sudah difasilitasi tapi belum masuk dokumentasi
        $this->rancanganList = FasilitasiProdukHukum::where('status_validasi_fasilitasi', 'Diterima')
            ->whereDoesntHave('dokumentasi') // Pastikan rancangan belum ada di dokumentasi
            ->with('rancangan')
            ->get();

        // Ambil semua perangkat daerah
        $this->perangkatList = PerangkatDaerah::all();
    }



    public function store()
    {
        // Cek Policy: Hanya Admin dan Verifikator yang bisa menambah dokumentasi
        if (Gate::denies('create', DokumentasiProdukHukum::class)) {
            abort(403, 'Anda tidak memiliki izin untuk menambah dokumentasi.');
        }

        $this->validate([
            'rancanganId' => 'required|exists:fasilitasi_produk_hukum,id',
            'nomor' => 'required|numeric|digits_between:1,3',
            'tanggal' => 'required|date',
            'nomorBeritaDaerah' => 'required|string',
            'tanggalBeritaDaerah' => 'required|date',
            'fileProdukHukum' => 'required|mimes:pdf|max:5120',
        ]);

        $path = null;
        if ($this->fileProdukHukum) {
            $path = $this->fileProdukHukum->store('dokumentasi/file_produk_hukum', 'local');
        }

        DokumentasiProdukHukum::create([
            'rancangan_id' => $this->rancanganId,
            'nomor' => $this->nomor,
            'tanggal' => $this->tanggal,
            'nomor_berita_daerah' => $this->nomorBeritaDaerah,
            'tanggal_berita_daerah' => $this->tanggalBeritaDaerah,
            'file_produk_hukum' => $path,
            'perangkat_daerah_id' => FasilitasiProdukHukum::find($this->rancanganId)->rancangan->user->perangkat_daerah_id,
        ]);

        // Cek apakah ada error dari Model (karena Model menggunakan `session()->flash()`)
        if (session()->has('error_nomor')) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => session('error_nomor'),
            ]);
            return;
        }

        $this->dispatch('closeModalTambahDokumentasi');
        $this->dispatch('refreshTable');

        $this->mount();

        // Tampilkan notifikasi sukses
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Status fasilitasi telah diperbarui.',
        ]);
    }

    public function resetError()
    {
        $this->resetErrorBag('fileProdukHukum'); // Menghapus error jika file berubah
    }


    public function render()
    {
        return view('livewire.dokumentasi.ajukan-dokumentasi-produk-hukum');
    }
}
