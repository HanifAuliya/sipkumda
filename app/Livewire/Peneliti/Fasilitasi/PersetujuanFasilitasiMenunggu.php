<?php

namespace App\Livewire\Peneliti\Fasilitasi;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\FasilitasiProdukHukum;
use App\Models\User;
use App\Notifications\PersetujuanRancanganNotification;

class PersetujuanFasilitasiMenunggu extends Component
{
    use WithPagination;

    public $search = ''; // Pencarian
    public $perPage = 5; // Default jumlah data per halaman

    public $selectedFasilitasi;
    public $statusBerkas;
    public $catatan;

    // Rules Validasi
    protected $rules = [
        'statusBerkas' => 'required|in:Disetujui,Ditolak',
        'catatan' => 'required|string|min:5',
    ];

    protected $messages = [
        'statusBerkas.required' => 'Status persetujuan harus dipilih.',
        'catatan.required' => 'Catatan wajib diisi.',
        'catatan.min' => 'Catatan minimal 5 karakter.',
    ];

    public function openDetailFasilitasi($fasilitasiId)
    {
        $this->selectedFasilitasi = FasilitasiProdukHukum::with('rancangan')->findOrFail($fasilitasiId);

        // Dispatch event untuk membuka modal di JavaScript
        $this->dispatch('openModalDetailFasilitasi');
    }

    public function updateStatus()
    {
        $this->validate();

        if ($this->selectedFasilitasi) {
            $this->selectedFasilitasi->status_berkas_fasilitasi = $this->statusBerkas;
            $this->selectedFasilitasi->catatan_persetujuan_fasilitasi = $this->catatan;

            if ($this->statusBerkas === 'Disetujui') {
                $this->selectedFasilitasi->status_validasi_fasilitasi = 'Menunggu Validasi';
                $this->selectedFasilitasi->tanggal_persetujuan_berkas = now();

                // Kirim notifikasi ke semua Verifikator
                $verifikators = User::role('Verifikator')->get(); // Ambil semua user dengan role Verifikator
                foreach ($verifikators as $verifikator) {
                    $verifikator->notify(new PersetujuanRancanganNotification([
                        'title' => "⏳📜 Fasilitasi Baru Menunggu Validasi!",
                        'message' => "🔍 Fasilitasi rancangan dengan nomor *{$this->selectedFasilitasi->rancangan->no_rancangan}* telah *disesuaikan* dengan hasil koreksi ✏️📑. Harap menunggu proses validasi selanjutnya. ⚖️⏳",
                        'slug' => $this->selectedFasilitasi->rancangan->slug,
                        'type' => 'fasilitasi_verifikasi',

                    ]));
                }
            }

            $this->selectedFasilitasi->save();

            // Kirim notifikasi ke Perangkat Daerah
            $this->selectedFasilitasi->rancangan->user->notify(new PersetujuanRancanganNotification([
                'title' => $this->statusBerkas === 'Disetujui'
                    ? "🎉📜 Fasilitasi Rancangan No. {$this->selectedFasilitasi->rancangan->no_rancangan} Disetujui! ✅"
                    : "❌📜 Fasilitasi Rancangan No. {$this->selectedFasilitasi->rancangan->no_rancangan} Ditolak!",

                'message' => $this->statusBerkas === 'Disetujui'
                    ? "🎊 Selamat! Fasilitasi Anda dengan nomor *{$this->selectedFasilitasi->rancangan->no_rancangan}* telah *disetujui* ✅. Mohon menunggu proses validasi selanjutnya 🔄📋."
                    : "⚠️ Mohon maaf, fasilitasi Anda dengan nomor *{$this->selectedFasilitasi->rancangan->no_rancangan}* *ditolak* ❌. Silakan periksa *catatan koreksi* 📝 dan sesuaikan revisi sesuai instruksi yang diberikan 🔄✏️. Semangat! 💪😊",

                'slug' => $this->selectedFasilitasi->rancangan->slug,
                'type' => $this->statusBerkas === 'Disetujui' ? 'fasilitasi_diterima' : 'fasilitasi_ditolak',

            ]));

            // Notifikasi Sukses
            $this->dispatch('swal:toast', [
                'type' => 'success',
                'message' => 'Status Berkas fasilitasi berhasil diperbarui!',
            ]);

            // Reset Data
            $this->resetForm();
            $this->dispatch('closeModalDetailFasilitasi'); // Tutup modal setelah aksi sukses
        }
    }

    public function resetForm()
    {
        $this->reset(['statusBerkas', 'catatan', 'selectedFasilitasi']);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function updatingSearch()
    {
        $this->resetPage(); // Reset halaman ke 1 saat pencarian berubah
    }

    public function render()
    {
        $fasilitasiMenunggu = FasilitasiProdukHukum::where('status_berkas_fasilitasi', 'Menunggu Persetujuan')
            ->whereHas('rancangan', function ($query) {
                $query->where('tentang', 'like', "%{$this->search}%")
                    ->orWhere('no_rancangan', 'like', "%{$this->search}%");
            })
            ->orderBy('tanggal_fasilitasi', 'desc') // Urutkan berdasarkan tanggal fasilitasi (terbaru di atas)
            ->paginate($this->perPage);

        return view('livewire.peneliti.fasilitasi.persetujuan-fasilitasi-menunggu', compact('fasilitasiMenunggu'));
    }
}
