<?php

namespace App\Livewire\PerangkatDaerah\Fasilitasi;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use Livewire\WithFileUploads;

use App\Models\User;
use App\Models\FasilitasiProdukHukum;
use App\Models\NotaDinas;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;

use Barryvdh\DomPDF\Facade\Pdf;

use App\Notifications\StatusFasilitasiNotification;


class FasilitasiBerlangsung extends Component
{
    use WithPagination, WithoutUrlPagination;
    use WithFileUploads;


    public $search = ''; // Pencarian
    public $perPage = 10; // Default jumlah item per halaman

    // Upload Ulang
    public $fasilitasiId;
    public $fileRancanganUlang;

    protected $listeners = ['refresh' => 'refreshFasilitasi'];


    public function openUploadUlangRevisi($id)
    {
        // Hanya fasilitasi milik pengguna yang sedang login
        $this->fasilitasiId = FasilitasiProdukHukum::milik(auth()->id())->findOrFail($id)->id;
        $this->reset('fileRancanganUlang'); // Reset input file agar tidak ada file lama

        // Buka modal upload ulang
        $this->dispatch('openModal', 'modalUploadUlangFasilitasi');
    }

    public function uploadUlangRevisi()
    {
        $this->validate([
            'fileRancanganUlang' => 'required|file|mimes:pdf|max:20480',
        ]);

        // Temukan fasilitasi berdasarkan ID
        $fasilitasi = FasilitasiProdukHukum::milik(auth()->id())->findOrFail($this->fasilitasiId);

        // Hapus file lama dari storage jika ada
        if ($fasilitasi->file_rancangan) {
            Storage::delete($fasilitasi->file_rancangan);
        }

        // Simpan file baru
        $path = $this->fileRancanganUlang->store('fasilitasi/rancangan', 'local');

        // Update file di database
        $fasilitasi->update([
            'file_rancangan' => $path,
            'status_berkas_fasilitasi' => 'Menunggu Persetujuan', // Kembali ke status awal
            'status_validasi_fasilitasi' => 'Menunggu Validasi', // Kembali ke status awal
        ]);

        // 🔹 Ambil `id_user` dari revisi terakhir yang berelasi dengan rancangan
        $penelitiId = $fasilitasi->rancangan->revisi()->latest()->first()->id_user ?? null;

        // 🔹 Cek apakah user tersebut memiliki role "Peneliti" menggunakan Spatie
        $peneliti = User::where('id', $penelitiId)->whereHas('roles', function ($query) {
            $query->where('name', 'Peneliti');
        })->first();

        if ($peneliti) Notification::send(
            $peneliti,
            new StatusFasilitasiNotification([
                'title' => "Upload Ulang Berkas Fasilitasi untuk Rancangan {$fasilitasi->rancangan->no_rancangan}",
                'message' => "Berkas fasilitasi telah diperbarui. Silahkan lakukan pengecekan.",
                'slug' => $fasilitasi->rancangan->slug,
                'type' => 'uploadUlang_fasilitasi',
            ])
        );


        // Reset input setelah penyimpanan
        $this->reset(['fileRancanganUlang', 'fasilitasiId']);
        $this->resetErrorBag();
        $this->resetValidation();
        $this->resetPage();

        // Tutup modal dan tampilkan notifikasi sukses
        $this->dispatch('closeModal', 'modalUploadUlangFasilitasi');
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Upload Ulang Berhasil!',
            'message' => 'Berkas fasilitasi berhasil diperbarui.',
        ]);
    }

    public function removeFile($fileField)
    {
        $this->reset($fileField); // Menghapus file yang sudah diunggah
    }
    public function resetError($field)
    {
        $this->resetErrorBag($field);
    }

    public function updatingSearch()
    {
        // Reset halaman ke halaman pertama saat pencarian berubah
        $this->resetPage();
    }
    public function refreshFasilitasi()
    {
        $this->resetPage(); // Reset ke halaman pertama
    }

    public function generatePDF($notaId)
    {
        // Hanya nota dinas dari fasilitasi milik pengguna yang sedang login
        $notaDinas = NotaDinas::with('fasilitasi.rancangan.user.perangkatDaerah', 'tandaTangan')
            ->whereHas('fasilitasi', fn ($q) => $q->milik(auth()->id()))
            ->findOrFail($notaId);
        $pdf = Pdf::loadView('pdf.nota-dinas', compact('notaDinas'))->setPaper('A4', 'portrait');

        // Kirim event ke browser untuk menutup SweetAlert setelah PDF selesai dibuat
        $this->dispatch('hideLoadingSwal');

        // 🔹 Hapus karakter yang tidak valid
        $safeFilename = preg_replace('/[^A-Za-z0-9\-_]/', '-', $notaDinas->nomor_nota);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, "Nota-Dinas-{$safeFilename}.pdf");
    }


    public function render()
    {
        $userId = auth()->id(); // Ambil ID pengguna yang login

        $fasilitasiBerlangsung = FasilitasiProdukHukum::with(['rancangan.user.perangkatDaerah', 'notaDinas'])
            ->whereIn('status_berkas_fasilitasi', ['Menunggu Persetujuan', 'Disetujui', 'Ditolak']) // Menampilkan hanya status ini
            ->whereHas('rancangan', function ($query) use ($userId) {
                $query->where('status_rancangan', 'Disetujui') // Filter hanya rancangan yang disetujui
                    ->where('id_user', $userId) // Hanya rancangan milik pengguna yang login
                    ->where(function ($subQuery) {
                        $subQuery->where('tentang', 'like', "%{$this->search}%")
                            ->orWhere('no_rancangan', 'like', "%{$this->search}%");
                    });
            })
            ->paginate($this->perPage); // Tambahkan pagination

        return view('livewire.perangkatdaerah.fasilitasi.fasilitasi-berlangsung', compact('fasilitasiBerlangsung'));
    }
}
