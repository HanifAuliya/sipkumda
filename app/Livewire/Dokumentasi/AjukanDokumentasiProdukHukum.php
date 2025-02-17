<?php

namespace App\Livewire\Dokumentasi;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\DokumentasiProdukHukum;
use App\Models\FasilitasiProdukHukum;
use App\Models\PerangkatDaerah;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use App\Notifications\DokumentasiNotification;

class AjukanDokumentasiProdukHukum extends Component
{
    use WithFileUploads;

    public $rancanganId;
    public $rancanganList = [];
    public $perangkatList = [];
    public $tahun;
    public $nomor;
    public $fileProdukHukum;
    public $nomorBeritaDaerah;
    public $tanggalBeritaDaerah;

    public $listeners = [
        'loadData' => 'loadData',
    ];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    { // Ambil rancangan yang sudah difasilitasi tapi belum masuk dokumentasi
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
            'nomorBeritaDaerah' => 'required|string',
            'tanggalBeritaDaerah' => 'required|date',
            'fileProdukHukum' => 'required|mimes:pdf|max:5120',
        ]);

        $path = null;
        if ($this->fileProdukHukum) {
            $path = $this->fileProdukHukum->store('dokumentasi/file_produk_hukum', 'local');
        }

        // Ambil rancangan terkait
        $rancangan = FasilitasiProdukHukum::find($this->rancanganId)->rancangan;

        DokumentasiProdukHukum::create([
            'rancangan_id' => $this->rancanganId,
            'nomor' => $this->nomor,
            'tanggal' => now(),
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

        // Kirim Notifikasi ke Semua Verifikator
        $verifikatorUsers = User::role('Verifikator')->get();
        Notification::send($verifikatorUsers, new DokumentasiNotification([
            'title' => "📄 Dokumentasi Produk Hukum Baru",
            'message' => "Dokumentasi produk hukum dengan Nomor {$this->nomor} telah Ditambahkan ke Pengarispan. Silahkan Cek di Halaman Daftar Produk Hukum",
            'type' => 'dokumentasi_dibuat',
            'slug' => $rancangan->slug, // Mengambil slug dari rancangan
        ]));

        $this->dispatch('closeModalTambahDokumentasi');
        $this->dispatch('refreshTable');

        $this->loadData();

        $this->reset();

        // Tampilkan notifikasi sukses
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Status fasilitasi telah diperbarui.',
        ]);
    }

    public function resetForm()
    {
        // Reset semua nilai form
        $this->reset([
            'editId',
            'rancanganId',
            'nomor',
            'nomorBeritaDaerah',
            'tanggalBeritaDaerah',
            'fileProdukHukum',
            'dokumentasi'
        ]);

        // Reset error validation jika ada
        $this->resetValidation();
    }

    public function removeFile()
    {
        if ($this->fileProdukHukum) {
            $this->fileProdukHukum->delete(); // Menghapus dari Livewire temp storage
        }
        $this->reset('fileProdukHukum'); // Mengosongkan variabel agar input aktif kembali
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
