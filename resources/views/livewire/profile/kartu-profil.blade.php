{{-- Kartu datar, seragam dengan sambutan Dashboard. --}}
<section class="rounded-2xl bg-white p-6 shadow-card sm:p-8 dark:bg-navy-900 dark:shadow-none dark:ring-1 dark:ring-white/5">

    <div class="flex flex-col items-center gap-5 text-center sm:flex-row sm:text-left">
        <div class="relative size-28 shrink-0">
            <img src="{{ $foto ? $foto->temporaryUrl() : $user->fotoUrl() }}" alt="Foto profil {{ $user->nama_user }}"
                class="size-full rounded-full bg-slate-100 object-cover ring-4 ring-slate-100 dark:bg-navy-800 dark:ring-navy-800">

            <div wire:loading.flex wire:target="foto, simpanFoto, hapusFoto" class="absolute inset-0 items-center justify-center rounded-full bg-navy-900/70">
                <x-ui.spinner class="size-7" />
            </div>

            <label class="absolute -right-0.5 -bottom-0.5 grid size-10 cursor-pointer place-items-center rounded-full bg-white text-lg text-navy-900 shadow-soft ring-1 ring-slate-200 transition hover:scale-105 has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-primary-400 dark:bg-navy-800 dark:text-white dark:ring-navy-700" title="Pilih foto">
                <i class="bi bi-camera" aria-hidden="true"></i>
                <input type="file" wire:model="foto" accept="image/jpeg,image/png,image/webp" class="sr-only" aria-label="Pilih foto profil">
            </label>
        </div>

        <div class="min-w-0 flex-1">
            <h1 class="text-xl font-bold break-words text-navy-900 sm:text-2xl dark:text-white">{{ $user->nama_user }}</h1>

            <div class="mt-2 flex flex-wrap items-center justify-center gap-x-3 gap-y-1.5 text-sm sm:justify-start">
                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $user->getRoleNames()->first() ?? 'Tanpa Role' }}</span>
                <span class="text-slate-500 dark:text-slate-400">NIP {{ $user->NIP }}</span>
                @if ($user->perangkatDaerah)
                    <span class="text-slate-500 dark:text-slate-400">{{ $user->perangkatDaerah->nama_perangkat_daerah }}</span>
                @endif
            </div>

            @error('foto')
                <p class="mt-3 flex items-center justify-center gap-1.5 text-sm font-medium text-rose-600 sm:justify-start dark:text-rose-300">
                    <i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}
                </p>
            @enderror

            @if ($foto)
                <div class="mt-4 flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                    <x-ui.button variant="success" size="sm" icon="check2" wire:click="simpanFoto" loading="simpanFoto">Simpan Foto</x-ui.button>
                    <x-ui.button variant="outline" size="sm" wire:click="batalFoto">Batal</x-ui.button>
                    <span class="text-sm text-slate-500 dark:text-slate-400">Pratinjau, belum tersimpan.</span>
                </div>
            @else
                <div class="mt-3 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-sm text-slate-500 sm:justify-start dark:text-slate-400">
                    <span>Foto: JPG, PNG, atau WebP, maks. 5MB.</span>
                    @if ($user->foto_profil)
                        <button type="button" class="font-semibold text-primary-600 underline-offset-2 hover:underline dark:text-primary-300"
                            x-on:click="$confirm({ text: 'Foto profil akan dihapus dan kembali ke gambar bawaan.', confirmText: 'Ya, hapus' }).then((ok) => ok && $wire.hapusFoto())">
                            Hapus foto
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</section>
