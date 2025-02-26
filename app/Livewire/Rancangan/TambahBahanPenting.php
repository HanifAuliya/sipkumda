<?php

namespace App\Livewire\Rancangan;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\ImportantMaterial;
use Illuminate\Support\Facades\Storage;

class TambahBahanPenting extends Component
{

    use WithFileUploads;

    public $buttonName;
    public $file;
    public $materials;

    protected $rules = [
        'buttonName' => 'required|string|max:255',
        'file' => 'required|file|mimes:doc,docx,pdf|max:2048',
    ];
    protected $listeners = ['deleteConfirmed' => 'deleteMaterial'];

    public function mount()
    {
        // Ambil data bahan penting dari database
        $this->materials = ImportantMaterial::all();
    }

    public function deleteMaterial($id)
    {
        // Temukan data berdasarkan ID
        $material = ImportantMaterial::findOrFail($id);

        // Hapus file dari storage
        Storage::disk('public')->delete($material->file_path);

        // Hapus data dari database
        $material->delete();

        // Refresh data
        $this->materials = ImportantMaterial::all();

        // Notifikasi sukses
        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Data berhasil dihapus!',
        ]);
    }

    public function addMaterial()
    {
        $this->validate();

        // Jika ada file yang diunggah, simpan data ke database
        if ($this->file) {
            // Bersihkan nama file
            $fileName = preg_replace('/[^A-Za-z0-9\-_.]/', '_', $this->file->getClientOriginalName());

            // Simpan file dengan nama yang sudah dibersihkan
            $filePath = $this->file->storeAs('public/templates', $fileName);

            // Simpan data ke database
            ImportantMaterial::create([
                'button_name' => $this->buttonName,
                'file_path' => $filePath,
            ]);

            // Reset input dan file
            $this->reset(['buttonName', 'file']);
            $this->file = null; // Menghapus file setelah dibuat

            // Refresh data
            $this->materials = ImportantMaterial::all();

            // Notifikasi sukses
            $this->dispatch('swal:modal', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Rancangan berhasil ditambahkan!',
            ]);
        }
    }
    // Method untuk menghapus file yang telah diunggah
    public function removeFile()
    {
        $this->file = null;
        // Refresh data bahan penting
        $this->materials = ImportantMaterial::all();
    }


    public function download($id)
    {
        $material = ImportantMaterial::findOrFail($id);
        return Storage::download($material->file_path, $material->button_name . '.' . pathinfo($material->file_path, PATHINFO_EXTENSION));
    }

    public function getFileIcon($filePath)
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        switch ($extension) {
            case 'pdf':
                return 'bi bi-file-earmark-pdf text-danger'; // Ikon untuk PDF
            case 'doc':
            case 'docx':
                return 'bi bi-file-earmark-word text-primary'; // Ikon untuk Word
            case 'xls':
            case 'xlsx':
                return 'bi bi-file-earmark-excel text-warning'; // Ikon untuk Excel
            default:
                return 'bi bi-file-earmark'; // Ikon default
        }
    }

    public function render()
    {
        return view('livewire.rancangan.tambah-bahan-penting')->layout('layouts.app');
    }
}
