{{--
    Field unggah file Livewire: pilih file → tampil nama file + tombol hapus.
    <x-form.upload model="matrik" :file="$matrik" label="Matrik" accept=".doc,.docx" hint="Word, maks. 20MB" required />

    - model     : nama properti Livewire (juga dipakai untuk pesan error)
    - file      : nilai properti (TemporaryUploadedFile) untuk menampilkan pratinjau
    - removable : tampilkan tombol hapus yang memanggil removeFile('<model>')
--}}
@props(['model', 'file' => null, 'label' => null, 'hint' => null, 'accept' => null, 'required' => false, 'removable' => true])

<x-form.field :label="$label" :for="'upload-' . $model" :error="$model" :hint="$hint" :required="$required">
    @if ($file)
        <div class="flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50/70 px-3 py-2.5 dark:border-emerald-500/30 dark:bg-emerald-500/10">
            <i class="bi bi-file-earmark-check-fill text-lg text-emerald-500"></i>
            <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-700 dark:text-slate-200">
                {{ method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : basename((string) $file) }}
            </span>
            @if ($removable)
                <button type="button" wire:click="removeFile('{{ $model }}')"
                    class="rounded-md p-1.5 text-rose-500 transition hover:bg-rose-100 dark:hover:bg-rose-500/15" title="Hapus file">
                    <i class="bi bi-trash"></i>
                </button>
            @endif
        </div>
    @else
        <x-form.file id="upload-{{ $model }}" wire:model="{{ $model }}" :accept="$accept" {{ $attributes }} />
    @endif
</x-form.field>
