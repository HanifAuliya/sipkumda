<?php

namespace App\Livewire\Dokumentasi;

use App\Exports\DokumentasiExport;
use App\Models\DokumentasiProdukHukum;
use App\Models\User;
use App\Notifications\DokumentasiNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Dokumentasi extends Component
{
    use WithPagination, WithFileUploads;

    private const SORTABLE = ['nomor', 'tanggal_penetapan', 'tanggal_pengarsipan'];

    public $search = '';
    public $perPage = 10;
    public $jenisRancangan = '';
    public $tahun = '';

    public $sortColumn = 'tanggal_pengarsipan';
    public $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'jenisRancangan' => ['except' => ''],
        'tahun' => ['except' => ''],
    ];

    // Form edit
    public $editId;
    public $editTahun;
    public $nomor;
    public $nomor_berita;
    public $tahun_berita;
    public $tanggal_penetapan;
    public $file_produk_hukum;

    protected $validationAttributes = [
        'nomor_berita' => 'nomor berita daerah',
        'tahun_berita' => 'tahun berita daerah',
        'file_produk_hukum' => 'file produk hukum',
    ];

    public function updating($property)
    {
        if (in_array($property, ['search', 'perPage', 'jenisRancangan', 'tahun'])) {
            $this->resetPage();
        }
    }

    /** Dipanggil komponen tambah dokumentasi setelah menyimpan. */
    #[On('refreshTable')]
    public function refreshTable()
    {
        $this->resetPage();
    }

    private function namaFile(string $ekstensi): string
    {
        return 'dokumentasi'
            . ($this->tahun ? '-' . $this->tahun : '')
            . ($this->jenisRancangan ? '-' . str($this->jenisRancangan)->slug() : '')
            . ".{$ekstensi}";
    }

    public function exportExcel()
    {
        $this->authorize('viewAny', DokumentasiProdukHukum::class);

        $this->dispatch('hideLoadingSwal');

        return Excel::download(new DokumentasiExport($this->search, $this->jenisRancangan, $this->tahun), $this->namaFile('xlsx'));
    }

    public function exportPDF()
    {
        $this->authorize('viewAny', DokumentasiProdukHukum::class);

        $dokumentasiList = DokumentasiProdukHukum::with('rancangan', 'perangkatDaerah')
            ->filter($this->search, $this->jenisRancangan, $this->tahun)
            ->orderByDesc('tanggal_pengarsipan')
            ->get();

        $pdf = Pdf::loadView('pdf.dokumentasi', compact('dokumentasiList'))->setPaper('A4', 'landscape');

        $this->dispatch('hideLoadingSwal');

        return response()->streamDownload(fn () => print($pdf->output()), $this->namaFile('pdf'));
    }

    public function sortBy($column)
    {
        if (! in_array($column, self::SORTABLE, true)) {
            return;
        }

        $this->sortDirection = $this->sortColumn === $column && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortColumn = $column;
    }

    public function edit($id)
    {
        $dokumentasi = DokumentasiProdukHukum::findOrFail($id);
        $this->authorize('update', $dokumentasi);

        $this->resetForm();

        $this->editId = $dokumentasi->id;
        $this->editTahun = $dokumentasi->tahun;
        $this->nomor = $dokumentasi->nomor;
        [$this->nomor_berita, $this->tahun_berita] = array_pad(explode('/', (string) $dokumentasi->nomor_tahun_berita, 2), 2, '');
        $this->tanggal_penetapan = $dokumentasi->tanggal_penetapan
            ? \Illuminate\Support\Carbon::parse($dokumentasi->tanggal_penetapan)->format('Y-m-d')
            : null;

        $this->dispatch('openModal', 'modalEditDokumentasi');
    }

    public function update()
    {
        $dokumentasi = DokumentasiProdukHukum::findOrFail($this->editId);
        $this->authorize('update', $dokumentasi);

        $this->validate([
            'nomor' => 'required|numeric|digits_between:1,3',
            'nomor_berita' => 'required|numeric',
            'tahun_berita' => 'required|numeric|digits:4',
            'tanggal_penetapan' => 'required|date',
            'file_produk_hukum' => 'nullable|mimes:pdf|max:20480',
        ]);

        // Cek nomor ganda sebelum menyentuh file agar file lama tidak hilang saat gagal.
        if (DokumentasiProdukHukum::nomorDipakai($this->nomor, $dokumentasi->tahun, $dokumentasi->id)) {
            $this->addError('nomor', "Nomor {$this->nomor} untuk tahun {$dokumentasi->tahun} sudah digunakan.");
            return;
        }

        $data = [
            'nomor' => $this->nomor,
            'nomor_tahun_berita' => "{$this->nomor_berita}/{$this->tahun_berita}",
            'tanggal_penetapan' => $this->tanggal_penetapan,
        ];

        $fileLama = $dokumentasi->file_produk_hukum;
        if ($this->file_produk_hukum) {
            $data['file_produk_hukum'] = $this->file_produk_hukum->store('dokumentasi/file_produk_hukum', 'local');
        }

        $dokumentasi->update($data);

        if ($this->file_produk_hukum && $fileLama && $fileLama !== $data['file_produk_hukum'] && Storage::exists($fileLama)) {
            Storage::delete($fileLama);
        }

        Notification::send(User::role('Verifikator')->get(), new DokumentasiNotification([
            'title' => 'Dokumentasi Produk Hukum Diperbarui',
            'message' => "Dokumentasi produk hukum dengan Nomor {$this->nomor} telah diperbarui.",
            'type' => 'dokumentasi_diperbarui',
        ]));

        $this->dispatch('closeModal', 'modalEditDokumentasi');
        $this->dispatch('swal:toast', [
            'type' => 'success',
            'message' => 'Dokumentasi berhasil diperbarui.',
        ]);
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->reset(['editId', 'editTahun', 'nomor', 'nomor_berita', 'tahun_berita', 'tanggal_penetapan', 'file_produk_hukum']);
        $this->resetValidation();
    }

    public function removeFile()
    {
        $this->reset('file_produk_hukum');
        $this->resetValidation('file_produk_hukum');
    }

    public function delete($id)
    {
        $dokumentasi = DokumentasiProdukHukum::with('rancangan')->findOrFail($id);
        $this->authorize('delete', $dokumentasi);

        if ($dokumentasi->file_produk_hukum && Storage::exists($dokumentasi->file_produk_hukum)) {
            Storage::delete($dokumentasi->file_produk_hukum);
        }

        $dokumentasi->delete();

        Notification::send(User::role('Verifikator')->get(), new DokumentasiNotification([
            'title' => 'Dokumentasi Produk Hukum Dihapus',
            'message' => "Dokumentasi dengan {$dokumentasi->nomor_formatted} telah dihapus dari sistem.",
            'type' => 'dokumentasi_dihapus',
            'slug' => $dokumentasi->rancangan?->slug,
        ]));

        // Daftar rancangan pada form tambah perlu dimuat ulang.
        $this->dispatch('loadData');
        $this->dispatch('swal:toast', [
            'type' => 'success',
            'message' => 'Dokumentasi berhasil dihapus.',
        ]);
    }

    public function render()
    {
        $dokumentasiList = DokumentasiProdukHukum::with('rancangan', 'perangkatDaerah')
            ->filter($this->search, $this->jenisRancangan, $this->tahun)
            ->orderBy(in_array($this->sortColumn, self::SORTABLE, true) ? $this->sortColumn : 'tanggal_pengarsipan', $this->sortDirection === 'asc' ? 'asc' : 'desc')
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        return view('livewire.dokumentasi.dokumentasi', [
            'dokumentasiList' => $dokumentasiList,
            'totalDokumentasi' => DokumentasiProdukHukum::count(),
            'jenisOptions' => DokumentasiProdukHukum::query()->distinct()->orderBy('jenis_dokumentasi')->pluck('jenis_dokumentasi')->filter(),
            'tahunOptions' => DokumentasiProdukHukum::query()->distinct()->orderByDesc('tahun')->pluck('tahun'),
        ])->layout('layouts.app');
    }
}
