{{--
    Unggah PDF lampiran (baris lampiran berbentuk berkas). Setiap unggahan menjadi versi baru; versi lama tetap
    bisa dibuka dari kolom Rumusan Awal. $teksTombol: label tombol unggah.
--}}
<div class="rounded-lg border border-dashed border-slate-300 p-3.5 dark:border-navy-600"
    x-data="{ mengunggah: false, persen: 0 }"
    x-on:livewire-upload-start="mengunggah = true" x-on:livewire-upload-finish="mengunggah = false"
    x-on:livewire-upload-error="mengunggah = false" x-on:livewire-upload-progress="persen = $event.detail.progress">
    <div class="flex flex-wrap items-center gap-3">
        <input type="file" accept="application/pdf,.pdf" wire:model="pdfLampiran" aria-label="PDF lampiran"
            class="block min-h-10 min-w-0 flex-1 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold dark:file:bg-navy-800 dark:file:text-slate-200">
        <x-ui.button size="rapat" icon="upload" wire:click="unggahLampiran" loading="unggahLampiran"
            x-bind:disabled="mengunggah || ! $wire.pdfLampiran">{{ $teksTombol ?? 'Unggah PDF' }}</x-ui.button>
    </div>
    <p x-show="mengunggah" x-cloak class="mt-2 text-sm text-slate-500" x-text="`Mengunggah… ${persen}%`"></p>
    @error('pdfLampiran')
        <p class="mt-2 text-sm font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
    @enderror
    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">PDF, paling besar 20 MB. Ditambahkan apa adanya di belakang naskah saat dicetak.</p>
</div>
