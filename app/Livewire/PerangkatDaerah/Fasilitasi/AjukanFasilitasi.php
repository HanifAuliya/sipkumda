<?php

namespace App\Livewire\PerangkatDaerah\Fasilitasi;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\FasilitasiProdukHukum;
use App\Models\RancanganProdukHukum;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use App\Notifications\StatusFasilitasiNotification;


class AjukanFasilitasi extends Component
{
    use WithFileUploads;

    public $showModal = false; // Untuk mengontrol modal
    public $rancanganId; // Dropdown rancangan
    public $fileRancangan; // File rancangan yang diupload
    public $rancanganOptions = []; // Daftar rancangan untuk dropdown

    public function mount()
    {
        $this->loadRancanganOptions();
    }

    public function loadRancanganOptions()
    {
        // Rancangan milik pengguna yang sudah disetujui dan belum diajukan ke fasilitasi
        $this->rancanganOptions = RancanganProdukHukum::where('status_rancangan', 'Disetujui')
            ->where('id_user', auth()->id())
            ->whereDoesntHave('fasilitasi')
            ->get(['id_rancangan', 'no_rancangan', 'tentang'])
            ->mapWithKeys(function ($item) {
                return [$item->id_rancangan => "{$item->no_rancangan} - {$item->tentang}"];
            });
    }

    public function submit()
    {
        $this->validate([
            'rancanganId' => 'required|exists:rancangan_produk_hukum,id_rancangan',
            'fileRancangan' => 'required|file|mimes:pdf|max:20480',
        ]);

        // Dikunci per rancangan: klik ganda / dua tab tidak membuat fasilitasi ganda.
        $fasilitasi = Cache::lock("ajukan-fasilitasi-{$this->rancanganId}", 15)->block(10, function () {
            // Hanya rancangan milik pengguna, sudah disetujui, dan belum difasilitasi (sama dengan isi dropdown)
            $this->loadRancanganOptions();
            $rancangan = collect($this->rancanganOptions)->has($this->rancanganId)
                ? RancanganProdukHukum::find($this->rancanganId)
                : null;

            if (! $rancangan) {
                return null;
            }

            $safeNoRancangan = str_replace('/', '-', $rancangan->no_rancangan);
            $safeTentang = str_replace(' ', '_', strtolower($rancangan->tentang));
            $fileName = "{$safeNoRancangan}_{$safeTentang}_fasilitasi_produk_hukum.pdf";

            return FasilitasiProdukHukum::create([
                'rancangan_id' => $rancangan->id_rancangan,
                'tanggal_fasilitasi' => now(),
                'file_rancangan' => $this->fileRancangan->storeAs('fasilitasi/rancangan', $fileName, 'local'),
                'status_berkas_fasilitasi' => 'Menunggu Persetujuan',
                'status_validasi_fasilitasi' => 'Belum Tahap Validasi',
            ]);
        });

        if (! $fasilitasi) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Kesalahan',
                'message' => 'Rancangan tidak ditemukan atau sudah diajukan fasilitasi.',
            ]);
            return;
        }

        // 🔹 Ambil `id_user` dari revisi terakhir yang berelasi dengan rancangan
        $penelitiId = $fasilitasi->rancangan->revisi()->latest()->first()->id_user ?? null;

        // 🔹 Cek apakah user tersebut memiliki role "Peneliti" menggunakan Spatie
        $peneliti = User::where('id', $penelitiId)->whereHas('roles', function ($query) {
            $query->where('name', 'Peneliti');
        })->first();

        // Kirim notifikasi ke Peneliti
        if ($peneliti) {
            Notification::send(
                $peneliti,
                new StatusFasilitasiNotification([
                    'title' => "Pengajuan Fasilitasi untuk Rancangan {$fasilitasi->rancangan->no_rancangan}",
                    'message' => "Fasilitasi telah diajukan. Silahkan lakukan pengecekan dihalaman Perstujuan Fasilitasi.",
                    'slug' => $fasilitasi->rancangan->slug,
                    'type' => 'pengajuan_fasilitasi',
                ])
            );
        }

        // Reset data
        $this->resetFormFasilitasi();

        // 🔄 **Panggil ulang `loadRancanganOptions()` agar data diperbarui setelah pengajuan**
        $this->loadRancanganOptions();

        // Refresh tampilan
        $this->dispatch('refresh');
        $this->dispatch('closeModal', 'modalAjukanFasilitasi');

        // SweetAlert Notification
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Fasilitasi Diajukan!',
            'message' => 'Fasilitasi berhasil diajukan untuk rancangan yang dipilih.',
        ]);
    }


    public function removeFile()
    {
        $this->reset('fileRancangan'); // Reset file yang sudah diunggah
        // Hapus file sementara Livewire
        if ($this->fileRancangan) {
            $this->fileRancangan->delete();
        }
    }

    public function getFormValidProperty()
    {
        return !empty($this->rancanganId) && !empty($this->fileRancangan);
    }



    public function resetError($field)
    {
        // Reset error validasi untuk field tertentu
        $this->resetErrorBag($field);
    }

    public function resetFormFasilitasi()
    {
        // Reset nilai rancanganId
        $this->reset('rancanganId');
        // Hapus file sementara Livewire
        if ($this->fileRancangan) {
            $this->fileRancangan->delete();
        }
        // set null
        $this->fileRancangan = null;

        // Reset error validasi
        $this->resetErrorBag();
        $this->resetValidation();
    }
    public function render()
    {
        return view('livewire.perangkatdaerah.fasilitasi.ajukan-fasilitasi');
    }
}
