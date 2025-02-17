<?php

namespace App\Livewire\PerangkatDaerah\Fasilitasi;

use Livewire\Component;
use App\Models\FasilitasiProdukHukum;
use App\Models\NotaDinas;

use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use Barryvdh\DomPDF\Facade\Pdf;

class RiwayatFasilitasi extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $search = ''; // Pencarian
    public $perPage = 10; // Default jumlah item per halaman
    public $selectedFasilitasi = null;

    public function updatingSearch()
    {
        // Reset halaman ke halaman pertama saat pencarian berubah
        $this->resetPage();
    }
    public function refreshFasilitasi()
    {
        $this->resetPage(); // Reset ke halaman pertama
    }

    public function openDetailFasilitasi($fasilitasiId)
    {
        $this->selectedFasilitasi = FasilitasiProdukHukum::with('rancangan')->findOrFail($fasilitasiId);

        // Dispatch event untuk membuka modal di JavaScript
        $this->dispatch('openModalDetailFasilitasi');
    }

    public function generatePDF($notaId)
    {
        $notaDinas = NotaDinas::with('fasilitasi.rancangan.user.perangkatDaerah', 'tandaTangan')->findOrFail($notaId);
        $pdf = Pdf::loadView('pdf.nota-dinas', compact('notaDinas'))->setPaper('A4', 'portrait');

        // Kirim event ke browser untuk menutup SweetAlert setelah PDF selesai dibuat
        $this->dispatch('hideLoadingSwal');

        // 🔹 Hapus karakter yang tidak valid
        $safeFilename = preg_replace('/[^A-Za-z0-9\-_]/', '-', $notaDinas->nomor_nota);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, "Nota-Dinas-{$safeFilename}.pdf");
    }

    public function render()
    {
        $userId = auth()->id(); // Ambil ID pengguna yang login

        $riwayatFasilitasi = FasilitasiProdukHukum::with('rancangan')
            ->where('status_validasi_fasilitasi', 'Diterima') // Hanya fasilitasi yang sudah diterima
            ->where('status_paraf_koordinasi', 'Selesai')
            ->where('status_asisten', 'Selesai')
            ->where('status_sekda', 'Selesai')
            ->where('status_bupati', 'Selesai')
            ->whereHas('rancangan', function ($query) use ($userId) {
                $query->where('status_rancangan', 'Disetujui') // Hanya rancangan yang sudah disetujui
                    ->where('id_user', $userId) // Hanya rancangan milik pengguna yang login
                    ->where(function ($subQuery) {
                        $subQuery->where('tentang', 'like', "%{$this->search}%")
                            ->orWhere('no_rancangan', 'like', "%{$this->search}%");
                    });
            })
            ->orderBy('tanggal_validasi_fasilitasi', 'desc') // Urutkan berdasarkan tanggal fasilitasi (terbaru di atas)
            ->paginate($this->perPage);

        return view('livewire.perangkatdaerah.fasilitasi.riwayat-fasilitasi', compact('riwayatFasilitasi'));
    }
}
