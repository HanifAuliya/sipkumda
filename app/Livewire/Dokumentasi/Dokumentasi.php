<?php

namespace App\Livewire\Dokumentasi;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\DokumentasiProdukHukum;
use App\Models\PerangkatDaerah;
use App\Models\RancanganProdukHukum;

use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\DokumentasiExport;
use Illuminate\Http\Request;


class Dokumentasi extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = ''; // Input pencarian
    public $perPage = 10; // Jumlah data per halaman
    public $jenisRancangan = ''; // Filter jenis rancangan
    public $tahun = '';

    // EDIT
    public $editId;
    public $rancanganId;
    public $nomor;
    public $tanggal;
    public $nomorBeritaDaerah;
    public $tanggalBeritaDaerah;
    public $fileProdukHukum;
    public $dokumentasi;


    protected $listeners = [
        'refreshTable' => '$refresh',
        'deleteConfirmed' => 'delete'
    ];

    public function exportExcel()
    {
        return Excel::download(new DokumentasiExport($this->search, $this->jenisRancangan, $this->tahun), 'Dokumentasi.xlsx');
    }

    public function exportPDF()
    {
        $dokumentasiList = DokumentasiProdukHukum::with('rancangan', 'perangkatDaerah')
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
            ->get();

        $pdf = Pdf::loadView('pdf.dokumentasi', compact('dokumentasiList'));

        // Set ukuran kertas & orientasi
        $pdf->setPaper('A4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'dokumentasi.pdf');
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
        $this->tanggal = $dokumentasi->tanggal;
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
            'tanggal',
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
            'tanggal' => 'required|date',
            'nomorBeritaDaerah' => 'required|string|regex:/^[A-Za-z0-9]+$/',
            'tanggalBeritaDaerah' => 'required|date',
            'fileProdukHukum' => 'nullable|mimes:pdf|max:5120', // Bisa kosong
        ]);

        $dokumentasi = DokumentasiProdukHukum::findOrFail($this->editId);

        // Simpan file baru jika diunggah
        if ($this->fileProdukHukum) {
            $path = $this->fileProdukHukum->store('dokumentasi/file_produk_hukum', 'local');
            $dokumentasi->file_produk_hukum = $path;
        }

        // Update data
        $dokumentasi->update([
            'rancangan_id' => $this->rancanganId,
            'nomor' => $this->nomor,
            'tanggal' => $this->tanggal,
            'nomor_berita_daerah' => $this->nomorBeritaDaerah,
            'tanggal_berita_daerah' => $this->tanggalBeritaDaerah,
        ]);

        // Tutup modal dan refresh tabel
        $this->dispatch('closeModalEditDokumentasi');
        $this->dispatch('refreshTable');

        // Notifikasi sukses
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Dokumentasi berhasil diperbarui.',
        ]);
    }

    public function delete($id)
    {
        $dokumentasi = DokumentasiProdukHukum::findOrFail($id);
        $dokumentasi->delete();

        // Refresh tabel setelah penghapusan
        $this->dispatch('refreshTable');

        // Tampilkan notifikasi sukses
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Dokumentasi berhasil dihapus.',
        ]);
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
            });

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
