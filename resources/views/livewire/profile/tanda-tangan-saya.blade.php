<x-ui.card title="Tanda Tangan"
    description="Dipakai di matriks naskah yang Anda teliti, di atas nama dan NIP Anda. Muncul setelah nota dinas diterbitkan.">
    {{-- Latar kotak-kotak memperlihatkan bagian yang transparan, seperti di aplikasi gambar. --}}
    <div class="relative grid h-40 place-items-center overflow-hidden rounded-xl bg-white bg-[length:16px_16px] bg-[linear-gradient(45deg,#f1f5f9_25%,transparent_25%),linear-gradient(-45deg,#f1f5f9_25%,transparent_25%),linear-gradient(45deg,transparent_75%,#f1f5f9_75%),linear-gradient(-45deg,transparent_75%,#f1f5f9_75%)] bg-[position:0_0,0_8px,8px_-8px,-8px_0] ring-1 ring-slate-200 ring-inset dark:ring-navy-700">
        @if ($gambar)
            <img src="{{ $gambar->temporaryUrl() }}" alt="Pratinjau tanda tangan" class="max-h-32 max-w-[85%] object-contain">
        @elseif ($user->file_ttd)
            <img src="{{ $user->ttdUrl() }}" alt="Tanda tangan {{ $user->nama_user }}" class="max-h-32 max-w-[85%] object-contain">
        @else
            <div class="flex flex-col items-center gap-2 text-sm text-slate-400">
                <i class="bi bi-vector-pen text-3xl" aria-hidden="true"></i>
                Belum ada tanda tangan
            </div>
        @endif

        <div wire:loading.flex wire:target="gambar, simpan, hapus" class="absolute inset-0 items-center justify-center bg-white/70 dark:bg-navy-900/70">
            <x-ui.spinner class="size-7 text-primary-500" />
        </div>
    </div>

    @error('gambar')
        <p class="mt-2 flex items-center gap-1.5 text-sm font-medium text-rose-600 dark:text-rose-300">
            <i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}
        </p>
    @enderror

    @if ($gambar)
        <div class="mt-4 flex flex-wrap items-center gap-2">
            <x-ui.button variant="success" icon="check2" wire:click="simpan" loading="simpan">Simpan Tanda Tangan</x-ui.button>
            <x-ui.button variant="outline" wire:click="batal">Batal</x-ui.button>
            <span class="text-sm text-slate-500 dark:text-slate-400">Pratinjau, belum tersimpan.</span>
        </div>
    @else
        <div class="mt-4 flex flex-wrap items-center gap-2">
            <label class="inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition ring-inset hover:bg-slate-50 has-focus-visible:outline-2 has-focus-visible:outline-primary-400 dark:text-slate-200 dark:ring-navy-700 dark:hover:bg-navy-800">
                <i class="bi bi-upload" aria-hidden="true"></i>
                {{ $user->file_ttd ? 'Ganti Gambar' : 'Unggah Gambar' }}
                <input type="file" wire:model="gambar" accept="image/png,image/jpeg" class="sr-only" aria-label="Pilih gambar tanda tangan">
            </label>
            @if ($user->file_ttd)
                <x-ui.button variant="ghost" icon="trash"
                    x-on:click="$confirm({ text: 'Tanda tangan akan dihapus dari matriks yang dicetak berikutnya.', confirmText: 'Ya, hapus' }).then((ok) => ok && $wire.hapus())">Hapus</x-ui.button>
            @endif
        </div>
        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
            PNG berlatar transparan paling rapi; JPG berlatar putih juga bisa. Maks. 2MB. Tepi kosong dipangkas otomatis.
        </p>
    @endif
</x-ui.card>
