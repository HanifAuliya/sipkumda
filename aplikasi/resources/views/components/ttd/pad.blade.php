{{--
    Kotak untuk menggambar tanda tangan langsung (mouse, jari, atau stylus). Hasilnya masuk ke
    properti Livewire `model` sebagai data URL PNG (resources/js/alpine/pad-ttd.js).
    <x-ttd.pad model="ttdLangsung" />
--}}
@props(['model'])

<div x-data="padTtd(@js($model))" {{ $attributes->class('space-y-2') }}>
    <div wire:ignore class="relative h-44 overflow-hidden rounded-xl bg-white ring-1 ring-slate-300 ring-inset dark:ring-navy-600">
        {{-- Garis dasar dan petunjuk; tidak ikut tergambar karena berada di luar kanvas. --}}
        <div class="pointer-events-none absolute inset-x-6 bottom-10 border-b border-dashed border-slate-300" aria-hidden="true"></div>
        <p x-show="kosong" class="pointer-events-none absolute inset-x-0 bottom-3 text-center text-sm text-slate-400">Tanda tangan di atas garis</p>
        <canvas class="absolute inset-0 size-full cursor-crosshair touch-none" aria-label="Kotak tanda tangan"
            x-on:pointerdown="mulai($event)" x-on:pointermove="gerak($event)"
            x-on:pointerup="selesai()" x-on:pointercancel="selesai()" x-on:pointerleave="selesai()"></canvas>
    </div>
    <div class="flex items-center justify-between gap-2">
        <p class="text-sm text-slate-500 dark:text-slate-400">Pakai mouse, jari, atau stylus.</p>
        <x-ui.button variant="ghost" size="sm" icon="arrow-counterclockwise" x-on:click="bersihkan()" x-bind:disabled="kosong">Hapus</x-ui.button>
    </div>
</div>
