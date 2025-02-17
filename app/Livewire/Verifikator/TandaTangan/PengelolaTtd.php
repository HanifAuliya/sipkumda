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
    public $editId;
    public $nama_ttd, $file_ttd, $status;
    public $file_ttd_preview; // Untuk menampilkan preview file
    public $existingFile;

    protected $listeners = [
        'refreshTtdList' => 'render',
        'refreshTable' => '$refresh',
        'deleteConfirmedTtd' => 'delete'
    ];


    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openModalTambah()
    {
        $this->resetForm();
        $this->dispatch('openModalTambahTtd');
    }


    public function updatedFileTtd()
    {
        if ($this->file_ttd) {
            $this->validate([
                'file_ttd' => 'image|mimes:png|max:2048', // Hanya menerima PNG dengan maksimal 2MB
            ]);
            $this->file_ttd_preview = $this->file_ttd->temporaryUrl(); // Simpan preview file
        }
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

    public function openModalEdit($id)
    {
        $ttd = TandaTangan::findOrFail($id);

        $this->editId = $ttd->id;
        $this->nama_ttd = $ttd->nama_ttd;
        $this->status = $ttd->status;
        $this->existingFile = $ttd->file_ttd; // Simpan file lama

        // Buka modal edit
        $this->dispatch('openModalEditTtd');
    }


    public function update()
    {
        $this->validate([
            'nama_ttd' => 'required|string|max:100',
            'file_ttd' => 'nullable|image|mimes:png|max:2048', // Bisa kosong jika tidak mengganti
            'status' => 'required|in:Aktif,Tidak Aktif',
        ]);

        $ttd = TandaTangan::findOrFail($this->editId);

        // Simpan file baru jika diunggah
        if ($this->file_ttd) {
            $path = $this->file_ttd->storeAs('tanda_tangan/ttd', 'ttd_' . uniqid() . '.png', 'local');
            $ttd->file_ttd = $path; // Update file TTD baru
        }

        // Update data
        $ttd->update([
            'nama_ttd' => $this->nama_ttd,
            'status' => $this->status,
        ]);

        // Tutup modal edit
        $this->dispatch('closeModalEditTtd');

        // Refresh tabel
        $this->dispatch('refreshTable');

        // Tampilkan notifikasi sukses
        $this->dispatch('swal:ttd', [
            'type' => 'success',
            'title' => 'Berhasil',
            'message' => "TTD berhasil diperbarui",
        ]);
    }

    public function removeFile()
    {
        $this->file_ttd = null;
        $this->file_ttd_preview = null;
    }


    public function delete($id)
    {
        if (is_array($id)) {
            $id = $id['id']; // Ambil ID jika dikirim dalam array
        }

        $ttd = TandaTangan::findOrFail($id);
        $ttd->delete();

        // Perbarui tampilan tanpa dispatch tambahan
        $this->resetPage();

        // Tampilkan notifikasi sukses
        $this->dispatch('swal:ttd', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'TTD berhasil dihapus.',
        ]);
    }




    public function resetForm()
    {
        $this->reset(['nama_ttd', 'file_ttd', 'status',]);
    }

    public function render()
    {
        $ttdList = TandaTangan::where('nama_ttd', 'like', "%{$this->search}%")
            ->paginate($this->perPage);

        return view('livewire.verifikator.tanda-tangan.pengelola-ttd', compact('ttdList'))->layout('layouts.app');
    }
}
