<section class="relative overflow-hidden rounded-2xl bg-linear-to-br from-navy-900 via-navy-800 to-primary-700 p-6 text-white shadow-card sm:p-8">
    <x-ui.bola />

    <div class="relative flex flex-col items-center gap-5 text-center sm:flex-row sm:text-left">
        <div class="relative size-28 shrink-0">
            <img src="{{ $foto ? $foto->temporaryUrl() : $user->fotoUrl() }}" alt="Foto profil {{ $user->nama_user }}"
                class="size-full rounded-full bg-white/10 object-cover ring-4 ring-white/20">

            <div wire:loading.flex wire:target="foto, simpanFoto, hapusFoto" class="absolute inset-0 items-center justify-center rounded-full bg-navy-900/70">
                <x-ui.spinner class="size-7" />
            </div>

            <label class="absolute -right-0.5 -bottom-0.5 grid size-10 cursor-pointer place-items-center rounded-full bg-white text-lg text-navy-900 shadow-lg ring-2 ring-navy-900/10 transition hover:scale-105 has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-white" title="Pilih foto">
                <i class="bi bi-camera" aria-hidden="true"></i>
                <input type="file" wire:model="foto" accept="image/jpeg,image/png,image/webp" class="sr-only" aria-label="Pilih foto profil">
            </label>
        </div>

        <div class="min-w-0 flex-1">
            <h1 class="text-xl font-bold break-words text-white sm:text-2xl">{{ $user->nama_user }}</h1>

            <div class="mt-2 flex flex-wrap items-center justify-center gap-x-3 gap-y-1.5 text-sm sm:justify-start">
                <span class="rounded-full bg-white/15 px-2.5 py-0.5 font-semibold">{{ $user->getRoleNames()->first() ?? 'Tanpa Role' }}</span>
                <span class="text-white/75">NIP {{ $user->NIP }}</span>
                @if ($user->perangkatDaerah)
                    <span class="text-white/75">{{ $user->perangkatDaerah->nama_perangkat_daerah }}</span>
                @endif
            </div>

            @error('foto')
                <p class="mt-3 flex items-center justify-center gap-1.5 text-sm font-medium text-rose-200 sm:justify-start">
                    <i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}
                </p>
            @enderror

            @if ($foto)
                <div class="mt-4 flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                    <x-ui.button variant="success" size="sm" icon="check2" wire:click="simpanFoto" loading="simpanFoto">Simpan Foto</x-ui.button>
                    <x-ui.button variant="outline" size="sm" wire:click="batalFoto">Batal</x-ui.button>
                    <span class="text-sm text-white/75">Pratinjau, belum tersimpan.</span>
                </div>
            @else
                <div class="mt-3 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-sm text-white/70 sm:justify-start">
                    <span>Foto: JPG, PNG, atau WebP, maks. 5MB.</span>
                    @if ($user->foto_profil)
                        <button type="button" class="font-semibold text-white underline-offset-2 hover:underline"
                            x-on:click="$confirm({ text: 'Foto profil akan dihapus dan kembali ke gambar bawaan.', confirmText: 'Ya, hapus' }).then((ok) => ok && $wire.hapusFoto())">
                            Hapus foto
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</section>
