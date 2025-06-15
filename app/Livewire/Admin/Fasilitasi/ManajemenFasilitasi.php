<?php

namespace App\Livewire\Admin\Fasilitasi;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\FasilitasiProdukHukum;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Notifications\StatusFasilitasiNotification;

class ManajemenFasilitasi extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;

    public $fasilitasiId;
    public $selectedFasilitasi;
    public $statusOptions = [];
    public $selectedStatus = '';
    public $confirmUpdate = false;

    protected $listeners = ['refreshTable' => '$refresh'];


    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openUpdateStatus($id)
    {
        $this->fasilitasiId = $id;
        $this->selectedFasilitasi = FasilitasiProdukHukum::findOrFail($id);

        // Ubah ke Laravel Collection agar bisa menggunakan isEmpty()
        $this->statusOptions = collect([
            'status_paraf_koordinasi' => 'Paraf Koordinasi',
            'status_asisten' => 'Asisten',
            'status_sekda' => 'Sekda',
            'status_bupati' => 'Bupati',
        ])->filter(function ($label, $key) {
            return $this->selectedFasilitasi->$key === 'Belum';
        });

        $this->dispatch('openModalUpdateStatus');
    }

    public function refreshModal()
    {
        // Perbarui ulang data statusOptions setelah perubahan
        $this->statusOptions = collect([
            'status_paraf_koordinasi' => 'Paraf Koordinasi',
            'status_asisten' => 'Asisten',
            'status_sekda' => 'Sekda',
            'status_bupati' => 'Bupati',
        ])->filter(function ($label, $key) {
            return $this->selectedFasilitasi->$key === 'Belum';
        });
    }

    public function resetModal()
    {
        // Reset nilai rancanganId
        $this->reset('fasilitasiId');
        $this->reset('selectedStatus');
        $this->reset('statusOptions');
        // set null

        // Reset error validasi
        $this->resetErrorBag();
        $this->resetValidation();
    }


    public function updateStatus()
    {
        // Pastikan checkbox dikonfirmasi sebelum update
        if (!$this->confirmUpdate) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Anda harus mengaktifkan konfirmasi terlebih dahulu.',
            ]);
            return;
        }

        // Pastikan ada fasilitasi yang dipilih
        if (!$this->selectedFasilitasi) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Error!',
                'message' => 'Tidak ada data fasilitasi yang dipilih.',
            ]);
            return;
        }

        // Update status dan tanggal sesuai pilihan
        $statusField = $this->selectedStatus;
        if ($statusField && in_array($statusField, ['status_paraf_koordinasi', 'status_asisten', 'status_sekda', 'status_bupati'])) {
            $this->selectedFasilitasi->update([
                $statusField => 'Selesai',
                'tanggal_' . str_replace('status_', '', $statusField) => now(),
            ]);
        }

        // 🔥 Kirim Notifikasi ke User yang mengajukan fasilitasi
        $user = $this->selectedFasilitasi->rancangan->user;
        if ($user) {
            $user->notify(new StatusFasilitasiNotification([
                'title' => "Status Fasilitasi No. {$this->selectedFasilitasi->rancangan->no_rancangan} Diperbarui!",
                'message' => "Status fasilitasi Anda telah diperbarui, *{$statusField}* Telah Selesai. Silakan cek detailnya pada sistem.",
                'slug' => $this->selectedFasilitasi->rancangan->slug,
                'type' => 'update_fasilitasi',
            ]));
        }

        // 🔥 Kirim Notifikasi ke Verifikator
        $verifikators = User::role('Verifikator')->get();
        if ($verifikators->count()) {
            foreach ($verifikators as $verifikator) {
                $verifikator->notify(new StatusFasilitasiNotification([
                    'title' => " Status Fasilitasi Diperbarui",
                    'message' => "Fasilitasi dengan Nomor Rancangan *{$this->selectedFasilitasi->rancangan->no_rancangan}* telah diperbarui, *{$statusField}* Selesai. Mohon lakukan pengecekan jika diperlukan.",
                    'slug' => $this->selectedFasilitasi->rancangan->slug,
                    'type' => 'update_fasilitasi',
                ]));
            }
        }

        // Reset konfirmasi setelah update
        $this->confirmUpdate = false;

        // Tampilkan notifikasi sukses
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Status fasilitasi telah diperbarui.',
        ]);
        // Reset
        $this->resetModal();
        // Tutup modal dan refresh data
        $this->dispatch('closeModalUpdateStatus');
        $this->dispatch('refreshTable');
    }


    public function render()
    {
        $fasilitasiList = FasilitasiProdukHukum::with('rancangan')
            ->where('status_validasi_fasilitasi', 'Diterima') // Hanya yang sudah diterima
            ->where('status_berkas_fasilitasi', 'Disetujui') // Hanya yang berkasnya disetujui
            ->whereHas('rancangan', function ($query) {
                $query->where('tentang', 'like', "%{$this->search}%")
                    ->orWhere('no_rancangan', 'like', "%{$this->search}%");
            })
            ->orderByDesc('tanggal_validasi_fasilitasi') // Urutkan berdasarkan tanggal terbaru
            ->paginate($this->perPage);

        return view('livewire.admin.fasilitasi.manajemen-fasilitasi', compact('fasilitasiList'))->layout('layouts.app');
    }
}
