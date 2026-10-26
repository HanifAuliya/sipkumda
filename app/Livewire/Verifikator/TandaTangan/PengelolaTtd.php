<?php

namespace App\Livewire\Verifikator\TandaTangan;

use App\Models\TandaTangan;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;

class PengelolaTtd extends Component
{
    use WithPagination, WithoutUrlPagination, WithFileUploads;

    public $search = '';
    public $perPage = 5;

    // Form tambah/edit (editId null = tambah)
    public $editId;
    public $nama_ttd, $nip_ttd, $jabatan, $status, $file_ttd;
    public $existingFile;

    protected $validationAttributes = [
        'nama_ttd' => 'nama lengkap',
        'nip_ttd' => 'NIP',
        'file_ttd' => 'gambar tanda tangan',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function updatedFileTtd()
    {
        $this->validateOnly('file_ttd', ['file_ttd' => 'nullable|image|mimes:png|max:2048']);
    }

    public function openModalTambah()
    {
        $this->resetForm();
        $this->dispatch('openModal', 'modalTtd');
    }

    public function openModalEdit($id)
    {
        $this->resetForm();

        $ttd = TandaTangan::findOrFail($id);

        $this->editId = $ttd->id;
        $this->nama_ttd = $ttd->nama_ttd;
        $this->nip_ttd = $ttd->nip_ttd;
        $this->jabatan = $ttd->jabatan;
        $this->status = $ttd->status;
        $this->existingFile = $ttd->file_ttd;

        $this->dispatch('openModal', 'modalTtd');
    }

    public function save()
    {
        $this->validate([
            'nama_ttd' => 'required|string|max:100',
            'nip_ttd' => 'required|string|max:50|unique:tanda_tangan,nip_ttd' . ($this->editId ? ',' . $this->editId : ''),
            'jabatan' => 'required|string|max:100',
            'file_ttd' => ($this->editId ? 'nullable' : 'required') . '|image|mimes:png|max:2048',
            'status' => 'required|in:Aktif,Tidak Aktif',
        ]);

        $data = [
            'nama_ttd' => $this->nama_ttd,
            'nip_ttd' => $this->nip_ttd,
            'jabatan' => $this->jabatan,
            'status' => $this->status,
        ];

        // File lama tidak dihapus karena masih dipakai saat Nota Dinas lama dibuat ulang.
        if ($this->file_ttd) {
            $data['file_ttd'] = $this->file_ttd->storeAs('tanda_tangan/ttd', 'ttd_' . uniqid() . '.png', 'local');
        }

        if ($this->editId) {
            TandaTangan::findOrFail($this->editId)->update($data);
        } else {
            TandaTangan::create($data + ['dibuat_oleh' => auth()->id()]);
        }

        $this->dispatch('closeModal', 'modalTtd');
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Berhasil',
            'message' => $this->editId ? 'TTD berhasil diperbarui.' : 'TTD berhasil ditambahkan.',
        ]);
        $this->resetForm();
    }

    public function removeFile()
    {
        $this->reset('file_ttd');
        $this->resetValidation('file_ttd');
    }

    public function delete($id)
    {
        $ttd = TandaTangan::withCount('notaDinas')->findOrFail($id);

        // Relasi nota_dinas memakai cascade: menghapus TTD akan ikut menghapus Nota Dinas.
        if ($ttd->nota_dinas_count > 0) {
            $this->dispatch('swal:modal', [
                'type' => 'warning',
                'title' => 'Tidak dapat dihapus',
                'message' => "TTD ini dipakai di {$ttd->nota_dinas_count} Nota Dinas. Ubah statusnya menjadi Tidak Aktif sebagai gantinya.",
            ]);
            return;
        }

        $ttd->delete();
        $this->resetPage();

        $this->dispatch('swal:toast', [
            'type' => 'success',
            'message' => 'TTD berhasil dihapus.',
        ]);
    }

    public function resetForm()
    {
        $this->reset(['editId', 'nama_ttd', 'nip_ttd', 'jabatan', 'status', 'file_ttd', 'existingFile']);
        $this->resetValidation();
    }

    public function render()
    {
        $ttdList = TandaTangan::withCount('notaDinas')
            ->where(function ($query) {
                $query->where('nama_ttd', 'like', "%{$this->search}%")
                    ->orWhere('nip_ttd', 'like', "%{$this->search}%")
                    ->orWhere('jabatan', 'like', "%{$this->search}%");
            })
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.verifikator.tanda-tangan.pengelola-ttd', compact('ttdList'))->layout('layouts.app');
    }
}
