<?php

namespace App\Livewire\Peneliti\Rancangan;

use App\Models\RancanganProdukHukum;
use Livewire\Component;
use App\Models\Revisi;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

use Illuminate\Support\Facades\Log;


class RiwayatRevisi extends Component
{
    use WithFileUploads;
    use WithPagination, WithoutUrlPagination;

    public $search = '';
    public $perPage = 5;
    public $selectedRevisi;

    public $revisiRancangan;
    public $selectedRevisiId;
    public $revisiMatrik;
    public $catatanRevisi;


    public $listeners = [
        'resetRevisiConfirmed' => 'resetRevisi',
    ];

    protected $rules = [
        'revisiRancangan' => 'required|mimes:pdf|max:2048', // Maks 2MB
        'revisiMatrik' => 'required|mimes:pdf|max:2048',    // Maks 2MB
        'catatanRevisi' => 'nullable|string|max:1000',
    ];

    public function loadDetailRevisi($id)
    {
        $this->selectedRevisi = Revisi::with('rancangan.user')->find($id);
        if (!$this->selectedRevisi) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Kesalahan',
                'message' => 'Detail revisi tidak ditemukan.',
            ]);
            return;
        }

        // Emit untuk membuka modal
        $this->dispatch('openDetailRevisiModal');
    }

    public function openUploadRevisi($idRancangan)
    {
        // Cari data revisi terkait
        $this->selectedRevisi = Revisi::where('id_rancangan', $idRancangan)->latest()->first();

        if (!$this->selectedRevisi) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Kesalahan',
                'message' => 'Data revisi tidak ditemukan.',
            ]);
            return;
        }

        // Simpan ID revisi
        $this->selectedRevisiId = $this->selectedRevisi->id_revisi;

        // Reset form dan buka modal
        $this->reset(['revisiRancangan', 'revisiMatrik', 'catatanRevisi']);
        $this->dispatch('openModal', 'uploadRevisiModal');
    }



    public function uploadUlangRevisi()
    {
        $this->validate();

        // Pastikan ID revisi ada
        if (!$this->selectedRevisiId) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Kesalahan',
                'message' => 'ID revisi tidak ditemukan.',
            ]);
            return;
        }

        // Cari revisi berdasarkan ID
        $revisi = Revisi::find($this->selectedRevisiId);

        if (!$revisi) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Kesalahan',
                'message' => 'Data revisi tidak ditemukan.',
            ]);
            return;
        }

        // Hapus file lama jika ada
        if ($revisi->revisi_rancangan) {
            Storage::disk('local')->delete($revisi->revisi_rancangan);
        }
        if ($revisi->revisi_matrik) {
            Storage::disk('local')->delete($revisi->revisi_matrik);
        }

        // Simpan file baru ke storage
        $revisiRancanganPath = $this->revisiRancangan->store('revisi/rancangan', 'local');
        $revisiMatrikPath = $this->revisiMatrik->store('revisi/matrik', 'local');

        // Perbarui data revisi di database
        $revisi->update([
            'revisi_rancangan' => $revisiRancanganPath,
            'revisi_matrik' => $revisiMatrikPath,
            'catatan_revisi' => $this->catatanRevisi,
            'status_validasi' => 'Menunggu Validasi',
            'tanggal_revisi' => now(),
        ]);

        // Emit notifikasi sukses
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'message' => 'Revisi berhasil diunggah ulang dan menunggu validasi.',
        ]);

        // Reset form dan tutup modal
        $this->resetForm();
        $this->dispatch('closeModal', 'uploadRevisiModal');
    }



    public function resetRevisi($id)
    {
        $revisi = Revisi::findOrFail($id);

        // Hapus file dari storage jika ada
        if ($revisi->revisi_rancangan) {
            Storage::disk('local')->delete($revisi->revisi_rancangan);
        }

        if ($revisi->revisi_matrik) {
            Storage::disk('local')->delete($revisi->revisi_matrik);
        }

        // Reset data revisi
        $revisi->update([
            'revisi_rancangan' => null,
            'revisi_matrik' => null,
            'catatan_revisi' => null,
            'tanggal_revisi' => null,
            'status_validasi' => 'Belum Tahap Validasi',
        ]);

        $this->dispatch('swal:reset', [
            'type' => 'success',
            'title' => 'Revisi Direset',
            'message' => 'Revisi berhasil direset dan file dihapus dari storage.',
        ]);
    }

    public function resetForm()
    {
        // Hapus file sementara Livewire
        if ($this->revisiRancangan) {
            $this->revisiRancangan->delete();
        }
        if ($this->revisiMatrik) {
            $this->revisiMatrik->delete();
        }
        $this->reset(['revisiRancangan', 'revisiMatrik', 'catatanRevisi', 'selectedRevisi']);
        $this->resetValidation();
    }

    public function render()
    {

        // Dapatkan ID user yang sedang login
        $penelitiId = Auth::id();

        $revisi = RancanganProdukHukum::with(['revisi.peneliti', 'user'])
            ->whereHas('revisi', function ($query) use ($penelitiId) {
                $query->where('status_validasi', 'Menunggu Validasi')
                    ->where('status_revisi', 'Proses Revisi')
                    ->where('status_rancangan', 'Dalam Proses')
                    ->where('id_user', $penelitiId); // Filter berdasarkan peneliti yang sedang login
            })
            ->where(function ($query) {
                $query->where('id_rancangan', 'like', "%{$this->search}%")
                    ->orWhere('tentang', 'like', "%{$this->search}%");
            })
            ->orderByDesc(
                Revisi::select('tanggal_revisi')
                    ->whereColumn('rancangan_produk_hukum.id_rancangan', 'revisi.id_rancangan')
                    ->latest('tanggal_revisi')
            )
            ->paginate($this->perPage);


        return view('livewire.peneliti.rancangan.riwayat-revisi', compact('revisi'));
    }
}
