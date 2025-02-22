<?php

namespace App\Livewire\PerangkatDaerah\Fasilitasi;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\FasilitasiProdukHukum;
use App\Models\RancanganProdukHukum;
use App\Models\User;
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
        // Ambil ID user yang sedang login
        $userId = auth()->id();

        // Ambil ID rancangan yang sudah diajukan ke fasilitasi
        $rancanganTerfasilitasi = FasilitasiProdukHukum::pluck('rancangan_id')->toArray();

        // Ambil rancangan dengan status_rancangan = 'Disetujui', milik user, dan belum diajukan ke fasilitasi
        $this->rancanganOptions = RancanganProdukHukum::where('status_rancangan', 'Disetujui')
            ->where('id_user', $userId) // Filter hanya rancangan milik pengguna yang login
            ->whereNotIn('id_rancangan', $rancanganTerfasilitasi) // Filter rancangan yang belum difasilitasi
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->id_rancangan => "{$item->no_rancangan} - {$item->tentang}"];
            });
    }

    public function submit()
    {
        $this->validate([
            'rancanganId' => 'required|exists:rancangan_produk_hukum,id_rancangan',
            'fileRancangan' => 'required|file|mimes:pdf|max:2048',
        ]);

        // Simpan fasilitasi
        $fasilitasi = FasilitasiProdukHukum::create([
            'rancangan_id' => $this->rancanganId,
            'tanggal_fasilitasi' => now(),
            'file_rancangan' => $this->fileRancangan->store('fasilitasi/rancangan', 'local'),
            'status_berkas_fasilitasi' => 'Menunggu Persetujuan',
            'status_validasi_fasilitasi' => 'Belum Tahap Validasi',
        ]);

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
                    'message' => "Fasilitasi telah diajukan. Silahkan lakukan pengecekan.",
                    'slug' => $fasilitasi->rancangan->slug,
                    'type' => 'pengajuan_fasilitasi',
                ])
            );
        }

        // Reset data
        $this->resetFormFasilitasi();
        // refresh
        $this->dispatch('refresh');

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
