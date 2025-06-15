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

        // 🔥 Ambil ID peneliti dari rancangan (berdasarkan id_user di tabel revisi)
        $penelitiId = $this->selectedFasilitasi->rancangan->revisi()->latest()->first()->id_user ?? null;

        // Jika validasi diterima, lanjutkan proses fasilitasi
        if ($this->statusValidasi === 'Diterima') {
            $this->selectedFasilitasi->update([
                'status_validasi_fasilitasi' => 'Diterima',
            ]);

            // Kirim notifikasi ke user pengaju rancangan
            $user = $this->selectedFasilitasi->rancangan->user;
            if ($user) {
                $user->notify(new ValidationResultNotification([
                    'title' => "Fasilitasi Anda dengan No. {$this->selectedFasilitasi->rancangan->no_rancangan} Telah Diterima!",
                    'message' => "Selamat!  Fasilitasi Anda telah berhasil divalidasi dan diterima. Mohon menunggu proses pembuatan Nota Dinas  sebelum melanjutkan ke tahap berikutnya.",
                    'type' => 'fasilitasi_diterima',
                    'slug' => $this->selectedFasilitasi->rancangan->slug,
                ]));
            }
            // 🔥 Kirim notifikasi ke semua Admin
            $admins = User::role('Admin')->get(); // Ambil semua user dengan role Admin
            Notification::send($admins, new ValidationResultNotification([
                'title' => 'Fasilitasi No. ' . $this->selectedFasilitasi->rancangan->no_rancangan . ' Telah Diterima!',
                'message' => " Fasilitasi dengan nomor {$this->selectedFasilitasi->rancangan->no_rancangan} telah divalidasi dan diterima . Harap segera buatkan Nota dan Catat pencatatan pengajuan.",
                'type' => 'fasilitasi_diterima_admin',
                'slug' => $this->selectedFasilitasi->rancangan->slug,

            ]));

            // 🔥 Kirim notifikasi ke Peneliti terkait (berdasarkan revisi terakhir)

            if ($penelitiId) {
                $peneliti = User::find($penelitiId);
                if ($peneliti) {
                    $peneliti->notify(new ValidationResultNotification([
                        'title' => " Fasilitasi No. {$this->selectedFasilitasi->rancangan->no_rancangan} Lanjut ke Tahap Berikutnya!",
                        'message' => "Fasilitasi rancangan yang telah Anda teliti kini telah diterima. Bisa di cek di tab Riwayat Persetujuan Fasilitasi untuk memantau fasilitasi.",
                        'type' => 'fasilitasi_lanjut_peneliti',
                        'slug' => $this->selectedFasilitasi->rancangan->slug,
                    ]));
                }
            }
        }

        // ✅ Jika validasi ditolak, kirim notifikasi **hanya ke peneliti**
        if ($this->statusValidasi === 'Ditolak') {
            $this->selectedFasilitasi->update([
                'status_validasi_fasilitasi' => 'Ditolak',
                'status_berkas_fasilitasi' => 'Ditolak',
                'catatan_persetujuan_fasilitasi' => null,
                'tanggal_persetujuan_berkas' => null,
            ]);


            // 🔹 Cek apakah penelitiId ada, lalu ambil instance User
            if ($penelitiId) {
                $peneliti = User::find($penelitiId);

                // 🔥 Pastikan instance User ditemukan sebelum memanggil notify()
                if ($peneliti) {
                    $peneliti->notify(new ValidationResultNotification([
                        'title' => " Fasilitasi No. '{$this->selectedFasilitasi->rancangan->no_rancangan}' Ditolak",
                        'message' => "Fasilitasi ini telah ditolak. Status berkas kini *Ditolak*. Mohon periksa kembali berkas yang telah Anda teliti  dan lakukan revisi sesuai catatan yang diberikan. Perangkat Daerah akan mengunggah ulang berkas untuk diperbaiki. Tetap semangat!",
                        'slug' => $this->selectedFasilitasi->rancangan->slug,
                        'type' => 'fasilitasi_ditolak',
                    ]));
                }
            }

            // Kirim notifikasi ke user pengaju rancangan
            $user = $this->selectedFasilitasi->rancangan->user;
            if ($user) {
                $user->notify(new ValidationResultNotification([
                    'title' => ' Fasilitasi No. ' . $this->selectedFasilitasi->rancangan->no_rancangan . ' Telah Diterima!',
                    'message' => " Berkas fasilitasi Anda ditolak! Harap periksa catatan pengajuan rancangan  dan ajukan ulang dengan perbaikan yang diperlukan. Jangan khawatir, Anda bisa melakukannya! ",
                    'type' => 'fasilitasi_diterima',
                    'slug' => $this->selectedFasilitasi->rancangan->slug,
                ]));
            }
        }


        // Kirim pesan sukses
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Berhasil',
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
            ->orderBy('tanggal_persetujuan_berkas', 'desc') // 🔥 Urutkan berdasarkan tanggal terbaru
            ->paginate($this->perPage);

        return view('livewire.verifikator.fasilitasi.validasi-menunggu', compact('fasilitasiMenunggu'));
    }
}
