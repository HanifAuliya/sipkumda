<?php

namespace App\Livewire\Rancangan;

use Livewire\Component;
use App\Models\ImportantMaterial;
use Illuminate\Support\Facades\Storage;


class BahanPentingDropdown extends Component
{
    public $buttonName;
    public $file;
    public $materials;


    public function mount()
    {
        // Ambil data bahan penting dari database
        $this->materials = ImportantMaterial::all();
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

    public function download($id)
    {
        $material = ImportantMaterial::findOrFail($id);
        return Storage::download($material->file_path, $material->button_name . '.' . pathinfo($material->file_path, PATHINFO_EXTENSION));
    }

    public function render()
    {
        return view('livewire.rancangan.bahan-penting-dropdown');
    }
}
