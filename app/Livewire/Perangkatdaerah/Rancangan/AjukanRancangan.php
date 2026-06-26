<?php

namespace App\Livewire\PerangkatDaerah\Rancangan;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\RancanganProdukHukum;
use Illuminate\Support\Facades\Notification;
use App\Notifications\RancanganBaruNotification;
use App\Models\User;
use App\Models\Revisi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Prediksi ML — hit Flask API /extract lalu /predict
use App\Services\KelengkapanBerkasChecker;


class AjukanRancangan extends Component
{
    use WithFileUploads;

    public $jenisRancangan;
    public $tentang;
    public $rancangan;
    public $matrik;
    public $nota_dinas_pd;
    public $bahanPendukung;
    public $tanggalNota;
    public $nomorNota;

    // === Properti hasil prediksi ML ===
    public $hasilPrediksi  = null;  // 'Lengkap' | 'Tidak Lengkap'
    public $catatanKurang  = [];    // daftar kekurangan
    public $sudahDicek     = false; // sudah pernah dicek
    public $gagalCek       = false; // API tidak bisa dihubungi

    protected $rules = [
        'jenisRancangan' => 'required',
        'tentang' => 'required|string|max:255',
        'rancangan' => 'required|mimes:doc,docx|max:20480', // Hanya format Word
        'matrik' => 'required|mimes:doc,docx|max:20480', // Hanya format Word
        'nota_dinas_pd' => 'required|mimes:pdf|max:20480', // PDF
        'bahanPendukung' => 'nullable|mimes:pdf|max:20480', // PDF opsional
        'tanggalNota' => 'required|date',
        'nomorNota' => 'required|string|unique:rancangan_produk_hukum,nomor_nota|max:50',
    ];

    // Listener: otomatis cek jika 3 file utama sudah terupload
    public function updatedRancangan()
    {
        $this->resetHasilCek();
        $this->cekOtomatisJikaLengkap();
    }
    public function updatedMatrik()
    {
        $this->resetHasilCek();
        $this->cekOtomatisJikaLengkap();
    }
    public function updatedNotaDinasPd()
    {
        $this->resetHasilCek();
        $this->cekOtomatisJikaLengkap();
    }

    private function cekOtomatisJikaLengkap()
    {
        if ($this->rancangan && $this->matrik && $this->nota_dinas_pd) {
            $this->jalankanPrediksi();
        }
    }

    private function resetHasilCek()
    {
        $this->hasilPrediksi = null;
        $this->catatanKurang = [];
        $this->sudahDicek    = false;
        $this->gagalCek      = false;
    }



    public function jalankanPrediksi()
    {
        if (!$this->rancangan || !$this->matrik || !$this->nota_dinas_pd) return;

        try {
            $checker = new KelengkapanBerkasChecker();
            $hasil   = $checker->check($this->nota_dinas_pd, $this->rancangan, $this->matrik);

            $this->hasilPrediksi = $hasil['hasil'];
            $this->catatanKurang = $hasil['catatan'];
            $this->sudahDicek    = true;
            $this->gagalCek      = false;
        } catch (\Exception $e) {
            Log::error('Gagal cek kelengkapan: ' . $e->getMessage());
            $this->gagalCek   = true;
            $this->sudahDicek = false;
        }
    }

    // =========================================================
    // Submit Pengajuan
    // =========================================================
    public function submit()
    {
        $this->validate();

        $mainFolder = 'rancangan';
        $filePrefix = str_replace(' ', '_', strtolower($this->jenisRancangan))
            . '_' . str_replace('/', '-', $this->nomorNota);

        $rancanganPath      = $this->rancangan->storeAs("$mainFolder/rancangan",      "{$filePrefix}_rancangan."     . $this->rancangan->extension(), 'local');
        $matrikPath         = $this->matrik->storeAs("$mainFolder/matrik",            "{$filePrefix}_matrik."        . $this->matrik->extension(),    'local');
        $notaDinasPath      = $this->nota_dinas_pd->storeAs("$mainFolder/nota_dinas", "{$filePrefix}_nota_dinas.pdf",                                 'local');
        $bahanPendukungPath = $this->bahanPendukung
            ? $this->bahanPendukung->storeAs("$mainFolder/bahan_pendukung", "{$filePrefix}_bahan_pendukung.pdf", 'local')
            : null;

        // Hasil prediksi langsung ikut disimpan
        $catatan = ($this->hasilPrediksi === 'Tidak Lengkap' && count($this->catatanKurang) > 0)
            ? implode('; ', $this->catatanKurang)
            : null;

        $rancangan = RancanganProdukHukum::create([
            'id_user'                    => auth()->id(),
            'jenis_rancangan'            => $this->jenisRancangan,
            'tentang'                    => $this->tentang,
            'rancangan'                  => $rancanganPath,
            'matrik'                     => $matrikPath,
            'nota_dinas_pd'              => $notaDinasPath,
            'bahan_pendukung'            => $bahanPendukungPath,
            'status_berkas'              => 'Menunggu Persetujuan',
            'status_rancangan'           => 'Dalam Proses',
            'tanggal_pengajuan'          => now(),
            'tanggal_nota'               => $this->tanggalNota,
            'nomor_nota'                 => $this->nomorNota,
            'hasil_prediksi_kelengkapan' => $this->hasilPrediksi, // tersimpan otomatis
            'catatan_berkas'             => $catatan,             // tersimpan otomatis
        ]);

        Revisi::create([
            'id_rancangan'    => $rancangan->id_rancangan,
            'status_revisi'   => 'Belum Tahap Revisi',
            'status_validasi' => 'Belum Tahap Validasi',
            'catatan_revisi'  => null,
        ]);

        Notification::send(User::role(['Admin'])->get(), new RancanganBaruNotification([
            'title'   => 'Rancangan Menunggu Persetujuan Berkas',
            'message' => auth()->user()->nama_user . ' telah menambahkan rancangan baru dengan nomor '
                . $rancangan->no_rancangan . '. Silahkan periksa dan lakukan verifikasi berkas.',
            'slug'    => $rancangan->slug,
            'type'    => 'admin_persetujuan',
        ]));

        Notification::send(User::role(['Verifikator'])->get(), new RancanganBaruNotification([
            'title'   => 'Rancangan Baru Telah Ditambahkan',
            'message' => auth()->user()->nama_user . ' telah menambahkan rancangan baru dengan nomor '
                . $rancangan->nomor_nota . '.',
            'slug'    => $rancangan->slug,
            'type'    => 'verifikator_detail',
        ]));

        $this->dispatch('refreshNotifications');
        $this->dispatch('rancanganDiperbarui');
        $this->reset();

        $this->dispatch('swal:modal', [
            'type'    => 'success',
            'title'   => 'Berhasil!',
            'message' => 'Rancangan berhasil ditambahkan!',
        ]);

        $this->dispatch('closeModal', 'ajukanRancanganModal');
    }

    public function removeFile($fileField)
    {
        $this->reset($fileField);
        $this->resetHasilCek();
    }

    public function resetForm()
    {
        foreach (['rancangan', 'matrik', 'nota_dinas_pd', 'bahanPendukung'] as $f) {
            if ($this->$f) $this->$f->delete();
        }
        $this->reset();
    }

    public function resetError($field)
    {
        $this->resetErrorBag($field);
    }

    public function render()
    {
        return view('livewire.perangkatdaerah.rancangan.ajukan-rancangan');
    }
}
