<?php

namespace App\Livewire\Admin\Rancangan;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use App\Models\RancanganProdukHukum;
use Illuminate\Support\Carbon;
use App\Notifications\PersetujuanRancanganNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Gate;

class PersetujuanMenunggu extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $search = '';
    public $perPage = 5; // Default jumlah data per halaman

    protected $queryString = ['search', 'perPage'];

    public $selectedRancangan;

    public $catatan;
    public $statusBerkas;

    protected $rules = [
        'statusBerkas' => 'required|in:Disetujui,Ditolak',
        'catatan' => 'required|string|min:5|max:255',
    ];

    protected $messages = [
        'statusBerkas.required' => 'Status harus dipilih.',
        'catatan.required' => 'Catatan wajib diisi.',
        'catatan.min' => 'Catatan minimal 5 karakter.',
        'catatan.max' => 'Catatan maksimal 255 karakter.',
    ];

    public function updatedPerPage()
    {
        $this->resetPage(); // Reset pagination saat jumlah per halaman berubah
    }

    public function openModal($id)
    {
        $this->selectedRancangan = RancanganProdukHukum::with(['user', 'perangkatDaerah'])->find($id);
        if (!$this->selectedRancangan || Gate::denies('view', $this->selectedRancangan)) {
            abort(403, 'Unauthorized access');
        }
        $this->catatan = $this->selectedRancangan->catatan_berkas ?? '';
        $this->statusBerkas = $this->selectedRancangan->status_berkas ?? '';

        $this->dispatch('openModalPersetujuan');
    }

    public function updateStatus()
    {
        $this->validate();

        if ($this->selectedRancangan) {
            $this->selectedRancangan->status_berkas = $this->statusBerkas;
            $this->selectedRancangan->catatan_berkas = $this->catatan;

            // Set tanggal_berkas_disetujui jika status disetujui
            if ($this->statusBerkas === 'Disetujui') {
                $this->selectedRancangan->tanggal_berkas_disetujui = Carbon::now();
            } else {
                $this->selectedRancangan->tanggal_berkas_disetujui = null; // Reset jika bukan "Disetujui"
            }

            $this->selectedRancangan->save();

            // Kirim notifikasi ke user
            Notification::send(
                $this->selectedRancangan->user, // User yang mengajukan rancangan
                new PersetujuanRancanganNotification([
                    'title' => "Rancangan Anda {$this->statusBerkas}",
                    'message' => "Rancangan Anda dengan nomor {$this->selectedRancangan->no_rancangan} telah {$this->statusBerkas}. Silahkan Periksa !",
                    'slug' => $this->selectedRancangan->slug, // Slug untuk memuat modal detail
                    'type' => $this->statusBerkas === 'Disetujui' ? 'persetujuan_diterima' : 'persetujuan_ditolak', // Tentukan tipe
                    // 'url' => route('user.rancangan.detail', $this->rancangan->id), // URL detail rancangan
                ])
            );

            // Emit notifikasi sukses ke pengguna
            $this->dispatch('refreshNotifications');

            $this->dispatch('swal:modal', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Status rancangan berhasil diperbarui!',
            ]);
        }
    }

    public function resetStatus()
    {
        if ($this->selectedRancangan) {
            $this->selectedRancangan->update([
                'status_berkas' => 'Menunggu Persetujuan',
                'catatan_berkas' => null,
                'tanggal_berkas_disetujui' => null, // Reset tanggal jika status direset
            ]);

            $this->statusBerkas = 'Menunggu Persetujuan';
            $this->catatan = '';

            // Kirim notifikasi ke user
            Notification::send(
                $this->selectedRancangan->user, // User yang mengajukan rancangan
                new PersetujuanRancanganNotification([
                    'title' => "Rancangan Anda {$this->statusBerkas}",
                    'message' => "Rancangan Anda dengan nomor {$this->selectedRancangan->no_rancangan} telah {$this->statusBerkas}.",
                    'slug' => $this->selectedRancangan->slug, // Slug untuk memuat modal detail
                    'type' => 'persetujuan_menunggu', // Tipe notifikasi
                    // 'url' => route('user.rancangan.detail', $this->rancangan->id), // URL detail rancangan
                ])
            );

            // Emit notifikasi sukses ke pengguna
            $this->dispatch('refreshNotifications');

            $this->dispatch('swal:modal', [
                'type' => 'info',
                'title' => 'Reset Berhasil!',
                'message' => 'Status rancangan telah direset ke Menunggu Persetujuan.',
            ]);
        }
    }

    public function render()
    {
        $rancanganMenunggu = RancanganProdukHukum::with(['user', 'perangkatDaerah'])
            ->whereIn('status_berkas', ['Menunggu Persetujuan', 'Ditolak'])
            ->where(function ($query) {
                $query->where('no_rancangan', 'like', "%{$this->search}%")
                    ->orWhere('tentang', 'like', "%{$this->search}%");
            })
            ->orderBy('tanggal_pengajuan', 'desc') // Urutkan berdasarkan tangal pengakuan
            ->paginate($this->perPage);

        return view('livewire.admin.rancangan.persetujuan-menunggu', compact('rancanganMenunggu'));
    }
}
