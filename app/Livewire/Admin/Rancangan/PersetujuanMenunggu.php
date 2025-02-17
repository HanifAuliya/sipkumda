<?php

namespace App\Livewire\Admin\Rancangan;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use App\Models\RancanganProdukHukum;
use App\Models\User;
use Illuminate\Support\Carbon;
use App\Notifications\PersetujuanRancanganNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PersetujuanMenunggu extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $search = '';
    public $perPage = 5; // Default jumlah data per halaman

    public $selectedRancangan;

    public $catatan;
    public $statusBerkas;

    protected $rules = [
        'statusBerkas' => 'required|in:Disetujui,Ditolak',
        'catatan' => 'required|string|min:5',
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

                // Update status_revisi di tabel Revisi menjadi 'Menunggu Peneliti'
                $revisi = $this->selectedRancangan->revisi()->first(); // Ambil revisi terkait
                if ($revisi) {
                    $revisi->update([
                        'status_revisi' => 'Menunggu Peneliti',
                    ]);
                }

                // Kirim notifikasi ke verifikator
                $verifikator = User::role('Verifikator')->get(); // Ambil semua user dengan role Verifikator
                Notification::send(
                    $verifikator, // Semua verifikator
                    new PersetujuanRancanganNotification([
                        'title' => "🎉📜 Berkas Rancangan No. {$this->selectedRancangan->no_rancangan} Disetujui! 🔍",
                        'message' => "✅ Berkas Rancangan dengan nomor *{$this->selectedRancangan->no_rancangan}* telah berhasil *disetujui*! 🏆🎊 Harap segera *memilih peneliti* untuk melanjutkan proses berikutnya. ⏳⚖️",
                        'slug' => $this->selectedRancangan->slug, // Slug untuk modal detail
                        'type' => 'pilih_peneliti', // Tipe notifikasi untuk verifikator
                    ])
                );
            } else {
                $this->selectedRancangan->tanggal_berkas_disetujui = null; // Reset jika bukan "Disetujui"
            }

            $this->selectedRancangan->save();

            // Kirim notifikasi ke user
            Notification::send(
                $this->selectedRancangan->user, // User yang mengajukan rancangan
                new PersetujuanRancanganNotification([
                    'title' => $this->statusBerkas === 'Disetujui'
                        ? "✅📜 Berkas Rancangan No. {$this->selectedRancangan->no_rancangan} Disetujui!"
                        : "❌📜 Berkas Rancangan No. {$this->selectedRancangan->no_rancangan} Ditolak!",

                    'message' => $this->statusBerkas === 'Disetujui'
                        ? "🎉 Selamat! Berkas Rancangan Anda telah *disetujui* ✅. Proses selanjutnya adalah *penugasan Peneliti* 🔍. Mohon menunggu pemilihan peneliti."
                        : "⚠️ Mohon maaf, Berkas Rancangan Anda *ditolak* ❌. Silakan periksa *catatan revisi* 📝 dan lakukan perbaikan sebelum mengajukan ulang. Semangat! 💪😊",

                    'slug' => $this->selectedRancangan->slug, // Slug untuk modal detail
                    'type' => $this->statusBerkas === 'Disetujui' ? 'persetujuan_diterima' : 'persetujuan_ditolak', // Tentukan tipe notifikasi
                ])
            );

            // Emit notifikasi sukses ke pengguna
            $this->dispatch('refreshNotifications');
            // reset
            $this->resetForm();


            $this->dispatch('swal:modal', [
                'type' => 'success',
                'message' => 'Status rancangan berhasil diperbarui!',
            ]);
        }
    }

    public function resetForm()
    { // Atur ulang semua properti ke nilai default
        $this->selectedRancangan = null;

        $this->resetErrorBag(); // Reset error validasi
        $this->resetValidation(); // Reset tampilan error validasi
    }
    public function render()
    {
        $rancanganMenunggu = RancanganProdukHukum::with(['user', 'perangkatDaerah'])
            ->whereIn('status_berkas', ['Menunggu Persetujuan'])
            ->where('status_rancangan', 'Dalam Proses')
            ->where(function ($query) {
                $query->where('no_rancangan', 'like', "%{$this->search}%")
                    ->orWhere('tentang', 'like', "%{$this->search}%");
            })
            ->orderBy('tanggal_pengajuan', 'desc') // Urutkan berdasarkan tangal pengakuan
            ->paginate($this->perPage);

        return view('livewire.admin.rancangan.persetujuan-menunggu', compact('rancanganMenunggu'));
    }
}
