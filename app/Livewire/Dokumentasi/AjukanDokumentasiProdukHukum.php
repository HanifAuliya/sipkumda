<?php

namespace App\Livewire\Dokumentasi;

use App\Models\DokumentasiProdukHukum;
use App\Models\FasilitasiProdukHukum;
use App\Models\PerangkatDaerah;
use App\Models\User;
use App\Notifications\DokumentasiNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Form tambah dokumentasi, dua mode:
 * - rancangan : arsip dari fasilitasi yang sudah diterima (jenis/tentang/PD diambil dari rancangan)
 * - manual    : arsip produk hukum sebelum ada sistem
 */
class AjukanDokumentasiProdukHukum extends Component
{
    use WithFileUploads;

    public $mode = 'rancangan';

    public $rancanganId; // id fasilitasi
    public $nomor;
    public $tahun;
    public $nomor_berita;
    public $tahun_berita;
    public $tanggal_penetapan;
    public $file_produk_hukum;
    public $perangkat_daerah_id;
    public $jenis_dokumentasi;
    public $tentang_dokumentasi;

    protected $validationAttributes = [
        'rancanganId' => 'rancangan',
        'nomor_berita' => 'nomor berita daerah',
        'tahun_berita' => 'tahun berita daerah',
        'file_produk_hukum' => 'file produk hukum',
        'perangkat_daerah_id' => 'perangkat daerah',
        'jenis_dokumentasi' => 'jenis produk hukum',
        'tentang_dokumentasi' => 'tentang',
    ];

    /** Fasilitasi yang sudah diterima dan belum diarsipkan. */
    private function fasilitasiTersedia()
    {
        return FasilitasiProdukHukum::where('status_validasi_fasilitasi', 'Diterima')
            ->whereDoesntHave('dokumentasi');
    }

    #[On('tambahDokumentasi')]
    public function open($mode = 'rancangan')
    {
        $this->authorize('create', DokumentasiProdukHukum::class);

        $this->resetForm();
        $this->mode = $mode === 'manual' ? 'manual' : 'rancangan';
        $this->tahun = $this->mode === 'rancangan' ? now()->year : null;

        $this->dispatch('openModal', 'modalTambahDokumentasi');
    }

    // Dipanggil setelah dokumentasi dihapus agar pilihan rancangan diperbarui.
    #[On('loadData')]
    public function loadData()
    {
    }

    public function store()
    {
        $this->authorize('create', DokumentasiProdukHukum::class);

        $manual = $this->mode === 'manual';

        $this->validate([
            'rancanganId' => $manual ? 'nullable' : 'required|integer',
            'nomor' => 'required|numeric|digits_between:1,3',
            'tahun' => $manual ? 'required|numeric|digits:4' : 'nullable',
            'nomor_berita' => ($manual ? 'required' : 'nullable') . '|numeric|digits_between:1,3',
            'tahun_berita' => ($manual ? 'required' : 'nullable') . '|numeric|digits:4',
            'tanggal_penetapan' => 'required|date',
            'file_produk_hukum' => 'required|mimes:pdf|max:20480',
            'perangkat_daerah_id' => $manual ? 'required|exists:perangkat_daerah,id' : 'nullable',
            'jenis_dokumentasi' => $manual ? 'required|in:Peraturan Bupati,Keputusan Bupati' : 'nullable',
            'tentang_dokumentasi' => $manual ? 'required|string' : 'nullable',
        ]);

        if ($manual) {
            $rancangan = null;
            $data = [
                'tahun' => $this->tahun,
                'perangkat_daerah_id' => $this->perangkat_daerah_id,
                'jenis_dokumentasi' => $this->jenis_dokumentasi,
                'tentang_dokumentasi' => $this->tentang_dokumentasi,
            ];
        } else {
            $rancangan = $this->fasilitasiTersedia()->with('rancangan.user')->find($this->rancanganId)?->rancangan;

            if (! $rancangan) {
                $this->addError('rancanganId', 'Rancangan tidak tersedia atau sudah diarsipkan.');
                return;
            }

            $data = [
                'rancangan_id' => $rancangan->id_rancangan,
                'tahun' => now()->year,
                'perangkat_daerah_id' => $rancangan->user?->perangkat_daerah_id,
                'jenis_dokumentasi' => $rancangan->jenis_rancangan,
                'tentang_dokumentasi' => $rancangan->tentang,
            ];
        }

        // Cek nomor ganda sebelum menyimpan file: nama file memakai nomor & tahun,
        // jadi file dokumen lain dengan nomor yang sama bisa tertimpa.
        if (DokumentasiProdukHukum::nomorDipakai($this->nomor, $data['tahun'])) {
            $this->addError('nomor', "Nomor {$this->nomor} untuk tahun {$data['tahun']} sudah ada.");
            return;
        }

        $namaFile = str_replace(' ', '_', $data['jenis_dokumentasi'])
            . "_Nomor_{$this->nomor}_Tahun_{$data['tahun']}_Kabupaten_Hulu_Sungai_Tengah.pdf";

        DokumentasiProdukHukum::create($data + [
            'nomor' => $this->nomor,
            'nomor_tahun_berita' => ($this->nomor_berita ?: '-') . '/' . ($this->tahun_berita ?: '-'),
            'tanggal_pengarsipan' => now(),
            'tanggal_penetapan' => $this->tanggal_penetapan,
            'file_produk_hukum' => $this->file_produk_hukum->storeAs('dokumentasi/file_produk_hukum', $namaFile, 'local'),
        ]);

        Notification::send(User::role('Verifikator')->get(), new DokumentasiNotification([
            'title' => 'Dokumentasi Produk Hukum Baru',
            'message' => "Dokumentasi produk hukum Nomor {$this->nomor} Tahun {$data['tahun']} telah ditambahkan ke pengarsipan. Silakan cek di halaman Daftar Dokumentasi.",
            'type' => 'dokumentasi_dibuat',
            'slug' => $rancangan?->slug,
        ]));

        $this->dispatch('closeModal', 'modalTambahDokumentasi');
        $this->dispatch('refreshTable');
        $this->dispatch('swal:toast', [
            'type' => 'success',
            'message' => 'Dokumentasi berhasil ditambahkan.',
        ]);
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->reset([
            'rancanganId', 'nomor', 'tahun', 'nomor_berita', 'tahun_berita', 'tanggal_penetapan',
            'file_produk_hukum', 'perangkat_daerah_id', 'jenis_dokumentasi', 'tentang_dokumentasi',
        ]);
        $this->resetValidation();
    }

    public function removeFile()
    {
        $this->reset('file_produk_hukum');
        $this->resetValidation('file_produk_hukum');
    }

    public function render()
    {
        $bisaTambah = auth()->user()->can('create', DokumentasiProdukHukum::class);

        return view('livewire.dokumentasi.ajukan-dokumentasi-produk-hukum', [
            'bisaTambah' => $bisaTambah,
            'rancanganList' => $bisaTambah && $this->mode === 'rancangan'
                ? $this->fasilitasiTersedia()->with('rancangan')->get()
                : collect(),
            'perangkatList' => $bisaTambah && $this->mode === 'manual'
                ? PerangkatDaerah::orderBy('nama_perangkat_daerah')->get()
                : collect(),
        ]);
    }
}
