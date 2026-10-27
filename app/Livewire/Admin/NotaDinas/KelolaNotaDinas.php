<?php

namespace App\Livewire\Admin\NotaDinas;

use App\Models\FasilitasiProdukHukum;
use App\Models\NotaDinas;
use App\Models\TandaTangan;
use App\Models\User;
use App\Notifications\ValidationResultNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;

class KelolaNotaDinas extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $search = '';
    public $perPage = 10;

    // Form tambah nota dinas
    public $fasilitasiId;
    public $tandaTanganId;
    public $nomorInputan;
    public $kepada;

    protected $validationAttributes = [
        'fasilitasiId' => 'fasilitasi',
        'tandaTanganId' => 'tanda tangan',
        'nomorInputan' => 'nomor nota',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    /** Fasilitasi yang sudah diterima dan belum memiliki nota dinas. */
    private function fasilitasiTersedia()
    {
        return FasilitasiProdukHukum::where('status_validasi_fasilitasi', 'Diterima')
            ->whereDoesntHave('notaDinas');
    }

    /** Kode jenis untuk nomor nota: NDPB (Peraturan Bupati) atau NDSK. */
    private function kodeJenis(?string $jenisRancangan): string
    {
        return $jenisRancangan === 'Peraturan Bupati' ? 'NDPB' : 'NDSK';
    }

    public function openModalTambahNotaDinas()
    {
        $this->authorize('create', NotaDinas::class);

        $this->resetForm();
        $this->dispatch('openModal', 'modalTambahNota');
    }

    public function store()
    {
        $this->authorize('create', NotaDinas::class);

        $this->validate([
            'fasilitasiId' => 'required|integer',
            'tandaTanganId' => 'required|exists:tanda_tangan,id',
            'nomorInputan' => 'required|numeric|digits_between:1,3',
            'kepada' => 'required|string|max:255',
        ]);

        $fasilitasi = $this->fasilitasiTersedia()->with('rancangan.user')->find($this->fasilitasiId);

        if (! $fasilitasi) {
            $this->addError('fasilitasiId', 'Fasilitasi tidak tersedia atau sudah memiliki nota dinas.');
            return;
        }

        $jenisRancangan = $fasilitasi->rancangan->jenis_rancangan ?? '';
        $tahun = now()->year;

        // Nomor inputan harus unik untuk jenis rancangan yang sama di tahun berjalan ("013" sama dengan "13").
        $nomorDipakai = NotaDinas::whereRaw('CAST(nomor_inputan AS UNSIGNED) = ?', [(int) $this->nomorInputan])
            ->whereYear('tanggal_nota', $tahun)
            ->whereHas('fasilitasi.rancangan', fn ($query) => $query->where('jenis_rancangan', $jenisRancangan))
            ->exists();

        if ($nomorDipakai) {
            $this->addError('nomorInputan', "Nomor {$this->nomorInputan} sudah digunakan untuk {$jenisRancangan} tahun {$tahun}.");
            return;
        }

        $nomorNota = "180/{$this->nomorInputan}/{$this->kodeJenis($jenisRancangan)}/KUM/{$tahun}";

        NotaDinas::create([
            'fasilitasi_id' => $fasilitasi->id,
            'nomor_inputan' => $this->nomorInputan,
            'nomor_nota' => $nomorNota,
            'tanggal_nota' => now(),
            'tanda_tangan_id' => $this->tandaTanganId,
            'kepada' => $this->kepada,
        ]);

        $rancangan = $fasilitasi->rancangan;

        Notification::send(User::role('Verifikator')->get(), new ValidationResultNotification([
            'title' => 'Nota Dinas Baru Telah Dibuat!',
            'message' => "Nota Dinas dengan nomor {$nomorNota} untuk rancangan dengan nomor {$rancangan->no_rancangan} telah dibuat dan disimpan dalam sistem. Mohon cek dan pantau fasilitasi lebih lanjut.",
            'type' => 'nota_dinas_dibuat',
            'slug' => $rancangan->slug,
        ]));

        $rancangan->user?->notify(new ValidationResultNotification([
            'title' => 'Nota Dinas Fasilitasi Telah Dibuat!',
            'message' => "Nota Dinas untuk fasilitasi Rancangan {$rancangan->no_rancangan} Anda dengan nomor {$nomorNota} telah dibuat. Anda dapat mencetaknya di menu Daftar Nota Dinas.",
            'type' => 'nota_dinas_user',
            'slug' => $rancangan->slug,
        ]));

        $this->dispatch('closeModal', 'modalTambahNota');
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Nota Dinas Berhasil Disimpan',
            'message' => "Nota Dinas dengan Nomor {$nomorNota} berhasil dibuat!",
        ]);
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->reset(['fasilitasiId', 'tandaTanganId', 'nomorInputan', 'kepada']);
        $this->resetValidation();
    }

    public function deleteNotaDinas($id)
    {
        $notaDinas = NotaDinas::findOrFail($id);
        $this->authorize('delete', $notaDinas);

        $notaDinas->delete();

        $this->dispatch('swal:toast', [
            'type' => 'success',
            'message' => 'Nota Dinas berhasil dihapus.',
        ]);
    }

    public function generatePDF($notaId)
    {
        $notaDinas = NotaDinas::with('fasilitasi.rancangan.user.perangkatDaerah', 'tandaTangan')->findOrFail($notaId);
        $this->authorize('view', $notaDinas);

        $pdf = Pdf::loadView('pdf.nota-dinas', compact('notaDinas'))->setPaper('A4', 'portrait');

        $this->dispatch('hideLoadingSwal');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'Nota-Dinas-' . str_replace('/', '-', $notaDinas->nomor_nota) . '.pdf'
        );
    }

    public function render()
    {
        $user = auth()->user();
        $bisaKelola = $user->can('create', NotaDinas::class);

        $notaDinasList = NotaDinas::with(['fasilitasi.rancangan.user.perangkatDaerah', 'tandaTangan'])
            // Perangkat Daerah hanya melihat nota dinas miliknya.
            ->when($user->hasRole('Perangkat Daerah'), fn ($query) => $query->whereHas(
                'fasilitasi.rancangan',
                fn ($q) => $q->where('id_user', $user->id)
            ))
            ->when($this->search, fn ($query) => $query->where(function ($q) {
                $q->where('nomor_nota', 'like', "%{$this->search}%")
                    ->orWhereHas('fasilitasi.rancangan', fn ($sub) => $sub
                        ->where('no_rancangan', 'like', "%{$this->search}%")
                        ->orWhere('tentang', 'like', "%{$this->search}%"));
            }))
            ->orderByDesc('tanggal_nota')
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        $fasilitasiList = $bisaKelola ? $this->fasilitasiTersedia()->with('rancangan')->get() : collect();
        $jenisTerpilih = $fasilitasiList->firstWhere('id', (int) $this->fasilitasiId)?->rancangan?->jenis_rancangan;

        return view('livewire.admin.nota-dinas.kelola-nota-dinas', [
            'notaDinasList' => $notaDinasList,
            'bisaKelola' => $bisaKelola,
            'fasilitasiList' => $fasilitasiList,
            'tandaTanganList' => $bisaKelola ? TandaTangan::where('status', 'Aktif')->get() : collect(),
            'akhiranNomor' => '/' . ($this->fasilitasiId ? $this->kodeJenis($jenisTerpilih) : 'ND··') . '/KUM/' . now()->year,
        ])->layout('layouts.app');
    }
}
