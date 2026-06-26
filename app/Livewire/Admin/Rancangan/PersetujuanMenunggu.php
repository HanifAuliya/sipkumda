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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Services\KelengkapanBerkasChecker;

class PersetujuanMenunggu extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $search  = '';
    public $perPage = 5;

    public $selectedRancangan;
    public $catatan;
    public $statusBerkas;

    protected $rules = [
        'statusBerkas' => 'required|in:Disetujui,Ditolak',
        'catatan'      => 'required|string|min:5',
    ];

    protected $messages = [
        'statusBerkas.required' => 'Status harus dipilih.',
        'catatan.required'      => 'Catatan wajib diisi.',
        'catatan.min'           => 'Catatan minimal 5 karakter.',
        'catatan.max'           => 'Catatan maksimal 255 karakter.',
    ];

    // =========================================================
    // TAMBAHAN BARU: auto-prediksi saat halaman pertama load
    // Hanya untuk item yang hasil_prediksi_kelengkapan masih null
    // =========================================================
    // public function mount()
    // {
    //     $itemBelumDicek = RancanganProdukHukum::whereIn('status_berkas', ['Menunggu Persetujuan'])
    //         ->where('status_rancangan', 'Dalam Proses')
    //         ->whereNull('hasil_prediksi_kelengkapan')
    //         ->get();

    //     foreach ($itemBelumDicek as $item) {
    //         $this->jalankanPrediksi($item->id_rancangan);
    //     }
    // }

    // =========================================================
    // TIDAK DIUBAH — semua kode asli di bawah ini sama persis
    // =========================================================

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function openModal($id)
    {
        $this->selectedRancangan = RancanganProdukHukum::with(['user', 'perangkatDaerah'])->find($id);
        if (!$this->selectedRancangan || Gate::denies('view', $this->selectedRancangan)) {
            abort(403, 'Unauthorized access');
        }
        $this->catatan      = $this->selectedRancangan->catatan_berkas ?? '';
        $this->statusBerkas = $this->selectedRancangan->status_berkas ?? '';
        $this->dispatch('openModal', 'modalPersetujuan');
    }

    public function updateStatus()
    {
        $this->validate();

        if ($this->selectedRancangan) {
            $this->selectedRancangan->status_berkas  = $this->statusBerkas;
            $this->selectedRancangan->catatan_berkas = $this->catatan;

            if ($this->statusBerkas === 'Disetujui') {
                $this->selectedRancangan->tanggal_berkas_disetujui = Carbon::now();

                $revisi = $this->selectedRancangan->revisi()->first();
                if ($revisi) {
                    $revisi->update(['status_revisi' => 'Menunggu Peneliti']);
                }

                $verifikator = User::role('Verifikator')->get();
                Notification::send(
                    $verifikator,
                    new PersetujuanRancanganNotification([
                        'title'   => "Berkas Rancangan No. {$this->selectedRancangan->no_rancangan} Disetujui! ",
                        'message' => " Berkas Rancangan dengan nomor *{$this->selectedRancangan->no_rancangan}* telah berhasil disetujui!  Harap segera *memilih peneliti* untuk melanjutkan proses berikutnya. ",
                        'slug'    => $this->selectedRancangan->slug,
                        'type'    => 'pilih_peneliti',
                    ])
                );
            } else {
                $this->selectedRancangan->tanggal_berkas_disetujui = null;
            }

            $this->selectedRancangan->save();

            Notification::send(
                $this->selectedRancangan->user,
                new PersetujuanRancanganNotification([
                    'title'   => $this->statusBerkas === 'Disetujui'
                        ? " Berkas Rancangan No. {$this->selectedRancangan->no_rancangan} Disetujui!"
                        : " Berkas Rancangan No. {$this->selectedRancangan->no_rancangan} Ditolak!",
                    'message' => $this->statusBerkas === 'Disetujui'
                        ? " Selamat! Berkas Rancangan Anda telah *disetujui* . Proses selanjutnya adalah *penugasan Peneliti* . Mohon menunggu pemilihan peneliti."
                        : " Mohon maaf, Berkas Rancangan Anda *ditolak* . Silakan periksa *catatan revisi* 📝 dan lakukan perbaikan sebelum mengajukan ulang. ",
                    'slug'    => $this->selectedRancangan->slug,
                    'type'    => $this->statusBerkas === 'Disetujui' ? 'persetujuan_diterima' : 'persetujuan_ditolak',
                ])
            );

            $this->dispatch('refreshNotifications');
            $this->resetForm();
            $this->dispatch('closeModal', 'modalPersetujuan');
            $this->dispatch('swal:toast', [
                'type'    => 'success',
                'message' => 'Status rancangan berhasil diperbarui!',
            ]);
        }
    }

    public function resetForm()
    {
        $this->selectedRancangan = null;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function jalankanPrediksi($id)
    {
        $item = RancanganProdukHukum::find($id);
        if (!$item) return;

        if (
            !is_null($item->hasil_prediksi_kelengkapan) &&
            !in_array($item->status_berkas, ['Menunggu Persetujuan', 'Ditolak'])
        ) return;

        try {
            // Buat temporary file object dari Storage agar bisa dipakai Checker
            $files = [];

            foreach (
                [
                    'nota_dinas_pd' => 'nota_dinas',
                    'rancangan'     => 'rancangan',
                    'matrik'        => 'matrik',
                ] as $kolom => $jenis
            ) {
                if ($item->$kolom && Storage::exists($item->$kolom)) {
                    $tmpPath  = tempnam(sys_get_temp_dir(), $jenis . '_');
                    $ext      = pathinfo($item->$kolom, PATHINFO_EXTENSION);
                    $tmpFile  = $tmpPath . '.' . $ext;
                    file_put_contents($tmpFile, Storage::get($item->$kolom));

                    // Bungkus jadi UploadedFile agar kompatibel dengan Checker
                    $files[$jenis] = new \Illuminate\Http\UploadedFile(
                        $tmpFile,
                        basename($item->$kolom),
                        null,
                        null,
                        true // test mode — skip is_uploaded_file check
                    );
                }
            }

            if (empty($files)) return;

            $checker = new KelengkapanBerkasChecker();
            $hasil   = $checker->check(
                $files['nota_dinas'] ?? null,
                $files['rancangan']  ?? null,
                $files['matrik']     ?? null,
            );

            $item->hasil_prediksi_kelengkapan = $hasil['hasil'];
            $item->catatan_berkas             = count($hasil['catatan']) > 0
                ? implode('; ', $hasil['catatan'])
                : null;
            $item->save();

            // Hapus file temporary
            foreach ($files as $f) {
                @unlink($f->getRealPath());
            }

            if (app()->runningInConsole() === false && request()->hasHeader('x-livewire')) {
                $this->dispatch('swal:toast', [
                    'type'    => 'success',
                    'message' => "Prediksi berhasil: {$hasil['hasil']}",
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Gagal prediksi: " . $e->getMessage());
            $this->dispatch('swal:toast', [
                'type'    => 'error',
                'message' => "Gagal menjalankan pemeriksaan kelengkapan.",
            ]);
        }
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
            ->orderBy('tanggal_pengajuan', 'desc')
            ->paginate($this->perPage);

        return view('livewire.admin.rancangan.persetujuan-menunggu', compact('rancanganMenunggu'));
    }
}
