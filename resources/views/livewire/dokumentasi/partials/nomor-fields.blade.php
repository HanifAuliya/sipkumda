{{--
    Field nomor produk hukum + nomor berita daerah, dipakai form tambah dan edit dokumentasi.
    - tahunTetap   : tahun produk hukum (teks); null = tahun diisi lewat properti "tahun"
    - beritaWajib  : nomor/tahun berita daerah wajib diisi
    Susunan nomor lengkap (properti "pola_nomor") hanya berlaku untuk Keputusan Bupati.
--}}
@php
    $beritaWajib ??= true;
    $angka = fn (int $max) => "\$el.value = \$el.value.replace(/\\D/g, '').slice(0, {$max})";
    $segmen = 'flex items-center bg-slate-50 px-3 text-sm text-slate-500 dark:bg-navy-800';
    $isian = 'w-16 min-w-0 flex-1 border-0 bg-white px-3 py-2.5 text-center font-mono text-sm focus:ring-0 dark:bg-navy-900';
    $grup = 'flex items-stretch overflow-hidden rounded-lg ring-1 ring-slate-200 focus-within:ring-2 focus-within:ring-primary-400 dark:ring-navy-700';
@endphp

<div class="grid gap-5 sm:grid-cols-2">
    <x-form.field label="Nomor Produk Hukum" for="nomor_produk_hukum" :error="['nomor', 'tahun']" required>
        <div class="{{ $grup }}">
            <span class="{{ $segmen }}">Nomor</span>
            <input id="nomor_produk_hukum" wire:model="nomor" inputmode="numeric" maxlength="3" placeholder="###"
                x-on:input="{{ $angka(3) }}" class="{{ $isian }}">
            @if ($tahunTetap)
                <span class="{{ $segmen }}">Tahun {{ $tahunTetap }}</span>
            @else
                <span class="{{ $segmen }}">Tahun</span>
                <input wire:model="tahun" inputmode="numeric" maxlength="4" placeholder="YYYY" aria-label="Tahun produk hukum"
                    x-on:input="{{ $angka(4) }}" class="{{ $isian }}">
            @endif
        </div>
    </x-form.field>

    <x-form.field label="Nomor Berita Daerah" for="nomor_berita" :error="['nomor_berita', 'tahun_berita']"
        :required="$beritaWajib" :hint="$beritaWajib ? null : 'Opsional.'">
        <div class="{{ $grup }}">
            <input id="nomor_berita" wire:model="nomor_berita" inputmode="numeric" maxlength="3" placeholder="No"
                x-on:input="{{ $angka(3) }}" class="{{ $isian }}">
            <span class="{{ $segmen }}">/</span>
            <input wire:model="tahun_berita" inputmode="numeric" maxlength="4" placeholder="YYYY" aria-label="Tahun berita daerah"
                x-on:input="{{ $angka(4) }}" class="{{ $isian }}">
        </div>
    </x-form.field>

    <x-form.field label="Susunan nomor Keputusan Bupati" for="pola_nomor" error="pola_nomor" class="sm:col-span-2"
        hint="Khusus Keputusan Bupati, mis. 140/{nomor}/412.1.6/TAHUN {tahun} tampil sebagai Nomor 140/1/412.1.6/TAHUN 2025. Kosongkan untuk Peraturan Bupati atau bila cukup Nomor 1 Tahun 2025.">
        <x-form.input id="pola_nomor" wire:model="pola_nomor" maxlength="100" placeholder="140/{nomor}/412.1.6/TAHUN {tahun}" autocomplete="off" />
    </x-form.field>
</div>
