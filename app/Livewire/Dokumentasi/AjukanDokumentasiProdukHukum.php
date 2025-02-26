<?php

namespace App\Livewire\Dokumentasi;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\DokumentasiProdukHukum;
use App\Models\FasilitasiProdukHukum;
use App\Models\PerangkatDaerah;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use App\Notifications\DokumentasiNotification;

class AjukanDokumentasiProdukHukum extends Component
{
    use WithFileUploads;

    public $rancanganId;
    public $rancanganList = [];
    public $perangkatList = [];
    public $nomor;
    public $tahun;
    public $nomor_berita; // Untuk input nomor (2 digit)
    public $tahun_berita; // Untuk input tahun (4 digit)
    public $file_produk_hukum;
    public $tanggal_pengarsipan;
    public $tanggal_penetapan;
    public $perangkat_daerah_id;
    public $jenis_dokumentasi; // Properti baru
    public $tentang_dokumentasi; // Properti baru

    public $listeners = [
        'loadData' => 'loadData',
    ];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
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

        // Validasi input
        $this->validate([
            'rancanganId' => 'required|exists:fasilitasi_produk_hukum,id',
            'nomor' => 'required|numeric|digits_between:1,3',
            'nomor_berita' => 'required|numeric|digits:2', // Nomor harus 2 digit
            'tahun_berita' => 'required|numeric|digits:4', // Tahun harus 4 digit
            'tanggal_penetapan' => 'required|date',
            'file_produk_hukum' => 'required|mimes:pdf|max:5120',
        ]);

        // Gabungkan nomor_berita dan tahun_berita menjadi nomor_tahun_berita
        $nomor_tahun_berita = "{$this->nomor_berita}/{$this->tahun_berita}";

        // Buat nama file sesuai format yang diinginkan**
        $jenisDokumenFormatted = str_replace(' ', '_', $this->jenis_dokumentasi); // Ganti spasi dengan underscore
        $namaFile = "{$jenisDokumenFormatted}_Nomor_{$this->nomor}_Tahun_{$this->tahun}_Kabupaten_Hulu_Sungai_Tengah.pdf";

        // Simpan file ke storage dengan nama baru**
        $path = $this->file_produk_hukum->storeAs('dokumentasi/file_produk_hukum', $namaFile, 'local');

        // Ambil rancangan terkait
        $rancangan = FasilitasiProdukHukum::find($this->rancanganId)->rancangan;

        // Simpan data ke database
        DokumentasiProdukHukum::create([
            'rancangan_id' => $this->rancanganId,
            'nomor' => now()->year,
            'tahun' => 'required|numeric|digits:4', // Tahun harus 4 digit
            'nomor_tahun_berita' => $nomor_tahun_berita,
            'tanggal_pengarsipan' => now(),
            'tanggal_penetapan' => $this->tanggal_penetapan,
            'file_produk_hukum' => $path,
            'perangkat_daerah_id' => $rancangan->user->perangkat_daerah_id,
            'jenis_dokumentasi' => $rancangan->jenis_rancangan, // Ambil jenis dari rancangan
            'tentang_dokumentasi' => $rancangan->tentang, // Ambil tentang dari rancangan
        ]);

        // Cek data rancangan sebelum simpan
        // dd($rancangan->jenis_rancangan, $rancangan->tentang);

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
            'message' => "Dokumentasi produk hukum dengan Nomor {$this->nomor} telah Ditambahkan ke Pengarsipan. Silahkan Cek di Halaman Daftar Produk Hukum.",
            'type' => 'dokumentasi_dibuat',
            'slug' => $rancangan->slug, // Mengambil slug dari rancangan
        ]));

        // Tutup modal dan refresh tabel
        $this->dispatch('closeModal', 'closeModalTambahDokumentasi');
        $this->dispatch('refreshTable');

        // Reset form
        $this->reset();
        $this->loadData();

        // Tampilkan notifikasi sukses
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Dokumentasi berhasil ditambahkan.',
        ]);
    }

    public function storeManual()
    {
        // Cek Policy: Hanya Admin dan Verifikator yang bisa menambah dokumentasi
        if (Gate::denies('create', DokumentasiProdukHukum::class)) {
            abort(403, 'Anda tidak memiliki izin untuk menambah dokumentasi.');
        }
        // Validasi input
        $this->validate([
            'nomor' => 'required|numeric|digits_between:1,3',
            'tahun' => 'required|numeric|digits:4', // Tahun harus 4 digit
            'nomor_berita' => 'required|numeric|digits:2', // Nomor harus 2 digit
            'tahun_berita' => 'required|numeric|digits:4', // Tahun harus 4 digit
            'tanggal_penetapan' => 'required|date',
            'file_produk_hukum' => 'required|mimes:pdf|max:5120',
            'perangkat_daerah_id' => 'required|exists:perangkat_daerah,id',
            'jenis_dokumentasi' => 'required|string|max:255', // Validasi untuk jenis_dokumentasi
            'tentang_dokumentasi' => 'required|string', // Validasi untuk tentang_dokumentasi
        ]);

        // Cek duplikasi nomor dan tahun
        $existing = DokumentasiProdukHukum::where('tahun', $this->tahun)
            ->where('nomor', $this->nomor)
            ->exists();

        if ($existing) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => "Nomor {$this->nomor} untuk tahun {$this->tahun} sudah ada! Pilih nomor atau tahun lain.",
            ]);
            return;
        }

        // Gabungkan nomor_berita dan tahun_berita menjadi nomor_tahun_berita
        $nomor_tahun_berita = "{$this->nomor_berita}/{$this->tahun_berita}";

        // Buat nama file sesuai format yang diinginkan**
        $jenisDokumenFormatted = str_replace(' ', '_', $this->jenis_dokumentasi); // Ganti spasi dengan underscore
        $namaFile = "{$jenisDokumenFormatted}_Nomor_{$this->nomor}_Tahun_{$this->tahun}_Kabupaten_Hulu_Sungai_Tengah.pdf";

        // Simpan file ke storage dengan nama baru**
        $path = $this->file_produk_hukum->storeAs('dokumentasi/file_produk_hukum', $namaFile, 'local');


        // Simpan data ke database
        DokumentasiProdukHukum::create([
            'nomor' => $this->nomor,
            'tahun' => $this->tahun, // Gunakan tahun yang diinput
            'nomor_tahun_berita' => $nomor_tahun_berita,
            'tanggal_pengarsipan' => now(),
            'tanggal_penetapan' => $this->tanggal_penetapan,
            'file_produk_hukum' => $path,
            'perangkat_daerah_id' => $this->perangkat_daerah_id,
            'jenis_dokumentasi' => $this->jenis_dokumentasi, // Input manual
            'tentang_dokumentasi' => $this->tentang_dokumentasi, // Input manual
        ]);

        // Kirim Notifikasi ke Semua Verifikator
        $verifikatorUsers = User::role('Verifikator')->get();
        Notification::send($verifikatorUsers, new DokumentasiNotification([
            'title' => "📄 Dokumentasi Produk Hukum Baru",
            'message' => "Dokumentasi produk hukum dengan Nomor {$this->nomor} telah Ditambahkan ke Pengarsipan. Silahkan Cek di Halaman Daftar Produk Hukum.",
            'type' => 'dokumentasi_dibuat',
        ]));

        // Tutup modal dan refresh tabel
        $this->dispatch('closeModal', 'modalTambahManual');
        $this->dispatch('refreshTable');

        // Reset form
        $this->reset();
        $this->loadData();

        // Tampilkan notifikasi sukses
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Dokumentasi berhasil ditambahkan secara manual.',
        ]);
    }

    public function resetForm()
    {
        $this->reset([
            'rancanganId',
            'nomor',
            'nomor_berita',
            'tahun_berita',
            'tanggal_pengarsipan',
            'tanggal_penetapan',
            'file_produk_hukum',
        ]);
        $this->resetValidation();
    }

    public function removeFile()
    {
        if ($this->file_produk_hukum) {
            $this->file_produk_hukum->delete(); // Menghapus dari Livewire temp storage
        }
        $this->reset('file_produk_hukum'); // Mengosongkan variabel agar input aktif kembali
    }

    public function resetError()
    {
        $this->resetErrorBag('file_produk_hukum'); // Menghapus error jika file berubah
    }

    public function render()
    {
        return view('livewire.dokumentasi.ajukan-dokumentasi-produk-hukum');
    }
}
