<?php

namespace App\Livewire\Verifikator\Rancangan;

use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;
use App\Models\RancanganProdukHukum;
use App\Models\User;
use App\Models\Revisi;
use Carbon\Carbon;

use Illuminate\Support\Facades\Notification;
use App\Notifications\PilihPenelitiNotification;
use Illuminate\Support\Facades\Log;


class PenelitiDitugaskan extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $search = '';
    public $perPage = 5;

    public $selectedRevisi; // Untuk menyimpan data revisi yang dipilih
    public $revisiId; // ID revisi yang dipilih

    protected $listeners = [
        'resetPenelitiConfirmed' => 'resetPeneliti',
        'pilihUlangPenelitiConfirmed' => 'pilihUlangPeneliti',
    ];


    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function openModal($idRevisi)
    {
        // Cari revisi dengan eager loading
        $this->selectedRevisi = Revisi::with([
            'rancangan.user', // Jika relasi rancangan memiliki user
            'rancangan.perangkatDaerah', // Jika rancangan memiliki perangkat daerah
            'peneliti', // Peneliti yang terkait dengan revisi
        ])->findOrFail($idRevisi);

        // Dispatch untuk membuka modal
        $this->dispatch('openModalPilihPeneliti');
    }
    public function pilihUlangPeneliti($idRancangan, $idPeneliti)
    {
        $rancangan = RancanganProdukHukum::find($idRancangan);

        if (!$rancangan) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Kesalahan',
                'message' => 'Rancangan tidak ditemukan.',
            ]);
            return;
        }

        // Ambil revisi terbaru
        $revisi = $rancangan->revisi()->latest()->first();
        if (!$revisi) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Kesalahan',
                'message' => 'Revisi tidak ditemukan.',
            ]);
            return;
        }
        // Jika peneliti yang sama dipilih
        if ($revisi->id_user && (int)$revisi->id_user === (int)$idPeneliti) {
            $this->dispatch('swal:modal', [
                'type' => 'info',
                'title' => 'Tidak Ada Perubahan',
                'message' => 'Peneliti yang sama telah dipilih sebelumnya.',
            ]);
            return;
        }

        // Simpan ID peneliti lama untuk notifikasi
        $penelitiLamaId = $revisi->id_user;

        // Update data revisi
        $revisi->update([
            'id_user' => $idPeneliti, // ID peneliti baru yang dipilih
            'status_revisi' => 'Proses Revisi',
            'tanggal_peneliti_ditunjuk' => now(),
            'revisi_rancangan' => null,
            'revisi_matrik' => null,
            'catatan_revisi' => null,
            'catatan_validasi' => null,
            'tanggal_revisi' => null,
            'tanggal_validasi' => null,
            'status_validasi' => 'Belum Tahap Validasi', // Status awal validasi
        ]);

        // Kirim notifikasi ke peneliti lama (jika ada peneliti sebelumnya)
        if ($penelitiLamaId) {
            $penelitiLama = User::find($penelitiLamaId);
            Notification::send($penelitiLama, new PilihPenelitiNotification([
                'title' => 'Pemberitahuan Penugasan Dibatalkan',
                'message' => "Anda tidak lagi ditugaskan untuk meneliti rancangan: {$rancangan->tentang}.",
                'slug' => $rancangan->slug,
                'type' => 'penugasan_dibatalkan',
            ]));
        }

        // Kirim notifikasi ke peneliti baru
        $penelitiBaru = User::find($idPeneliti);
        Notification::send($penelitiBaru, new PilihPenelitiNotification([
            'title' => "Penugasan Baru rancangan {$rancangan->no_rancangan}",
            'message' => "Anda telah ditugaskan untuk meneliti rancangan: {$rancangan->tentang}. Segera Periksa dan lakukan Revisi !",
            'slug' => $rancangan->slug,
            'type' => 'upload_revisi',
        ]));

        // Kirim notifikasi ke user pengaju
        Notification::send($rancangan->user, new PilihPenelitiNotification([
            'title' => "Peneliti Baru Ditugaskan untuk Rancangan Nomor {$rancangan->no_rancangan}",
            'message' => "Rancangan Anda dengan nomor {$rancangan->no_rancangan} telah ditetapkan peneliti baru untuk melaksanakan revisi. Mohon menunggu proses revisi oleh peneliti yang ditugaskan.",
            'slug' => $rancangan->slug,
            'type' => 'peneliti_dipilih',
        ]));

        // Notifikasi sukses
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Berhasil',
            'message' => 'Peneliti berhasil diperbarui.',
        ]);
    }


    public function resetPeneliti($id)
    {
        if (empty($id)) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Kesalahan',
                'message' => 'ID Rancangan tidak valid.',
            ]);
            return;
        }

        $rancangan = RancanganProdukHukum::find($id);

        if (!$rancangan) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Kesalahan',
                'message' => 'Rancangan tidak ditemukan.',
            ]);
            return;
        }

        $revisi = $rancangan->revisi()->latest()->first();

        if (!$revisi) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Kesalahan',
                'message' => 'Revisi tidak ditemukan.',
            ]);
            return;
        }

        $penelitiId = $revisi->id_user; // Simpan ID peneliti sebelum direset

        $revisi->update([
            'id_user' => null,
            'status_revisi' => 'Menunggu Peneliti',
            'tanggal_peneliti_ditunjuk' => null,
            'revisi_rancangan' => null,
            'revisi_matrik' => null,
            'catatan_revisi' => null,
            'catatan_validasi' => null,
            'tanggal_revisi' => null,
            'tanggal_validasi' => null,
            'status_validasi' => 'Belum Tahap Validasi', // Status awal validasi
        ]);

        // Kirim notifikasi ke pengguna (user pengaju)
        Notification::send(
            $rancangan->user,
            new PilihPenelitiNotification([
                'title' => 'Pemilihan Peneliti Dibatalkan',
                'message' => "Pemilihan peneliti untuk rancangan dengan nomor {$rancangan->no_rancangan} telah dibatalkan. Mohon menunggu informasi selanjutnya.",
                'slug' => $rancangan->slug,
                'type' => 'peneliti_dibatalkan',
            ])
        );

        // Kirim notifikasi ke peneliti lama (jika ada)
        if ($penelitiId) {
            $peneliti = User::find($penelitiId);
            Notification::send($peneliti, new PilihPenelitiNotification([
                'title' => 'Penugasan Anda Dibatalkan',
                'message' => "Anda tidak lagi ditugaskan untuk meneliti nomor rancangan {$rancangan->no_rancangan}, dengan judul rancangan: {$rancangan->tentang}.",
                'slug' => $rancangan->slug,
                'type' => 'peneliti_dibatalkan',
            ]));
        }

        // Tampilkan notifikasi sukses
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Berhasil',
            'message' => 'Peneliti berhasil direset dan status revisi kembali ke "Menunggu Peneliti".',
        ]);
    }

    public function resetDetail()
    {
        $this->selectedRevisi = null;
    }



    public function render()
    {
        // Query untuk rancangan dengan status revisi "Menunggu Revisi"
        $rancangan = RancanganProdukHukum::where('status_berkas', 'Disetujui')
            ->where('status_rancangan', 'Dalam Proses')
            ->whereHas('revisi', function ($query) {
                $query->whereIn('status_revisi', [
                    'Proses Revisi',
                    'Menunggu Validasi',
                    'Direvisi'
                ]);
            })
            ->with(['revisi.peneliti']) // Eager loading revisi dan peneliti
            ->where(function ($query) {
                $query->where('tentang', 'like', "%{$this->search}%")
                    ->orWhere('no_rancangan', 'like', "%{$this->search}%");
            })
            ->orderBy('tanggal_berkas_disetujui', 'desc') // Urutkan berdasarkan tangal pengakuan
            ->paginate($this->perPage);

        // Dapatkan daftar user dengan role "peneliti"
        $listPeneliti = User::role('peneliti')->pluck('nama_user', 'id');


        return view('livewire.verifikator.rancangan.peneliti-ditugaskan', compact('rancangan', 'listPeneliti'));
    }
}
