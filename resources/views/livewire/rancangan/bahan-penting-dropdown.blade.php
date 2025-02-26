<div>
    {{-- Dropdown Bahan Penting --}}
    <div class="dropdown">
        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="dropdownBahanPenting"
            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="ni ni-cloud-download-95 mr-1"></i> Bahan Penting
        </button>
        <div class="dropdown-menu shadow-lg" aria-labelledby="dropdownBahanPenting">
            {{-- Item Dropdown --}}
            @foreach ($materials as $material)
                <a class="dropdown-item d-flex align-items-center" href="#"
                    wire:click.prevent="download({{ $material->id }})">
                    <i class="{{ $this->getFileIcon($material->file_path) }} mr-2"></i> {{ $material->button_name }}
                </a>
            @endforeach
        </div>
    </div>
</div>
