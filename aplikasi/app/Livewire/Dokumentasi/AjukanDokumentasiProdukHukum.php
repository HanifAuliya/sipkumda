<?php

namespace App\Livewire\Dokumentasi;

use App\Enums\TahapNaskah;
use App\Livewire\Dokumentasi\Concerns\IsiNomorKeputusan;
use App\Models\DokumentasiProdukHukum;
use App\Models\Naskah;
use App\Models\PerangkatDaerah;
use App\Models\User;
use App\Notifications\DokumentasiNotification;
use App\Services\AlurNaskah;
use App\Support\FormatDokumen;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Form tambah arsip di halaman Dokumentasi, dua sumber:
 * - sistem : naskah dari SIFKUMDA yang paraf dan tanda tangannya lengkap. Jenis, judul, dan perangkat
 *            daerah diambil dari naskahnya, dan naskah ikut berpindah ke Selesai (AlurNaskah::arsipkan).
 * - lama   : produk hukum yang ditetapkan sebelum ada sistem, semua diisi manual.
 */
class AjukanDokumentasiProdukHukum extends Component
{
    use IsiNomorKeputusan;
    use WithFileUploads;

    /** sistem | lama */
    #[Locked]
    public string $sumber = 'lama';
    public $naskahId;

    public $nomor;
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
        'naskahId' => 'naskah',
    ];

    #[On('tambahDokumentasi')]
    public function open(string $sumber = 'lama', ?int $naskah = null)
    {
        $this->authorize('create', DokumentasiProdukHukum::class);

        $this->resetForm();
        $this->sumber = $sumber === 'sistem' ? 'sistem' : 'lama';

        if ($this->sumber === 'sistem' && $naskah) {
            $this->naskahId = $naskah;
            $this->updatedNaskahId($naskah);
        }

        $this->dispatch('openModal', 'modalTambahDokumentasi');
    }

    /** Naskah yang bisa diarsipkan: paraf dan tanda tangannya lengkap (tahap Arsip). */
    private function naskahSiapArsip()
    {
        return Naskah::terlihatOleh(auth()->user())
            ->where('tahap', TahapNaskah::Arsip)
            ->with('pemrakarsa.perangkatDaerah');
    }

    /** Memilih naskah mengisi jenis, judul, dan perangkat daerah dari naskahnya. */
    public function updatedNaskahId($id): void
    {
        $naskah = $id ? $this->naskahSiapArsip()->find($id) : null;

        $this->jenis_dokumentasi = $naskah?->jenis;
        $this->tentang_dokumentasi = $naskah?->tentang;
        $this->perangkat_daerah_id = $naskah?->pemrakarsa?->perangkat_daerah_id;
        $this->isiPolaNomor($naskah?->jenis);
        $this->resetValidation();
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

    public function store(AlurNaskah $alur)
    {
        $this->authorize('create', DokumentasiProdukHukum::class);

        if ($this->sumber === 'sistem') {
            return $this->arsipkanNaskah($alur);
        }

        $this->tentang_dokumentasi = Str::squish((string) $this->tentang_dokumentasi);

        $this->validate([
            'nomor' => 'required|numeric|digits_between:1,3',
            // Berita Daerah wajib untuk Peraturan Bupati; Keputusan Bupati biasanya tidak dimuat di sana.
            'nomor_berita' => ($this->beritaWajib() ? 'required' : 'nullable') . '|numeric|digits_between:1,3',
            'tahun_berita' => ($this->beritaWajib() ? 'required' : 'nullable') . '|numeric|digits:4',
            'tanggal_penetapan' => 'required|date',
            'file_produk_hukum' => 'required|mimes:pdf|max:20480',
            'perangkat_daerah_id' => 'required|exists:perangkat_daerah,id',
            'jenis_dokumentasi' => 'required|in:Peraturan Bupati,Keputusan Bupati',
            'tentang_dokumentasi' => 'required|string|max:' . DokumentasiProdukHukum::TENTANG_MAKS,
        ]);

        $data = [
            // Tahun mengikuti tanggal penetapan, supaya keduanya tidak bisa berbeda.
            'tahun' => (int) date('Y', strtotime($this->tanggal_penetapan)),
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

    /** Arsip yang terkait naskah: memakai alur naskah yang sama dengan tombol Arsipkan di halaman naskah. */
    private function arsipkanNaskah(AlurNaskah $alur)
    {
        $this->validate([
            'naskahId' => 'required|integer',
            'nomor' => 'required|numeric|digits_between:1,3',
            'nomor_berita' => ($this->beritaWajib() ? 'required' : 'nullable') . '|numeric|digits_between:1,3',
            'tahun_berita' => ($this->beritaWajib() ? 'required' : 'nullable') . '|numeric|digits:4',
            'tanggal_penetapan' => 'required|date',
            'file_produk_hukum' => 'required|mimes:pdf|max:20480',
        ]);

        $naskah = $this->naskahSiapArsip()->find($this->naskahId);

        if (! $naskah) {
            throw ValidationException::withMessages(['naskahId' => 'Naskah ini tidak lagi menunggu arsip. Muat ulang halaman.']);
        }

        $pola = $naskah->jenis === FormatDokumen::KEPBUP ? $this->polaNomorTerisi() : null;

        try {
            $naskah = $alur->arsipkan($naskah, auth()->user(), (string) $this->nomor, $this->tanggal_penetapan,
                $this->nomor_berita ?: null, $this->tahun_berita ?: null, $this->file_produk_hukum, $pola);
        } catch (ValidationException $e) {
            // Nama isian di alur naskah berbeda dengan form ini.
            $peta = ['nomorArsip' => 'nomor', 'tanggalPenetapan' => 'tanggal_penetapan', 'aksi' => 'naskahId'];

            throw ValidationException::withMessages(
                collect($e->errors())->mapWithKeys(fn ($v, $k) => [$peta[$k] ?? $k => $v])->all()
            );
        }

        $this->dispatch('closeModal', 'modalTambahDokumentasi');
        $this->dispatch('refreshTable');
        $this->dispatch('swal:toast', [
            'type' => 'success',
            'message' => "{$naskah->jenis} {$naskah->dokumentasi?->nomor_formatted} diarsipkan dan tampil di JDIH.",
        ]);
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->reset([
            'naskahId', 'sumber',
            'nomor', 'polaDasar', 'bagianNomor', 'nomor_berita', 'tahun_berita', 'tanggal_penetapan',
            'file_produk_hukum', 'perangkat_daerah_id', 'jenis_dokumentasi', 'tentang_dokumentasi',
        ]);
        $this->resetValidation();
    }

    private function beritaWajib(): bool
    {
        return $this->jenis_dokumentasi !== FormatDokumen::KEPBUP;
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
            'beritaWajib' => $this->beritaWajib(),
            'naskahList' => $bisaTambah && $this->sumber === 'sistem' ? $this->naskahSiapArsip()->latest('updated_at')->get() : collect(),
            'naskahTerpilih' => $bisaTambah && $this->sumber === 'sistem' && $this->naskahId
                ? $this->naskahSiapArsip()->find($this->naskahId) : null,
        ]);
    }
}
