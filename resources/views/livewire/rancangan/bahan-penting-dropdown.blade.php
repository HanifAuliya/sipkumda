<div>
    <x-ui.dropdown width="w-72">
        <x-slot:trigger>
            <x-ui.button variant="outline-primary" icon="cloud-download">
                Bahan Penting <i class="bi bi-chevron-down text-xs"></i>
            </x-ui.button>
        </x-slot:trigger>

        <p class="px-3 pt-1.5 pb-2 text-xs font-semibold tracking-wide text-slate-400 uppercase">Unduh template</p>
        @forelse ($materials as $material)
            <x-ui.dropdown-item wire:click="download({{ $material->id }})" wire:key="material-{{ $material->id }}">
                <i class="{{ $this->getFileIcon($material->file_path) }} text-base"></i>
                <span class="truncate">{{ $material->button_name }}</span>
            </x-ui.dropdown-item>
        @empty
            <p class="px-3 py-3 text-sm text-slate-500">Belum ada bahan penting.</p>
        @endforelse
    </x-ui.dropdown>
</div>
