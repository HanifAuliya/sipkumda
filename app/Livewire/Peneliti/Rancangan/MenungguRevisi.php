<?php

namespace App\Livewire\Peneliti\Rancangan;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use App\Models\Revisi;
use App\Models\User;
use App\Models\RancanganProdukHukum;

use App\Notifications\RevisiValidationNotification;
use Illuminate\Support\Facades\Auth;


class MenungguRevisi extends Component
{
    use WithFileUploads;
    use WithPagination, WithoutUrlPagination;

    public $search = '';
    public $perPage = 5;

    public $selectedRevisi;
    public $revisiRancangan, $revisiMatrik, $catatanRevisi, $selectedRevisiId;

    protected $listeners = ['refreshPage' => 'refreshRancangan'];
    public function refreshRancangan()
    {
        $this->resetPage(); // Reset ke halaman pertama
    }

    protected $rules = [
        'revisiRancangan' => 'required|file|max:10240|mimes:pdf',
        'revisiMatrik' => 'required|file|max:10240|mimes:pdf',
        'catatanRevisi' => 'required|string|max:1000',
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
    public function selectRevisi($id)
    {
        // Pastikan ID revisi valid
        $revisi = Revisi::find($id);

        if (!$revisi) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Kesalahan',
                'message' => 'Revisi tidak ditemukan.',
            ]);
            return;
        }

        $this->selectedRevisiId = $id;

        // Emit event untuk membuka modal
        $this->dispatch('openUploadRevisiModal');
    }


    public function uploadRevisi()
    {
        $this->validate();

        $revisi = Revisi::find($this->selectedRevisiId);
        if (!$revisi) {
            session()->flash('error', 'Data revisi tidak ditemukan.');
            return;
        }

        // Simpan file ke storage private
        $revisiRancanganPath = $this->revisiRancangan->store('revisi/rancangan', 'local');
        $revisiMatrikPath = $this->revisiMatrik->store('revisi/matrik', 'local');

        $revisi->update([
            'revisi_rancangan' => $revisiRancanganPath,
            'revisi_matrik' => $revisiMatrikPath,
            'catatan_revisi' => $this->catatanRevisi,
            'status_validasi' => 'Menunggu Validasi',
            'tanggal_revisi' => now(),
        ]);

        // Kirim notifikasi ke user yang mengajukan rancangan
        $user = $revisi->rancangan->user;
        $user->notify(new RevisiValidationNotification([
            'title' => 'Revisi Rancangan anda dengan nonmor' . $revisi->rancangan->no_rancangan . " Telah dikirm",
            'message' => 'Revisi untuk rancangan Anda telah berhasil diunggah dan menunggu validasi. Silahkan tunggu informasi selanjutnya',
            'slug' => $revisi->rancangan->slug,
            'type' => 'revisi_dikirim'
        ]));

        // Kirim notifikasi ke semua verifikator
        $verifikators = User::role('verifikator')->get();
        foreach ($verifikators as $verifikator) {
            $verifikator->notify(new RevisiValidationNotification([
                'title' => "Revisi Rancangan " . $revisi->rancangan->no_rancangan . " Baru Menunggu Validasi ",
                'message' => 'Rancangan tentang ' . $revisi->rancangan->tentang . "menunggu validasi, Silahkan Periksa dan Validasi",
                'slug' => $revisi->rancangan->slug,
                'type' => 'validasi_revisi'
            ]));
        }

        // Emit notifikasi sukses ke pengguna
        $this->dispatch('refreshNotifications');

        $this->dispatch('swal:modal', [
            'type' => 'success',
            'message' => 'Revisi berhasil diunggah dan dikirim untuk validasi.',
        ]);

        $this->dispatch('closeUploadRevisiModal');

        $this->resetForm();
    }



    public function resetForm()
    {
        // Hapus file revisi rancangan jika ada
        if ($this->revisiRancangan) {
            $this->revisiRancangan->delete();
        }

        // Hapus file revisi matrik jika ada
        if ($this->revisiMatrik) {
            $this->revisiMatrik->delete();
        }

        // Reset semua properti form
        $this->revisiRancangan = null;
        $this->revisiMatrik = null;
        $this->catatanRevisi = null;

        // Reset error validasi
        $this->resetErrorBag();
        $this->resetValidation();
    }

    // Query di Livewire render()
    public function render()
    {
        // Dapatkan ID user yang sedang login
        $penelitiId = Auth::id();

        // Query hanya rancangan dengan revisi milik peneliti yang sedang login
        $revisi = RancanganProdukHukum::with(['revisi.peneliti', 'user'])
            ->whereHas('revisi', function ($query) use ($penelitiId) {
                $query->where('status_revisi', 'Proses Revisi')
                    ->where('status_validasi', 'Belum Tahap Validasi')
                    ->where('status_rancangan', 'Dalam Proses')
                    ->where('id_user', $penelitiId); // Filter berdasarkan peneliti yang sedang login
            })
            ->where(function ($query) {
                $query->where('id_rancangan', 'like', "%{$this->search}%")
                    ->orWhere('tentang', 'like', "%{$this->search}%");
            })
            ->orderByDesc(
                Revisi::select('tanggal_peneliti_ditunjuk')
                    ->whereColumn('rancangan_produk_hukum.id_rancangan', 'revisi.id_rancangan')
                    ->latest('tanggal_peneliti_ditunjuk')
            )
            ->paginate($this->perPage);

        return view('livewire.peneliti.rancangan.menunggu-revisi', compact('revisi'));
    }
}
