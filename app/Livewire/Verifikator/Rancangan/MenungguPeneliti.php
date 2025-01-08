<?php

namespace App\Livewire\Verifikator\Rancangan;

use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;
use App\Models\RancanganProdukHukum;
use App\Models\User;
use App\Models\Revisi;
use Illuminate\Support\Facades\Notification;
use App\Notifications\PilihPenelitiNotification;

class MenungguPeneliti extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $search = '';
    public $perPage = 5;

    public $selectedRancangan;
    public $selectedPeneliti;


    protected $rules = [
        'selectedPeneliti' => 'required|exists:users,id',
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function openModal($id)
    {

        $this->selectedRancangan = RancanganProdukHukum::find($id);
        $this->dispatch('openModalPilihPeneliti');
    }

    public function assignPeneliti()
    {
        $this->validate();

        // Buat atau update entri di tabel Revisi
        $revisi = Revisi::updateOrCreate(
            [
                'id_rancangan' => $this->selectedRancangan->id_rancangan, // Pastikan id_rancangan dari rancangan yang dipilih
            ],
            [
                'id_user' => $this->selectedPeneliti, // ID Peneliti yang dipilih
                'status_revisi' => 'Menunggu Revisi'
            ]
        );

        // Kirim notifikasi ke peneliti
        $peneliti = User::find($this->selectedPeneliti);
        Notification::send($peneliti, new \App\Notifications\PilihPenelitiNotification([
            'title' => 'Penugasan Baru',
            'message' => "Anda telah ditugaskan untuk meneliti rancangan: {$this->selectedRancangan->tentang}",
            'slug' => $this->selectedRancangan->slug,
        ]));

        // Tutup modal dan tampilkan notifikasi sukses
        $this->dispatch('closeModalPilihPeneliti');
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Peneliti Dipilih',
            'message' => 'Peneliti berhasil ditugaskan untuk revisi rancangan.',
        ]);
    }


    public function render()
    {
        // Query Rancangan dengan Status Berkas "Disetujui" dan Status Revisi "Belum Tahap Revisi"
        $rancangan = RancanganProdukHukum::with('revisi') // Eager loading revisi
            ->where('status_berkas', 'Disetujui')
            ->whereHas('revisi', function ($query) {
                $query->where('status_revisi', 'Belum Tahap Direvisi');
            })
            ->where(function ($query) {
                $query->where('tentang', 'like', "%{$this->search}%")
                    ->orWhere('no_rancangan', 'like', "%{$this->search}%");
            })
            ->paginate($this->perPage);


        // Dapatkan daftar user dengan role "peneliti"
        $listPeneliti = User::role('peneliti')->get();

        return view('livewire.verifikator.rancangan.menunggu-peneliti', compact('rancangan', 'listPeneliti'));
    }
}
