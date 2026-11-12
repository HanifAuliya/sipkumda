<?php

namespace App\Livewire\Dokumentasi;

use App\Livewire\Dokumentasi\Concerns\IsiNomorKeputusan;
use App\Models\DokumentasiProdukHukum;
use App\Models\PerangkatDaerah;
use App\Models\User;
use App\Notifications\DokumentasiNotification;
use App\Support\FormatDokumen;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Form tambah dokumentasi untuk produk hukum yang ditetapkan sebelum ada sistem.
 * Produk hukum dari naskah diarsipkan lewat halaman Nota Dinas & Tanda Tangan.
 */
class AjukanDokumentasiProdukHukum extends Component
{
    use IsiNomorKeputusan;
    use WithFileUploads;

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
        'nomor_berita' => 'nomor berita daerah',
        'tahun_berita' => 'tahun berita daerah',
        'file_produk_hukum' => 'file produk hukum',
        'perangkat_daerah_id' => 'perangkat daerah',
        'jenis_dokumentasi' => 'jenis produk hukum',
        'tentang_dokumentasi' => 'tentang',
    ];

    #[On('tambahDokumentasi')]
    public function open()
    {
        $this->authorize('create', DokumentasiProdukHukum::class);

        $this->resetForm();

        $this->dispatch('openModal', 'modalTambahDokumentasi');
    }

    /** Begitu jenisnya Keputusan Bupati, isian nomor berganti ke format dari menu Format Dokumen. */
    private function isiPolaNomor(?string $jenis): void
    {
        $this->siapkanNomor($jenis === FormatDokumen::KEPBUP ? $this->polaKeputusanBawaan() : null);
    }

    public function updatedJenisDokumentasi($jenis): void
    {
        $this->isiPolaNomor($jenis);
    }

    public function store()
    {
        $this->authorize('create', DokumentasiProdukHukum::class);

        $this->tentang_dokumentasi = Str::squish((string) $this->tentang_dokumentasi);

        $this->validate([
            'nomor' => 'required|numeric|digits_between:1,3',
            'tahun' => 'required|numeric|digits:4',
            'nomor_berita' => 'required|numeric|digits_between:1,3',
            'tahun_berita' => 'required|numeric|digits:4',
            'tanggal_penetapan' => 'required|date',
            'file_produk_hukum' => 'required|mimes:pdf|max:20480',
            'perangkat_daerah_id' => 'required|exists:perangkat_daerah,id',
            'jenis_dokumentasi' => 'required|in:Peraturan Bupati,Keputusan Bupati',
            'tentang_dokumentasi' => 'required|string|max:' . DokumentasiProdukHukum::TENTANG_MAKS,
        ]);

        $data = [
            'tahun' => $this->tahun,
            'perangkat_daerah_id' => $this->perangkat_daerah_id,
            'jenis_dokumentasi' => $this->jenis_dokumentasi,
            'tentang_dokumentasi' => $this->tentang_dokumentasi,
        ];

        // Keputusan Bupati bernomor lengkap; jenis lain tetap "Nomor 1 Tahun 2025".
        $pola = $data['jenis_dokumentasi'] === FormatDokumen::KEPBUP ? $this->polaNomorTerisi() : null;

        DokumentasiProdukHukum::denganNomorTerkunci(function () use ($data, $pola) {
            // Cek nomor ganda sebelum menyimpan file: nama file memakai nomor & tahun,
            // jadi file dokumen lain dengan nomor yang sama bisa tertimpa.
            if (DokumentasiProdukHukum::nomorDipakai($this->nomor, $data['tahun'])) {
                throw ValidationException::withMessages(['nomor' => "Nomor {$this->nomor} untuk tahun {$data['tahun']} sudah ada."]);
            }

            $namaFile = str_replace(' ', '_', $data['jenis_dokumentasi'])
                . "_Nomor_{$this->nomor}_Tahun_{$data['tahun']}_Kabupaten_Hulu_Sungai_Tengah.pdf";

            DokumentasiProdukHukum::create($data + [
                'nomor' => $this->nomor,
                'pola_nomor' => $pola,
                'nomor_tahun_berita' => ($this->nomor_berita ?: '-') . '/' . ($this->tahun_berita ?: '-'),
                'tanggal_pengarsipan' => now(),
                'tanggal_penetapan' => $this->tanggal_penetapan,
                'file_produk_hukum' => $this->file_produk_hukum->storeAs('dokumentasi/file_produk_hukum', $namaFile, 'local'),
            ]);
        });

        Notification::send(User::role('Verifikator')->get(), new DokumentasiNotification([
            'title' => 'Dokumentasi Produk Hukum Baru',
            'message' => "Dokumentasi produk hukum Nomor {$this->nomor} Tahun {$data['tahun']} telah ditambahkan ke pengarsipan. Silakan cek di halaman Dokumentasi.",
            'type' => 'dokumentasi_dibuat',
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
            'nomor', 'polaDasar', 'bagianNomor', 'tahun', 'nomor_berita', 'tahun_berita', 'tanggal_penetapan',
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
            'perangkatList' => $bisaTambah ? PerangkatDaerah::orderBy('nama_perangkat_daerah')->get() : collect(),
        ]);
    }
}
