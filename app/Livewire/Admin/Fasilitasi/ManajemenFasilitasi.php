<?php

namespace App\Livewire\Admin\Fasilitasi;

use App\Models\FasilitasiProdukHukum;
use App\Models\User;
use App\Notifications\StatusFasilitasiNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;

class ManajemenFasilitasi extends Component
{
    use WithPagination, WithoutUrlPagination;

    /** Kolom status tahapan paraf => label. */
    private const TAHAPAN = [
        'status_paraf_koordinasi' => 'Paraf Koordinasi',
        'status_asisten' => 'Asisten',
        'status_sekda' => 'Sekda',
        'status_bupati' => 'Bupati',
    ];

    public $search = '';
    public $perPage = 10;

    public $selectedFasilitasi;
    public $selectedStatus = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    /** Tahapan yang belum selesai pada fasilitasi terpilih. */
    private function tahapanTersisa(): array
    {
        return $this->selectedFasilitasi
            ? array_filter(self::TAHAPAN, fn ($kolom) => $this->selectedFasilitasi->$kolom !== 'Selesai', ARRAY_FILTER_USE_KEY)
            : [];
    }

    public function openUpdateStatus($id)
    {
        $this->selectedFasilitasi = FasilitasiProdukHukum::with('rancangan.user.perangkatDaerah')->findOrFail($id);
        $this->authorize('update', $this->selectedFasilitasi);

        // Pilih otomatis tahapan berikutnya yang belum selesai.
        $this->selectedStatus = array_key_first($this->tahapanTersisa()) ?? '';
        $this->resetValidation();

        $this->dispatch('openModal', 'modalUpdateStatus');
    }

    public function resetModal()
    {
        $this->reset(['selectedFasilitasi', 'selectedStatus']);
        $this->resetValidation();
    }

    public function updateStatus()
    {
        if (! $this->selectedFasilitasi) {
            return;
        }

        $this->authorize('update', $this->selectedFasilitasi);

        $tersisa = $this->tahapanTersisa();
        $this->validate(
            ['selectedStatus' => 'required|in:' . implode(',', array_keys($tersisa))],
            ['selectedStatus.*' => 'Pilih tahapan yang belum selesai.']
        );

        $label = $tersisa[$this->selectedStatus];
        $rancangan = $this->selectedFasilitasi->rancangan;

        $this->selectedFasilitasi->update([
            $this->selectedStatus => 'Selesai',
            'tanggal_' . str_replace('status_', '', $this->selectedStatus) => now(),
        ]);

        $rancangan->user?->notify(new StatusFasilitasiNotification([
            'title' => "Status Fasilitasi No. {$rancangan->no_rancangan} Diperbarui!",
            'message' => "Status fasilitasi Anda telah diperbarui, tahap {$label} telah selesai. Silakan cek detailnya pada sistem.",
            'slug' => $rancangan->slug,
            'type' => 'update_fasilitasi',
        ]));

        Notification::send(User::role('Verifikator')->get(), new StatusFasilitasiNotification([
            'title' => 'Status Fasilitasi Diperbarui',
            'message' => "Fasilitasi dengan Nomor Rancangan {$rancangan->no_rancangan} telah diperbarui, tahap {$label} selesai. Mohon lakukan pengecekan jika diperlukan.",
            'slug' => $rancangan->slug,
            'type' => 'update_fasilitasi',
        ]));

        $this->dispatch('closeModal', 'modalUpdateStatus');
        $this->dispatch('swal:toast', [
            'type' => 'success',
            'message' => "Tahap {$label} ditandai selesai.",
        ]);
        $this->resetModal();
    }

    public function render()
    {
        $fasilitasiList = FasilitasiProdukHukum::with('rancangan.user.perangkatDaerah')
            ->where('status_validasi_fasilitasi', 'Diterima')
            ->where('status_berkas_fasilitasi', 'Disetujui')
            ->whereHas('rancangan', function ($query) {
                $query->where('tentang', 'like', "%{$this->search}%")
                    ->orWhere('no_rancangan', 'like', "%{$this->search}%");
            })
            ->orderByDesc('tanggal_validasi_fasilitasi')
            ->paginate($this->perPage);

        return view('livewire.admin.fasilitasi.manajemen-fasilitasi', [
            'fasilitasiList' => $fasilitasiList,
            'tahapanTersisa' => $this->tahapanTersisa(),
        ])->layout('layouts.app');
    }
}
