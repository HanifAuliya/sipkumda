<?php

namespace App\Livewire\Admin\NotaDinas;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\NotaDinas;
use App\Models\FasilitasiProdukHukum;
use App\Models\TandaTangan;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Notifications\ValidationResultNotification;
use Illuminate\Support\Facades\Notification;

class KelolaNotaDinas extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $listeners = [
        'deleteNotaDinas' => 'delete',
    ];

    public $search = ''; // Pencarian
    public $perPage = 10; // Jumlah data per halaman

    public $fasilitasiId;
    public $nomorNota;
    public $tandaTanganId;
    public $tanggalNota;
    public $nomorInputan;
    public $fasilitasiList = [];
    public $tandaTanganList = [];

    public function updatingSearch()
    {
        $this->resetPage(); // Reset pagination ke halaman pertama saat pencarian berubah
    }

    public function updatingPerPage()
    {
        $this->resetPage(); // Reset pagination jika perPage berubah
    }
    public function mount()
    {
        $this->loadData();
    }

    protected $rules = [
        'fasilitasiId' => 'required|exists:fasilitasi_produk_hukum,id',
        'nomorInputan' => 'required|numeric|digits_between:1,10', // Validasi nomor inputan
        'tandaTanganId' => 'required|exists:tanda_tangan,id',
    ];

    public function loadData()
    {
        $this->fasilitasiList = FasilitasiProdukHukum::where('status_validasi_fasilitasi', 'Diterima')
            ->whereDoesntHave('notaDinas') // Pastikan hanya fasilitasi yang belum memiliki nota dinas
            ->with('rancangan')
            ->get(); // Biarkan tetap Collection

        $this->tandaTanganList = TandaTangan::where('status', 'Aktif')->get();
    }

    public function openModalTambahNotaDinas()
    {
        $this->resetForm(); // Reset nilai sebelum membuka modal
        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('openModal', 'modalTambahNota'); // Event untuk membuka modal
    }


    public function store()
    {
        $this->validate([
            'fasilitasiId' => 'required|exists:fasilitasi_produk_hukum,id',
            'tandaTanganId' => 'required|exists:tanda_tangan,id',
            'nomorInputan' => 'required|numeric|digits_between:1,3',
        ]);

        $fasilitasi = FasilitasiProdukHukum::find($this->fasilitasiId);
        $jenisRancangan = $fasilitasi->rancangan->jenis_rancangan ?? '';

        // 🔥 Cek apakah nomor_inputan sudah ada dengan jenis dan tahun yang sama
        $tahunSekarang = now()->year;
        $existingNota = NotaDinas::where('nomor_inputan', $this->nomorInputan)
            ->whereHas('fasilitasi.rancangan', function ($query) use ($jenisRancangan, $tahunSekarang) {
                $query->where('jenis_rancangan', $jenisRancangan)
                    ->whereYear('tanggal_nota', $tahunSekarang);
            })
            ->exists();

        // 🔴 Jika sudah ada, tampilkan error dan batalkan proses
        if ($existingNota) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => "Nomor {$this->nomorInputan} sudah digunakan untuk {$jenisRancangan} di tahun {$tahunSekarang}.",
            ]);
            return; // 🔥 Hentikan eksekusi jika nomor sudah ada
        }

        // 🔹 Tentukan format kode jenis rancangan (NDPB atau NDSK)
        $kodeJenis = ($jenisRancangan === "Peraturan Bupati") ? "NDPB" : "NDSK";

        // 🔹 Generate nomor nota dinas
        $this->nomorNota = "180/{$this->nomorInputan}/{$kodeJenis}/KUM/" . $tahunSekarang;

        // 🔹 Simpan ke database
        NotaDinas::create([
            'fasilitasi_id' => $this->fasilitasiId,
            'nomor_inputan' => $this->nomorInputan,
            'nomor_nota' => $this->nomorNota,
            'tanggal_nota' => now(),
            'tanda_tangan_id' => $this->tandaTanganId,
        ]);

        // 🔥 Pastikan loadData() dipanggil setelah simpan
        $this->loadData();


        // 🔥 Kirim notifikasi ke **Verifikator** bahwa Nota Dinas telah dibuat
        $verifikator = User::role('Verifikator')->get(); // Ambil semua user dengan role Verifikator
        Notification::send($verifikator, new ValidationResultNotification([
            'title' => "📝 Nota Dinas Baru Telah Dibuat!",
            'message' => "Nota Dinas dengan nomor **{$this->nomorNota}**untuk rancangan dengan nomor{$fasilitasi->rancangan->no_ranncangan} telah dibuat dan disimpan dalam sistem. Mohon cek dan pantau fasilitasi lebih lanjut. ",
            'type' => 'nota_dinas_dibuat',
            'slug' => $fasilitasi->rancangan->slug,
        ]));

        // 🔥 Kirim notifikasi ke **User Pengaju Rancangan**
        $user = $fasilitasi->rancangan->user;
        if ($user) {
            $user->notify(new ValidationResultNotification([
                'title' => "📝 Nota Dinas Fasilitasi Telah Dibuat!",
                'message' => "Nota Dinas untuk fasilitasi Rancangan {$fasilitasi->rancangan->no_ranncangan} Anda dengan nomor **{$this->nomorNota}** telah dibuat. Kamu bisa cetak di Aksi ⚙️-> Cetak Nota Dinas, atau Kamu ke halaman Nota lalu cetak !🔥 . Sekarang Anda dapat Mengajukan Fasilitasi secara daring. ",
                'type' => 'nota_dinas_user',
                'slug' => $fasilitasi->rancangan->slug,
            ]));
        }


        // 🔥 Tampilkan notifikasi sukses
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Nota Dinas Berhasil Disimpan',
            'message' => "Nota Dinas dengan Nomor {$this->nomorNota} berhasil dibuat!",
        ]);


        // 🔥 Reset nilai setelah data difilter ulang
        $this->reset();

        // 🔥 Tutup modal setelah reset
        $this->dispatch('closeModal', 'modalTambahNota');
    }

    public function resetForm()
    {
        $this->reset(['fasilitasiId', 'nomorInputan', 'nomorNota', 'tandaTanganId']);
    }

    public function deleteNotaDinas($id)
    {
        // Cek apakah user adalah admin sebelum menghapus
        if (!auth()->user()->hasRole('Admin')) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Akses Ditolak!',
                'message' => 'Anda tidak memiliki izin untuk menghapus Nota Dinas.',
            ]);
            return;
        }

        // Cari dan hapus data
        $notaDinas = NotaDinas::findOrFail($id);
        $notaDinas->delete();


        // 🔥 Pastikan loadData() dipanggil setelah simpan
        $this->loadData();
        // 🔥 Notifikasi sukses
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Berhasil',
            'message' => 'Nota Dinas berhasil dihapus.',
        ]);
    }

    public function generatePDF($notaId)
    {
        $notaDinas = NotaDinas::with('fasilitasi.rancangan.user.perangkatDaerah', 'tandaTangan')->findOrFail($notaId);
        $pdf = Pdf::loadView('pdf.nota-dinas', compact('notaDinas'))->setPaper('A4', 'portrait');

        // Kirim event ke browser untuk menutup SweetAlert setelah PDF selesai dibuat
        $this->dispatch('hideLoadingSwal');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, "Nota-Dinas-{$notaDinas->nomor_inputan}.pdf");
    }

    public function render()
    {
        $query = NotaDinas::with([
            'fasilitasi.rancangan.user.perangkatDaerah', // Eager loading performa
            'tandaTangan'
        ])
            ->orderByDesc('tanggal_nota') // Urutkan berdasarkan tanggal_nota terbaru
            ->orderByDesc('created_at'); // Jika tanggal sama, urutkan berdasarkan waktu dibuat

        // **Filter Pencarian**
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->whereHas('fasilitasi.rancangan', function ($subQuery) {
                    $subQuery->where('no_rancangan', 'like', "%{$this->search}%")
                        ->orWhere('tentang', 'like', "%{$this->search}%");
                })
                    ->orWhere('nomor_nota', 'like', "%{$this->search}%");
            });
        }

        $notaDinasList = $query->paginate($this->perPage);

        return view('livewire.admin.nota-dinas.kelola-nota-dinas', [
            'notaDinasList' => $notaDinasList,
            'fasilitasiOptions' => FasilitasiProdukHukum::where('status_validasi_fasilitasi', 'Diterima')->get(),
            'tandaTanganOptions' => TandaTangan::where('status', 'Aktif')->get(),
        ])->layout('layouts.app');
    }
}
