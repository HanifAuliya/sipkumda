<x-ui.card title="Tanda Tangan"
    description="Dipakai di matriks naskah yang Anda teliti, di atas nama dan NIP Anda. Muncul setelah nota dinas diterbitkan. Admin juga bisa membantu mengunggahnya.">
    <x-ttd.pratinjau :src="$gambar ? $gambar->temporaryUrl() : $user->ttdUrl()" :alt="$gambar ? 'Pratinjau tanda tangan' : 'Tanda tangan ' . $user->nama_user"
        memuat="gambar, simpan, hapus" />

    @error('gambar')
        <p class="mt-2 flex items-center gap-1.5 text-sm font-medium text-rose-600 dark:text-rose-300">
            <i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}
        </p>
    @enderror

    @if ($gambar)
        <x-form.field label="Kata Sandi Saat Ini" for="sandiTtd" error="sandiSekarang" required class="mt-4 max-w-sm"
            hint="Tanda tangan ini tercetak di matriks resmi, jadi penggantiannya perlu kata sandi.">
            <x-form.password id="sandiTtd" name="sandiSekarang" wire:model="sandiSekarang" autocomplete="current-password"
                wire:keydown.enter.prevent="simpan" />
        </x-form.field>
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
