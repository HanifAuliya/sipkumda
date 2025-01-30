<?php

namespace App\Livewire\Verifikator\Fasilitasi;

use Livewire\Component;
use App\Models\FasilitasiProdukHukum;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use App\Models\User;
use Illuminate\Support\Carbon;
use App\Notifications\ValidationResultNotification;
use Illuminate\Support\Facades\Notification;


class ValidasiMenunggu extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $search = ''; // Pencarian
    public $perPage = 10; // Default jumlah item per halaman

    public $activeTab = 'menunggu-validasi'; // Tab default

    protected $queryString = ['activeTab']; // Masukkan 'tab' ke dalam query string

    public $selectedFasilitasi;
    public $catatanValidasi;
    public $statusValidasi;

    protected $rules = [
        'statusValidasi' => 'required|in:Diterima,Ditolak',
        'catatanValidasi' => 'required|string|max:1000',
    ];

    protected $messages = [
        'statusValidasi.required' => 'Status harus dipilih.',
        'catatanValidasi.required' => 'Catatan validasi wajib diisi.',
        'catatanValidasi.max' => 'Catatan validasi maksimal 1000 karakter.',
    ];

    public function openModalValidasiFasilitasi($idFasilitasi)
    {
        // Cari fasilitasi berdasarkan ID
        $this->selectedFasilitasi = FasilitasiProdukHukum::with(['rancangan.user', 'rancangan.perangkatDaerah'])
            ->findOrFail($idFasilitasi);

        // Reset form jika sebelumnya ada
        $this->resetFormValidasi();

        // Buka modal
        $this->dispatch('openModalValidasiFasilitasi');
    }

    public function validasiFasilitasi()
    {
        $this->validate();

        if (!$this->selectedFasilitasi) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Kesalahan',
                'message' => 'Data fasilitasi tidak ditemukan.',
            ]);
            return;
        }

        // Update status validasi fasilitasi
        $this->selectedFasilitasi->update([
            'status_validasi_fasilitasi' => $this->statusValidasi,
            'catatan_validasi_fasilitasi' => $this->catatanValidasi,
            'tanggal_validasi_fasilitasi' => now(),
        ]);

        // Jika validasi diterima, lanjutkan proses fasilitasi
        if ($this->statusValidasi === 'Diterima') {
            $this->selectedFasilitasi->update([
                'status_validasi_fasilitasi' => 'Diterima',
            ]);

            // Kirim notifikasi ke user pengaju rancangan
            // $user = $this->selectedFasilitasi->rancangan->user;
            // if ($user) {
            //     $user->notify(new ValidationResultNotification([
            //         'title' => 'Fasilitasi Anda dengan nomor ' . $this->selectedFasilitasi->rancangan->no_rancangan . ' Telah Diterima',
            //         'message' => "Selamat! Fasilitasi Anda telah berhasil divalidasi. Anda bisa lanjut ke tahap selanjutnya.",
            //         'type' => 'fasilitasi_diterima',
            //         'slug' => $this->selectedFasilitasi->rancangan->slug,
            //     ]));
            // }
        }

        // Jika validasi ditolak, berikan kesempatan untuk perbaikan
        if ($this->statusValidasi === 'Ditolak') {
            $this->selectedFasilitasi->update([
                'status_validasi_fasilitasi' => 'Ditolak',
            ]);

            // Kirim notifikasi ke user perangkat daerah
            // $user = $this->selectedFasilitasi->rancangan->user;
            // if ($user) {
            //     $user->notify(new ValidationResultNotification([
            //         'title' => "Fasilitasi No '{$this->selectedFasilitasi->rancangan->no_rancangan}' Ditolak",
            //         'message' => "Mohon periksa kembali berkas fasilitasi Anda dan ajukan ulang berdasarkan catatan yang diberikan.",
            //         'slug' => $this->selectedFasilitasi->rancangan->slug,
            //         'type' => 'fasilitasi_ditolak',
            //     ]));
            // }
        }

        // Kirim pesan sukses
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'berhasil',
            'message' => "Fasilitasi berhasil divalidasi sebagai '{$this->statusValidasi}'.",
        ]);

        // Reset form dan tutup modal
        $this->resetFormValidasi();
        $this->dispatch('closeModalValidasiFasilitasi');
    }

    public function resetFormValidasi()
    {

        $this->reset(['catatanValidasi', 'statusValidasi']);
    }

    public function resetData()
    {
        $this->reset(['catatanValidasi', 'statusValidasi']);
        $this->selectedFasilitasi = null;
    }


    public function render()
    {
        $fasilitasiMenunggu = FasilitasiProdukHukum::with('rancangan')
            ->whereIn('status_berkas_fasilitasi', ['Disetujui', 'Ditolak'])
            ->where('status_validasi_fasilitasi', 'Menunggu Validasi')
            ->whereHas('rancangan', function ($query) {
                $query->where('no_rancangan', 'like', "%{$this->search}%")
                    ->orWhere('tentang', 'like', "%{$this->search}%");
            })
            ->paginate($this->perPage);

        return view('livewire.verifikator.fasilitasi.validasi-menunggu', compact('fasilitasiMenunggu'));
    }
}
