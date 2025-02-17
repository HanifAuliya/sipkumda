<?php

namespace App\Livewire\Dokumentasi;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\DokumentasiProdukHukum;
use App\Models\User;
use App\Models\RancanganProdukHukum;

use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\DokumentasiExport;

use Illuminate\Support\Facades\Notification;
use App\Notifications\DokumentasiNotification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;


class Dokumentasi extends Component
{
    use WithPagination, WithFileUploads;
    protected $paginationTheme = 'bootstrap';

    public $search = ''; // Input pencarian
    public $perPage = 10; // Jumlah data per halaman
    public $jenisRancangan = ''; // Filter jenis rancangan
    public $tahun = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'jenisRancangan' => ['except' => ''],
        'tahun' => ['except' => '']
    ];


    // EDIT
    public $editId;
    public $rancanganId;
    public $nomor;
    public $tanggal;
    public $nomorBeritaDaerah;
    public $tanggalBeritaDaerah;
    public $fileProdukHukum;
    public $dokumentasi;
    public $sortColumn = 'tanggal'; // Kolom default yang diurutkan
    public $sortDirection = 'desc'; // Urutan default (desc = terbaru ke terlama)

    protected $listeners = [
        'refreshTable' => '$refresh',
        'deleteConfirmed' => 'delete'
    ];

    public function exportExcel()
    {
        // Buat nama file berdasarkan filter
        $fileName = 'Dokumentasi';
        $fileName .= $this->tahun ? '-' . $this->tahun : ''; // Tambah tahun jika dipilih
        $fileName .= $this->jenisRancangan ? '-' . str_replace(' ', '-', strtolower($this->jenisRancangan)) : ''; // Tambah jenis jika dipilih
        $fileName .= '.xlsx'; // Tambahkan ekstensi

        return Excel::download(new DokumentasiExport($this->search, $this->jenisRancangan, $this->tahun), $fileName);
    }


    public function exportPDF()
    {
        $dokumentasiList = DokumentasiProdukHukum::with('rancangan', 'perangkatDaerah')
            ->when($this->search, function ($q) {
                $q->whereHas('rancangan', function ($qr) {
                    $qr->where('tentang', 'like', "%{$this->search}%")
                        ->orWhere('no_rancangan', 'like', "%{$this->search}%");
                });
            })
            ->when($this->jenisRancangan, function ($q) {
                $q->whereHas('rancangan', function ($qr) {
                    $qr->where('jenis_rancangan', $this->jenisRancangan);
                });
            })
            ->when($this->tahun, function ($q) { // Filter berdasarkan tahun
                $q->whereHas('rancangan', function ($qr) {
                    $qr->whereYear('tanggal_pengajuan', $this->tahun);
                });
            })
            ->get();

        // Buat nama file berdasarkan filter
        $fileName = 'dokumentasi';
        $fileName .= $this->tahun ? '-' . $this->tahun : ''; // Tambah tahun jika dipilih
        $fileName .= $this->jenisRancangan ? '-' . str_replace(' ', '-', strtolower($this->jenisRancangan)) : ''; // Tambah jenis jika dipilih
        $fileName .= '.pdf'; // Tambahkan ekstensi

        // Load view PDF
        $pdf = Pdf::loadView('pdf.dokumentasi', compact('dokumentasiList'))
            ->setPaper('A4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName);
    }



    public function updatingSearch()
    {
        $this->resetPage(); // Reset ke halaman pertama saat mencari
    }

    public function updatingPerPage()
    {
        $this->resetPage(); // Reset ke halaman pertama saat mengubah perPage
    }

    public function updatingJenisRancangan()
    {
        $this->resetPage(); // Reset ke halaman pertama saat filter diubah
    }


    public function edit($id)
    {

        $dokumentasi = DokumentasiProdukHukum::with('rancangan')->find($id);

        if (!$dokumentasi) {
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Data dokumentasi tidak ditemukan.',
            ]);
            return;
        }

        $this->editId = $dokumentasi->id;
        $this->rancanganId = optional($dokumentasi->rancangan)->id_rancangan ?? null;
        $this->nomor = $dokumentasi->nomor;
        $this->nomorBeritaDaerah = $dokumentasi->nomor_berita_daerah;
        $this->tanggalBeritaDaerah = $dokumentasi->tanggal_berita_daerah;
        $this->fileProdukHukum = null; // File tidak ditampilkan, hanya bisa diupload ulang

    }

    public function resetForm()
    {
        // Reset semua nilai form
        $this->reset([
            'editId',
            'rancanganId',
            'nomor',
            'nomorBeritaDaerah',
            'tanggalBeritaDaerah',
            'fileProdukHukum',
            'dokumentasi'
        ]);

        // Reset error validation jika ada
        $this->resetValidation();
    }


    public function update()
    {
        // Validasi input
        $this->validate([
            'rancanganId' => 'required|exists:fasilitasi_produk_hukum,id',
            'nomor' => 'required|numeric|digits_between:1,3',
            'nomorBeritaDaerah' => 'required|string|regex:/^[A-Za-z0-9]+$/',
            'tanggalBeritaDaerah' => 'required|date',
            'fileProdukHukum' => 'nullable|mimes:pdf|max:5120', // Bisa kosong
        ]);

        $dokumentasi = DokumentasiProdukHukum::findOrFail($this->editId);

        // Cek Policy: Hanya Admin dan Verifikator yang bisa mengupdate dokumentasi
        $this->authorize('update', $dokumentasi);

        // Simpan file baru jika ada unggahan
        if ($this->fileProdukHukum) {
            // Hapus file lama jika ada
            if ($dokumentasi->file_produk_hukum && Storage::exists($dokumentasi->file_produk_hukum)) {
                Storage::delete($dokumentasi->file_produk_hukum);
            }

            // Simpan file baru
            $path = $this->fileProdukHukum->store('dokumentasi/file_produk_hukum', 'local');
            $dokumentasi->file_produk_hukum = $path;
        }

        // Update data
        $dokumentasi->update([
            'rancangan_id' => $this->rancanganId,
            'nomor' => $this->nomor,
            'nomor_berita_daerah' => $this->nomorBeritaDaerah,
            'tanggal_berita_daerah' => $this->tanggalBeritaDaerah,
        ]);

        // Kirim Notifikasi ke Verifikator
        $verifikatorUsers = User::role('Verifikator')->get();
        Notification::send($verifikatorUsers, new DokumentasiNotification([
            'title' => "📄 Dokumentasi Produk Hukum Diperbarui",
            'message' => "Dokumentasi produk hukum dengan Nomor {$this->nomor} telah diperbarui.",
            'type' => 'dokumentasi_diperbarui',
            'slug' => $dokumentasi->rancangan->slug, // Ambil slug dari rancangan
        ]));

        // Tutup modal dan refresh tabel
        $this->dispatch('closeModalEditDokumentasi');
        $this->dispatch('refreshTable');
        $this->dispatch('loadData');

        // Tampilkan notifikasi sukses
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Dokumentasi berhasil diperbarui.',
        ]);
    }


    public function resetError()
    {
        $this->resetErrorBag('fileProdukHukum');
    }

    public function removeFile()
    {
        if ($this->fileProdukHukum) {
            $this->fileProdukHukum->delete(); // Menghapus dari Livewire temp storage
        }
        $this->reset('fileProdukHukum'); // Mengosongkan variabel agar input aktif kembali
    }


    public function delete($id)
    {
        $dokumentasi = DokumentasiProdukHukum::findOrFail($id);

        // Cek Policy: Hanya Admin yang bisa menghapus dokumentasi
        $this->authorize('delete', $dokumentasi);

        // Hapus file dari storage jika ada
        if ($dokumentasi->file_produk_hukum && Storage::exists($dokumentasi->file_produk_hukum)) {
            Storage::delete($dokumentasi->file_produk_hukum);
        }

        // Hapus data dari database
        $dokumentasi->delete();

        // Kirim Notifikasi ke Verifikator
        $verifikatorUsers = User::role('Verifikator')->get();
        Notification::send($verifikatorUsers, new DokumentasiNotification([
            'title' => "❌ Dokumentasi Produk Hukum Dihapus",
            'message' => "Dokumentasi dengan Nomor {$dokumentasi->nomor} telah dihapus dari sistem.",
            'type' => 'dokumentasi_dihapus',
            'slug' => $dokumentasi->rancangan->slug,
        ]));

        // Refresh tabel setelah penghapusan
        $this->dispatch('refreshTable');
        $this->dispatch('loadData');

        // Tampilkan notifikasi sukses
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Dokumentasi berhasil dihapus.',
        ]);
    }


    public function sortBy($column)
    {
        // Jika klik kolom yang sama, toggle antara asc dan desc
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc'; // Default saat kolom baru dipilih
        }
    }


    public function render()
    {
        $query = DokumentasiProdukHukum::with('rancangan', 'perangkatDaerah')
            ->when($this->search, function ($q) {
                $q->whereHas('rancangan', function ($qr) {
                    $qr->where('tentang', 'like', '%' . $this->search . '%')
                        ->orWhere('no_rancangan', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->jenisRancangan, function ($q) {
                $q->whereHas('rancangan', function ($qr) {
                    $qr->where('jenis_rancangan', $this->jenisRancangan);
                });
            })
            ->when($this->tahun, function ($q) { // Filter tahun
                $q->whereHas('rancangan', function ($qr) {
                    $qr->whereYear('tanggal_pengajuan', $this->tahun);
                });
            })
            ->orderBy($this->sortColumn ?? 'tanggal', $this->sortDirection ?? 'desc') // Sorting dinamis
            ->orderByDesc('created_at'); // Fallback jika tanggal sama
        return view('livewire.dokumentasi.dokumentasi', [
            'dokumentasiList' => $query->paginate($this->perPage),
            'totalDokumentasi' => DokumentasiProdukHukum::count(),
            'jenisRancanganList' => RancanganProdukHukum::select('jenis_rancangan')->distinct()->pluck('jenis_rancangan'),
            'tahunOptions' => RancanganProdukHukum::selectRaw('YEAR(tanggal_pengajuan) as tahun')
                ->distinct()
                ->orderBy('tahun', 'desc')
                ->pluck('tahun'),
        ])->layout('layouts.app');
    }
}
