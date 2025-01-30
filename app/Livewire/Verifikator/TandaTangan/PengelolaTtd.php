<?php

namespace App\Livewire\Verifikator\TandaTangan;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TandaTangan;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class PengelolaTtd extends Component
{

    use WithPagination, WithFileUploads;

    public $search = '';
    public $perPage = 5;
    public $nama_ttd, $file_ttd, $status = 'Aktif';
    public $selectedTtdId;

    protected $listeners = ['refreshTtdList' => 'render'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openModalTambah()
    {
        $this->resetForm();
        $this->dispatch('openModalTambahTtd');
    }

    public function openModalEdit($id)
    {
        $ttd = TandaTangan::findOrFail($id);
        $this->selectedTtdId = $ttd->id;
        $this->nama_ttd = $ttd->nama_ttd;
        $this->status = $ttd->status;
        $this->dispatch('openModalEditTtd');
    }

    public function store()
    {
        $this->validate([
            'nama_ttd' => 'required|string|max:100',
            'file_ttd' => 'required|image|mimes:png|max:2048', // Hanya menerima PNG dengan maksimal 2MB
            'status' => 'required|in:Aktif,Tidak Aktif',
        ]);

        // Simpan gambar ke storage langsung
        $path = $this->file_ttd->storeAs('tanda_tangan/ttd', 'ttd_' . uniqid() . '.png', 'local');

        // Simpan ke database
        TandaTangan::create([
            'nama_ttd' => $this->nama_ttd,
            'file_ttd' => $path,
            'dibuat_oleh' => auth()->id(),
            'status' => $this->status
        ]);

        // Reset form
        $this->reset(['nama_ttd', 'file_ttd', 'status']);

        // Tutup modal dan tampilkan notifikasi sukses
        $this->dispatch('closeModalTambahTtd');
        $this->dispatch('swal:ttd', [
            'type' => 'success',
            'title' => 'Berhasil',
            'message' => "TTD berhasil ditambahkan",
        ]);
    }


    public function update()
    {
        $this->validate([
            'nama_ttd' => 'required|string|max:100',
            'status' => 'required|in:Aktif,Tidak Aktif'
        ]);

        $ttd = TandaTangan::findOrFail($this->selectedTtdId);

        if ($this->file_ttd) {
            Storage::disk('public')->delete($ttd->file_ttd);
            $ttd->file_ttd = $this->file_ttd->store('tanda_tangan', 'public');
        }

        $ttd->update([
            'nama_ttd' => $this->nama_ttd,
            'status' => $this->status
        ]);

        $this->dispatch('closeModalEditTtd');
        $this->dispatch('swal:modal', ['type' => 'success', 'message' => 'Tanda tangan berhasil diperbarui!']);
    }

    public function delete($id)
    {
        $ttd = TandaTangan::findOrFail($id);
        Storage::disk('public')->delete($ttd->file_ttd);
        $ttd->delete();

        $this->dispatch('swal:modal', ['type' => 'success', 'message' => 'Tanda tangan berhasil dihapus!']);
    }

    public function resetForm()
    {
        $this->reset(['nama_ttd', 'file_ttd', 'status', 'selectedTtdId']);
    }

    public function render()
    {
        $ttdList = TandaTangan::where('nama_ttd', 'like', "%{$this->search}%")
            ->paginate($this->perPage);

        return view('livewire.verifikator.tanda-tangan.pengelola-ttd', compact('ttdList'))->layout('layouts.app');
    }
}
