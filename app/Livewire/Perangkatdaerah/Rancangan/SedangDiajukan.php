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

use App\Models\User;

class SedangDiajukan extends Component
{
    use WithPagination, WithoutUrlPagination;
    use WithFileUploads;

    public $search = '';
    public $perPage = 3; // Default: 3 data per halaman

    public $jenisRancangan;
    public $selectedRancanganId;
    public $selectedRancangan;
    public $fileRancangan; // File rancangan
    public $fileMatrik; // File matrik
    public $fileNotaDinas; // File nota dinas
    public $nomorNota; // File bahan pendukung
    public $tanggalNota; // File bahan pendukung
    public $fileBahanPendukung; // File bahan pendukung
    public $hapusBahanPendukung = false;

    protected $listeners = ['rancanganDiperbarui' => 'refreshRancangan'];


    public function openUploadUlangModal($id)
    {
        // Cari data rancangan berdasarkan ID
        $rancangan = RancanganProdukHukum::findOrFail($id);

        // Set properti Livewire dengan data rancangan
        $this->selectedRancanganId = $rancangan->id_rancangan;
        $this->tanggalNota = $rancangan->tanggal_nota;
        $this->nomorNota = $rancangan->nomor_nota;

        // Emit event untuk membuka modal
        // 🔥 Tutup modal setelah reset
        $this->dispatch('openModal', 'uploadUlangBerkasModal');
    }

    // Logika upload ulang berkas
    public function uploadUlangBerkas()
    {
        // Validasi input file
        $this->validate([
            'fileRancangan' => 'required|mimes:doc,docx|max:2048', // File rancangan wajib
            'fileMatrik' => 'required|mimes:doc,docx|max:2048', // File matrik wajib
            'fileNotaDinas' => 'required|mimes:pdf|max:2048', // File nota dinas wajib
            'fileBahanPendukung' => 'nullable|mimes:pdf|max:2048', // File bahan pendukung opsional
            'tanggalNota' => 'required|date', // Tanggal Nota
            'nomorNota' => 'required|string|max:255', // Nomor Nota
        ]);

        // Cari rancangan berdasarkan ID yang dipilih
        $rancangan = RancanganProdukHukum::find($this->selectedRancanganId);

        if (!$rancangan) {
            // Jika rancangan tidak ditemukan, tampilkan error
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Kesalahan',
                'message' => 'Rancangan tidak ditemukan.',
            ]);
            return;
        }

        // Hapus file lama sebelum menyimpan file baru
        if ($rancangan->rancangan) {
            Storage::delete($rancangan->rancangan);
        }
        if ($rancangan->matrik) {
            Storage::delete($rancangan->matrik);
        }
        if ($rancangan->nota_dinas_pd) {
            Storage::delete($rancangan->nota_dinas_pd);
        }

        // Folder utama
        $mainFolder = 'rancangan';

        // Format Nama File Sama dengan Saat Pengajuan Awal
        $filePrefix = str_replace(' ', '_', strtolower($this->jenisRancangan)) . '_' . str_replace('/', '-', $this->nomorNota);

        // Simpan file baru ke dalam folder penyimpanan dengan nama yang sama jika ada file baru
        $rancanganPath = $this->fileRancangan
            ? $this->fileRancangan->storeAs(
                "$mainFolder/rancangan",
                basename($rancangan->rancangan) ?? "{$filePrefix}_rancangan." . $this->fileRancangan->extension(),
                'local'
            )
            : $rancangan->rancangan; // Gunakan file lama jika tidak ada file baru

        $matrikPath = $this->fileMatrik
            ? $this->fileMatrik->storeAs(
                "$mainFolder/matrik",
                basename($rancangan->matrik) ?? "{$filePrefix}_matrik." . $this->fileMatrik->extension(),
                'local'
            )
            : $rancangan->matrik; // Gunakan file lama jika tidak ada file baru

        $notaDinasPath = $this->fileNotaDinas
            ? $this->fileNotaDinas->storeAs(
                "$mainFolder/nota_dinas",
                basename($rancangan->nota_dinas_pd) ?? "{$filePrefix}_nota_dinas.pdf",
                'local'
            )
            : $rancangan->nota_dinas_pd; // Gunakan file lama jika tidak ada file baru

        // Jika checkbox bahan pendukung dicentang, hapus file lama
        if ($this->hapusBahanPendukung) {
            if ($rancangan->bahan_pendukung) {
                Storage::delete($rancangan->bahan_pendukung);
            }
            $bahanPendukungPath = null;
        } else {
            // Jika bahan pendukung baru diunggah, gunakan file baru dengan nama lama
            $bahanPendukungPath = $this->fileBahanPendukung
                ? $this->fileBahanPendukung->storeAs(
                    "$mainFolder/bahan_pendukung",
                    basename($rancangan->bahan_pendukung) ?? "{$filePrefix}_bahan_pendukung." . $this->fileBahanPendukung->extension(),
                    'local'
                )
                : $rancangan->bahan_pendukung; // Gunakan file lama jika tidak ada file baru
        }

        // Update data rancangan di database
        $rancangan->update([
            'rancangan' => $rancanganPath,
            'matrik' => $matrikPath,
            'nota_dinas_pd' => $notaDinasPath,
            'bahan_pendukung' => $bahanPendukungPath,
            'status_berkas' => 'Menunggu Persetujuan',
            'nomor_nota' => $this->nomorNota,
            'tanggal_nota' => $this->tanggalNota,
            'status_berkas' => 'Menunggu Persetujuan',
        ]);

        // Kirim notifikasi ke admin
        Notification::send(
            User::role(['Admin'])->get(),
            new RancanganBaruNotification([
                'title' => 'Pengajuan Ulang Rancangan',
                'message' => 'Rancangan dengan nomor ' . $rancangan->no_rancangan . ' telah diajukan ulang dan menunggu persetujuan. Silahkan periksa dan lakukan Persetujuan',
                'slug' => $rancangan->slug,
                'type' => 'admin_persetujuan',
            ])
        );

        // Emit notifikasi sukses
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Berkas Rancangan berhasil diajukan ulang!',
        ]);

        // Reset form dan tutup modal
        $this->reset(['fileRancangan', 'fileMatrik', 'fileNotaDinas', 'fileBahanPendukung', 'hapusBahanPendukung']);
        // 🔥 Tutup modal setelah reset
        $this->dispatch('closeModal', 'uploadUlangBerkasModal');
    }

    public function resetError($field)
    {
        // Reset error validasi untuk field tertentu
        $this->resetErrorBag($field);
    }

    public function removeFile($fileField)
    {
        $this->reset($fileField); // Menghapus file yang sudah diunggah
    }


    public function resetForm()
    {
        // Hapus file sementara yang diunggah oleh Livewire
        if ($this->fileRancangan) {
            $this->fileRancangan->delete();
        }
        if ($this->fileMatrik) {
            $this->fileMatrik->delete();
        }
        if ($this->fileNotaDinas) {
            $this->fileNotaDinas->delete();
        }
        if ($this->fileBahanPendukung) {
            $this->fileBahanPendukung->delete();
        }

        // Reset semua properti terkait form
        $this->reset(['fileRancangan', 'fileMatrik', 'fileNotaDinas', 'fileBahanPendukung', 'selectedRancanganId']);

        // Hapus semua error validasi
        $this->resetValidation();
    }

    public function resetData()
    {
        $this->reset(['selectedRancanganId']);
    }



    public function refreshRancangan()
    {
        $this->resetPage(); // Reset ke halaman pertama
    }

    public function updatingSearch()
    {
        $this->resetPage(); // Reset ke halaman pertama jika pencarian berubah
    }

    public function updatingPerPage()
    {
        $this->resetPage(); // Reset ke halaman pertama jika jumlah per halaman berubah
    }

    public function render()
    {
        $query = RancanganProdukHukum::with(['user', 'perangkatDaerah', 'revisi'])
            ->where('id_user', auth()->id())
            ->whereIn('status_rancangan', ['Dalam Proses', 'Ditolak'])
            ->orderBy('tanggal_pengajuan', 'desc');
        // Filter pencarian
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('no_rancangan', 'like', '%' . $this->search . '%')
                    ->orWhere('tentang', 'like', '%' . $this->search . '%');
            });
        }

        // Ambil data dengan pagination
        $rancangan = $query->paginate($this->perPage);

        return view('livewire.perangkatdaerah.rancangan.sedang-diajukan', compact('rancangan'));
    }
}
