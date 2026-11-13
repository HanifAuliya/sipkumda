<div>
    <x-ui.dropdown width="w-72">
        <x-slot:trigger>
            <x-ui.button variant="outline-primary" icon="cloud-download">
                Bahan Penting <i class="bi bi-chevron-down text-xs"></i>
            </x-ui.button>
        </x-slot:trigger>

        {{-- Template naskah bawaan selalu ada; bentuknya sama dengan yang paling akurat dibaca saat mengajukan rancangan. --}}
        <p class="px-3 pt-1.5 pb-2 text-sm font-semibold text-slate-500 dark:text-slate-400">Template naskah</p>
        <x-ui.dropdown-item :href="route('template', 'peraturan-bupati')">
            <i class="bi bi-file-earmark-word-fill text-base text-primary-500" aria-hidden="true"></i>
            <span class="truncate">Template Peraturan Bupati</span>
        </x-ui.dropdown-item>
        <x-ui.dropdown-item :href="route('template', 'keputusan-bupati')">
            <i class="bi bi-file-earmark-word-fill text-base text-amber-500" aria-hidden="true"></i>
            <span class="truncate">Template Keputusan Bupati</span>
        </x-ui.dropdown-item>

        @if ($materials->isNotEmpty())
            <p class="mt-1 border-t border-slate-100 px-3 pt-2.5 pb-2 text-sm font-semibold text-slate-500 dark:border-navy-700 dark:text-slate-400">Dari Bagian Hukum</p>
            @foreach ($materials as $material)
                <x-ui.dropdown-item wire:click="download({{ $material->id }})" wire:key="material-{{ $material->id }}">
                    <i class="{{ $this->getFileIcon($material->file_path) }} text-base"></i>
                    <span class="truncate">{{ $material->button_name }}</span>
                </x-ui.dropdown-item>
            @endforeach
        @endif
    </x-ui.dropdown>
</div>
