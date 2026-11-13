<x-ui.card title="Tanda Tangan"
    description="Sebagai Kepala Bagian Hukum, tanda tangan ini dicetak di nota dinas dan di kolom Kepala Bagian Hukum pada matriks. Admin juga bisa membantu mengaturnya.">
    @php $berkas = $ttd ? private_file_url('tanda_tangan/ttd', $ttd->file_ttd) : null; @endphp

    <x-ttd.pratinjau :src="$gambar ? $gambar->temporaryUrl() : $berkas" :alt="$gambar ? 'Pratinjau tanda tangan' : 'Tanda tangan ' . $nama"
        memuat="gambar, simpan" />

    @error('gambar')
        <p class="mt-2 flex items-center gap-1.5 text-sm font-medium text-rose-600 dark:text-rose-300">
            <i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}
        </p>
    @enderror

    @if ($ttd && $ttd->status !== 'Aktif')
        <x-ui.alert type="warning" class="mt-4">
            Admin menonaktifkan tanda tangan ini, jadi tidak bisa dipilih saat menerbitkan nota dinas.
        </x-ui.alert>
    @endif

    @if (! $ubah)
        {{-- Yang tercetak di bawah tanda tangan. --}}
        <dl class="mt-4 space-y-1 text-center text-sm">
            <dt class="sr-only">Nama</dt>
            <dd class="font-bold text-slate-800 underline dark:text-slate-100">{{ $ttd->nama_ttd }}</dd>
            <dt class="sr-only">Pangkat/golongan</dt>
            <dd class="text-slate-600 dark:text-slate-300">{{ $ttd->jabatan }}</dd>
            <dt class="sr-only">NIP</dt>
            <dd class="text-slate-600 dark:text-slate-300">NIP {{ $ttd->nip_ttd }}</dd>
        </dl>
        <div class="mt-4">
            <x-ui.button variant="outline" icon="pencil" wire:click="mulaiUbah">Ubah Tanda Tangan</x-ui.button>
        </div>
    @else
        <form wire:submit="simpan" class="mt-4 space-y-4">
            @unless ($ttd)
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Belum ada tanda tangan. Nama dan NIP di bawah diisi dari akun Anda; sesuaikan dengan yang tercetak di nota dinas.
                </p>
            @endunless

            <div>
                <label class="inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition ring-inset hover:bg-slate-50 has-focus-visible:outline-2 has-focus-visible:outline-primary-400 dark:text-slate-200 dark:ring-navy-700 dark:hover:bg-navy-800">
                    <i class="bi bi-upload" aria-hidden="true"></i>
                    {{ $gambar || $ttd?->file_ttd ? 'Ganti Gambar' : 'Unggah Gambar' }}
                    <input type="file" wire:model="gambar" accept="image/png,image/jpeg" class="sr-only" aria-label="Pilih gambar tanda tangan">
                </label>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    PNG berlatar transparan paling rapi; JPG berlatar putih juga bisa. Maks. 2MB. Tepi kosong dipangkas otomatis.
                </p>
            </div>

            <x-form.field label="Nama Lengkap" for="ttdNama" error="nama" required hint="Seperti tercetak, lengkap dengan gelar.">
                <x-form.input id="ttdNama" wire:model="nama" />
            </x-form.field>
            <x-form.field label="Pangkat/Golongan" for="ttdJabatan" error="jabatan" required hint="Contoh: Pembina Tk. I">
                <x-form.input id="ttdJabatan" wire:model="jabatan" />
            </x-form.field>
            <x-form.field label="NIP" for="ttdNip" error="nip" required>
                <x-form.input id="ttdNip" wire:model="nip" inputmode="numeric" placeholder="19xxxxxxxxxxxxxxxx" />
            </x-form.field>
            <x-form.field label="Kata Sandi Saat Ini" for="sandiTtdKabag" error="sandiSekarang" required
                hint="Tanda tangan ini tercetak di nota dinas resmi, jadi perubahannya perlu kata sandi.">
                <x-form.password id="sandiTtdKabag" name="sandiSekarang" wire:model="sandiSekarang" autocomplete="current-password" />
            </x-form.field>

            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button type="submit" variant="success" icon="check2" loading="simpan">Simpan Tanda Tangan</x-ui.button>
                @if ($ttd)
                    <x-ui.button variant="outline" wire:click="batal">Batal</x-ui.button>
                @endif
            </div>
        </form>
    @endif
</x-ui.card>
