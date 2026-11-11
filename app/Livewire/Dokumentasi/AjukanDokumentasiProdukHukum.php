<?php

namespace App\Livewire\Dokumentasi;

use App\Models\DokumentasiProdukHukum;
use App\Models\FasilitasiProdukHukum;
use App\Models\PerangkatDaerah;
use App\Models\User;
use App\Notifications\DokumentasiNotification;
use App\Support\FormatDokumen;
use App\Support\Status;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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

    /** Susunan nomor lengkap Keputusan Bupati; terisi dari menu Format Dokumen dan bisa diganti. */
    public $pola_nomor = '';
    public $nomor_berita;
    public $tahun_berita;
    public $tanggal_penetapan;
    public $file_produk_hukum;
    public $perangkat_daerah_id;
    public $jenis_dokumentasi;
    public $tentang_dokumentasi;

    protected $messages = ['pola_nomor.regex' => 'Susunan nomor harus memuat {nomor}.'];

    protected $validationAttributes = [
        'rancanganId' => 'rancangan',
        'nomor_berita' => 'nomor berita daerah',
        'tahun_berita' => 'tahun berita daerah',
        'file_produk_hukum' => 'file produk hukum',
        'perangkat_daerah_id' => 'perangkat daerah',
        'jenis_dokumentasi' => 'jenis produk hukum',
        'pola_nomor' => 'susunan nomor',
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

    /** Keputusan Bupati memakai susunan nomor dari menu Format Dokumen; jenis lain tanpa susunan. */
    private function isiPolaNomor(?string $jenis): void
    {
        $this->pola_nomor = $jenis === FormatDokumen::KEPBUP ? FormatDokumen::naskah(FormatDokumen::KEPBUP)['nomor_arsip'] : '';
    }

    public function updatedJenisDokumentasi($jenis): void
    {
        $this->isiPolaNomor($jenis);
    }

    public function updatedRancanganId($id): void
    {
        $rancangan = $id ? $this->fasilitasiTersedia()->with('rancangan')->find($id)?->rancangan : null;
        $this->isiPolaNomor($rancangan ? Status::label($rancangan->jenis_rancangan) : null);
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

        $this->tentang_dokumentasi = Str::squish((string) $this->tentang_dokumentasi);

        $this->validate([
            'rancanganId' => $manual ? 'nullable' : 'required|integer',
            'nomor' => 'required|numeric|digits_between:1,3',
            'pola_nomor' => ['nullable', 'string', 'max:100', 'regex:/\{nomor\}/'],
            'tahun' => $manual ? 'required|numeric|digits:4' : 'nullable',
            'nomor_berita' => ($manual ? 'required' : 'nullable') . '|numeric|digits_between:1,3',
            'tahun_berita' => ($manual ? 'required' : 'nullable') . '|numeric|digits:4',
            'tanggal_penetapan' => 'required|date',
            'file_produk_hukum' => 'required|mimes:pdf|max:20480',
            'perangkat_daerah_id' => $manual ? 'required|exists:perangkat_daerah,id' : 'nullable',
            'jenis_dokumentasi' => $manual ? 'required|in:Peraturan Bupati,Keputusan Bupati' : 'nullable',
            'tentang_dokumentasi' => $manual ? 'required|string|max:' . DokumentasiProdukHukum::TENTANG_MAKS : 'nullable',
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
                'jenis_dokumentasi' => Status::label($rancangan->jenis_rancangan),
                'tentang_dokumentasi' => $rancangan->tentang,
            ];
        }

        DokumentasiProdukHukum::denganNomorTerkunci(function () use ($data) {
            // Cek nomor ganda sebelum menyimpan file: nama file memakai nomor & tahun,
            // jadi file dokumen lain dengan nomor yang sama bisa tertimpa.
            if (DokumentasiProdukHukum::nomorDipakai($this->nomor, $data['tahun'])) {
                throw ValidationException::withMessages(['nomor' => "Nomor {$this->nomor} untuk tahun {$data['tahun']} sudah ada."]);
            }

            if (isset($data['rancangan_id']) && DokumentasiProdukHukum::where('rancangan_id', $data['rancangan_id'])->exists()) {
                throw ValidationException::withMessages(['rancanganId' => 'Rancangan ini sudah diarsipkan.']);
            }

            $namaFile = str_replace(' ', '_', $data['jenis_dokumentasi'])
                . "_Nomor_{$this->nomor}_Tahun_{$data['tahun']}_Kabupaten_Hulu_Sungai_Tengah.pdf";

            DokumentasiProdukHukum::create($data + [
                'nomor' => $this->nomor,
                // Susunan nomor hanya untuk Keputusan Bupati; jenis lain tetap "Nomor 1 Tahun 2025".
                'pola_nomor' => $data['jenis_dokumentasi'] === FormatDokumen::KEPBUP && filled($this->pola_nomor) ? trim($this->pola_nomor) : null,
                'nomor_tahun_berita' => ($this->nomor_berita ?: '-') . '/' . ($this->tahun_berita ?: '-'),
                'tanggal_pengarsipan' => now(),
                'tanggal_penetapan' => $this->tanggal_penetapan,
                'file_produk_hukum' => $this->file_produk_hukum->storeAs('dokumentasi/file_produk_hukum', $namaFile, 'local'),
            ]);
        });

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
            'rancanganId', 'nomor', 'pola_nomor', 'tahun', 'nomor_berita', 'tahun_berita', 'tanggal_penetapan',
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
