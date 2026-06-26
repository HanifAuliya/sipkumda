<?php

namespace App\Livewire\PerangkatDaerah\Rancangan;

use Livewire\Component;
use App\Models\RancanganProdukHukum;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use App\Notifications\RancanganBaruNotification;
// use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;

use App\Services\KelengkapanBerkasChecker;

class SedangDiajukan extends Component
{
    use WithPagination, WithoutUrlPagination;
    use WithFileUploads;

    public $search   = '';
    public $perPage  = 3;

    public $jenisRancangan;
    public $selectedRancanganId;
    public $selectedRancangan;
    public $fileRancangan;
    public $fileMatrik;
    public $fileNotaDinas;
    public $nomorNota;
    public $tanggalNota;
    public $fileBahanPendukung;
    public $hapusBahanPendukung = false;

    // === Properti hasil prediksi AI (untuk upload ulang) ===
    public $hasilPrediksiUlang  = null;  // 'Lengkap' | 'Tidak Lengkap'
    public $catatanKurangUlang  = [];
    public $sudahDicekUlang     = false;
    public $gagalCekUlang       = false;

    protected $listeners = ['rancanganDiperbarui' => 'refreshRancangan'];

    // =========================================================
    // Listener: otomatis cek AI jika 3 file utama sudah terupload
    // =========================================================
    public function updatedFileRancangan()
    {
        $this->resetHasilCekUlang();
        $this->cekOtomatisJikaLengkap();
    }
    public function updatedFileMatrik()
    {
        $this->resetHasilCekUlang();
        $this->cekOtomatisJikaLengkap();
    }
    public function updatedFileNotaDinas()
    {
        $this->resetHasilCekUlang();
        $this->cekOtomatisJikaLengkap();
    }

    private function cekOtomatisJikaLengkap()
    {
        if ($this->fileRancangan && $this->fileMatrik && $this->fileNotaDinas) {
            $this->cekKelengkapanUlang();
        }
    }

    private function resetHasilCekUlang()
    {
        $this->hasilPrediksiUlang = null;
        $this->catatanKurangUlang = [];
        $this->sudahDicekUlang    = false;
        $this->gagalCekUlang      = false;
    }

    // =========================================================
    // Cek AI menggunakan file temporary Livewire (sebelum disimpan)
    // =========================================================
    public function cekKelengkapanUlang()
    {
        if (!$this->fileRancangan || !$this->fileMatrik || !$this->fileNotaDinas) return;

        try {
            $checker = new KelengkapanBerkasChecker();
            $hasil   = $checker->check($this->fileNotaDinas, $this->fileRancangan, $this->fileMatrik);

            $this->hasilPrediksiUlang = $hasil['hasil'];
            $this->catatanKurangUlang = $hasil['catatan'];
            $this->sudahDicekUlang    = true;
            $this->gagalCekUlang      = false;
        } catch (\Exception $e) {
            Log::error('Gagal cek kelengkapan ulang: ' . $e->getMessage());
            $this->gagalCekUlang   = true;
            $this->sudahDicekUlang = false;
        }
    }

    public function openUploadUlangModal($id)
    {
        $rancangan = RancanganProdukHukum::findOrFail($id);
        $this->selectedRancanganId = $rancangan->id_rancangan;
        $this->tanggalNota         = $rancangan->tanggal_nota;
        $this->nomorNota           = $rancangan->nomor_nota;
        $this->resetHasilCekUlang();
        $this->dispatch('openModal', 'uploadUlangBerkasModal');
    }

    // =========================================================
    // Upload ulang + prediksi AI setelah file disimpan
    // =========================================================
    public function uploadUlangBerkas()
    {
        $this->validate([
            'fileRancangan'      => 'required|mimes:doc,docx|max:20480',
            'fileMatrik'         => 'required|mimes:doc,docx|max:20480',
            'fileNotaDinas'      => 'required|mimes:pdf|max:20480',
            'fileBahanPendukung' => 'nullable|mimes:pdf|max:20480',
            'tanggalNota'        => 'required|date',
            'nomorNota'          => 'required|string|max:255',
        ]);

        $rancangan = RancanganProdukHukum::find($this->selectedRancanganId);

        if (!$rancangan) {
            $this->dispatch('swal:modal', [
                'type'    => 'error',
                'title'   => 'Kesalahan',
                'message' => 'Rancangan tidak ditemukan.',
            ]);
            return;
        }

        if ($rancangan->rancangan)     Storage::delete($rancangan->rancangan);
        if ($rancangan->matrik)        Storage::delete($rancangan->matrik);
        if ($rancangan->nota_dinas_pd) Storage::delete($rancangan->nota_dinas_pd);

        $mainFolder = 'rancangan';
        $filePrefix = str_replace(' ', '_', strtolower($rancangan->jenis_rancangan))
            . '_' . str_replace('/', '-', $this->nomorNota);

        $rancanganPath = $this->fileRancangan
            ? $this->fileRancangan->storeAs(
                "$mainFolder/rancangan",
                basename($rancangan->rancangan) ?? "{$filePrefix}_rancangan." . $this->fileRancangan->extension(),
                'local'
            )
            : $rancangan->rancangan;

        $matrikPath = $this->fileMatrik
            ? $this->fileMatrik->storeAs(
                "$mainFolder/matrik",
                basename($rancangan->matrik) ?? "{$filePrefix}_matrik." . $this->fileMatrik->extension(),
                'local'
            )
            : $rancangan->matrik;

        $notaDinasPath = $this->fileNotaDinas
            ? $this->fileNotaDinas->storeAs(
                "$mainFolder/nota_dinas",
                basename($rancangan->nota_dinas_pd) ?? "{$filePrefix}_nota_dinas.pdf",
                'local'
            )
            : $rancangan->nota_dinas_pd;

        if ($this->hapusBahanPendukung) {
            if ($rancangan->bahan_pendukung) Storage::delete($rancangan->bahan_pendukung);
            $bahanPendukungPath = null;
        } else {
            $bahanPendukungPath = $this->fileBahanPendukung
                ? $this->fileBahanPendukung->storeAs(
                    "$mainFolder/bahan_pendukung",
                    basename($rancangan->bahan_pendukung) ?? "{$filePrefix}_bahan_pendukung." . $this->fileBahanPendukung->extension(),
                    'local'
                )
                : $rancangan->bahan_pendukung;
        }

        // Gunakan hasil prediksi yang sudah dicek sebelum submit (dari cekKelengkapanUlang)
        $catatanAI = ($this->hasilPrediksiUlang === 'Tidak Lengkap' && count($this->catatanKurangUlang) > 0)
            ? implode('; ', $this->catatanKurangUlang)
            : null;

        $rancangan->update([
            'rancangan'                  => $rancanganPath,
            'matrik'                     => $matrikPath,
            'nota_dinas_pd'              => $notaDinasPath,
            'bahan_pendukung'            => $bahanPendukungPath,
            'status_berkas'              => 'Menunggu Persetujuan',
            'nomor_nota'                 => $this->nomorNota,
            'tanggal_nota'               => $this->tanggalNota,
            'hasil_prediksi_kelengkapan' => $this->hasilPrediksiUlang,
            'catatan_berkas'             => $catatanAI,
        ]);

        // Kirim notifikasi ke admin
        Notification::send(
            User::role(['Admin'])->get(),
            new RancanganBaruNotification([
                'title'   => 'Pengajuan Ulang Rancangan',
                'message' => 'Rancangan dengan nomor ' . $rancangan->no_rancangan . ' telah diajukan ulang dan menunggu persetujuan. Silahkan periksa dan lakukan Persetujuan',
                'slug'    => $rancangan->slug,
                'type'    => 'admin_persetujuan',
            ])
        );

        $this->dispatch('swal:modal', [
            'type'    => 'success',
            'title'   => 'Berhasil!',
            'message' => 'Berkas Rancangan berhasil diajukan ulang!',
        ]);

        $this->reset(['fileRancangan', 'fileMatrik', 'fileNotaDinas', 'fileBahanPendukung', 'hapusBahanPendukung']);
        $this->resetHasilCekUlang();
        $this->dispatch('closeModal', 'uploadUlangBerkasModal');
    }

    public function resetError($field)
    {
        $this->resetErrorBag($field);
    }

    public function removeFile($fileField)
    {
        $this->reset($fileField);
        $this->resetHasilCekUlang();
    }

    public function resetForm()
    {
        if ($this->fileRancangan)     $this->fileRancangan->delete();
        if ($this->fileMatrik)        $this->fileMatrik->delete();
        if ($this->fileNotaDinas)     $this->fileNotaDinas->delete();
        if ($this->fileBahanPendukung) $this->fileBahanPendukung->delete();

        $this->reset(['fileRancangan', 'fileMatrik', 'fileNotaDinas', 'fileBahanPendukung', 'selectedRancanganId']);
        $this->resetHasilCekUlang();
        $this->resetValidation();
    }

    public function resetData()
    {
        $this->reset(['selectedRancanganId']);
    }

    public function refreshRancangan()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = RancanganProdukHukum::with(['user', 'perangkatDaerah', 'revisi'])
            ->where('id_user', auth()->id())
            ->whereIn('status_rancangan', ['Dalam Proses', 'Ditolak'])
            ->orderBy('tanggal_pengajuan', 'desc');

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('no_rancangan', 'like', '%' . $this->search . '%')
                    ->orWhere('tentang', 'like', '%' . $this->search . '%');
            });
        }

        $rancangan = $query->paginate($this->perPage);

        return view('livewire.perangkatdaerah.rancangan.sedang-diajukan', compact('rancangan'));
    }
}
