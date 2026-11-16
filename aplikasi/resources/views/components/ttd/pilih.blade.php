{{--
    Pilihan tanda tangan untuk satu dokumen: yang tersimpan, atau tanda tangan sekarang.
    <x-ttd.pilih sumber="sumberTtd" langsung="ttdLangsung" simpan="simpanTtd" :src="$urlTersimpan" error="ttd" />
    - sumber   : properti Livewire berisi 'tersimpan' | 'langsung'
    - langsung : properti data URL dari kotak tanda tangan
    - simpan   : properti bool "simpan juga sebagai tanda tangan saya"
    - src      : gambar tanda tangan tersimpan; null = belum ada, pilihan itu dinonaktifkan
    - slot     : pengganti pratinjau tersimpan (mis. daftar pilihan penanda tangan)
--}}
@props(['sumber', 'langsung', 'simpan', 'src' => null, 'error' => null, 'keteranganTersimpan' => null])

@php
    $opsi = 'flex cursor-pointer items-start gap-3 rounded-xl p-3.5 ring-1 ring-slate-200 transition ring-inset has-checked:bg-primary-50/60 has-checked:ring-2 has-checked:ring-primary-400 has-disabled:cursor-not-allowed has-disabled:opacity-60 dark:ring-navy-700 dark:has-checked:bg-primary-500/10';
    $adaTersimpan = filled($src) || ! $slot->isEmpty();
@endphp

<div {{ $attributes->class('space-y-3') }}>
    <label class="{{ $opsi }}">
        <input type="radio" value="tersimpan" wire:model="{{ $sumber }}" class="mt-1 text-primary-600 focus:ring-primary-500" @disabled(! $adaTersimpan)>
        <span class="min-w-0 flex-1">
            <span class="block font-semibold text-slate-800 dark:text-slate-100">Pakai tanda tangan tersimpan</span>
            <span class="block text-sm text-slate-500 dark:text-slate-400">
                {{ $adaTersimpan ? ($keteranganTersimpan ?? 'Tanda tangan yang sudah diunggah di Profil.') : 'Belum ada. Gambar sekarang, atau unggah dulu di Profil.' }}
            </span>
            @if ($slot->isEmpty() && filled($src))
                <span class="mt-2 flex h-20 w-44 items-center justify-center rounded-lg bg-white p-1.5 ring-1 ring-slate-200 dark:ring-navy-700">
                    <img src="{{ $src }}" alt="Tanda tangan tersimpan" class="max-h-full max-w-full object-contain">
                </span>
            @endif
        </span>
    </label>
    @unless ($slot->isEmpty())
        <div x-show="$wire.{{ $sumber }} === 'tersimpan'" class="pl-1">{{ $slot }}</div>
    @endunless

    <label class="{{ $opsi }}">
        <input type="radio" value="langsung" wire:model="{{ $sumber }}" class="mt-1 text-primary-600 focus:ring-primary-500">
        <span class="min-w-0 flex-1">
            <span class="block font-semibold text-slate-800 dark:text-slate-100">Tanda tangan sekarang</span>
            <span class="block text-sm text-slate-500 dark:text-slate-400">Gambar langsung di layar, khusus untuk dokumen ini.</span>
        </span>
    </label>

    <div x-show="$wire.{{ $sumber }} === 'langsung'" x-cloak class="space-y-3">
        <x-ttd.pad :model="$langsung" />
        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
            <input type="checkbox" wire:model="{{ $simpan }}" class="rounded text-primary-600 focus:ring-primary-500">
            Simpan juga sebagai tanda tangan saya untuk dokumen berikutnya
        </label>
    </div>

    @if ($error)
        @error($error)
            <p class="flex items-center gap-1.5 text-sm font-medium text-rose-600 dark:text-rose-300">
                <i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}
            </p>
        @enderror
    @endif
</div>
