{{--
    Kotak pratinjau gambar tanda tangan. Latar kotak-kotak memperlihatkan bagian yang transparan,
    seperti di aplikasi gambar.
    <x-ttd.pratinjau :src="$url" alt="Tanda tangan Budi" memuat="gambar, simpan" />
    - memuat : target wire:loading yang menampilkan putaran di atas gambar
--}}
@props(['src' => null, 'alt' => 'Tanda tangan', 'memuat' => null])

<div {{ $attributes->class('relative grid h-40 place-items-center overflow-hidden rounded-xl bg-white bg-[length:16px_16px] bg-[linear-gradient(45deg,#f1f5f9_25%,transparent_25%),linear-gradient(-45deg,#f1f5f9_25%,transparent_25%),linear-gradient(45deg,transparent_75%,#f1f5f9_75%),linear-gradient(-45deg,transparent_75%,#f1f5f9_75%)] bg-[position:0_0,0_8px,8px_-8px,-8px_0] ring-1 ring-slate-200 ring-inset dark:ring-navy-700') }}>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $alt }}" class="max-h-32 max-w-[85%] object-contain"
            x-data x-on:error="$el.replaceWith(Object.assign(document.createElement('span'), { className: 'text-sm text-slate-400', textContent: 'Gambar tidak ditemukan' }))">
    @else
        <div class="flex flex-col items-center gap-2 text-sm text-slate-400">
            <i class="bi bi-vector-pen text-3xl" aria-hidden="true"></i>
            Belum ada tanda tangan
        </div>
    @endif

    @if ($memuat)
        <div wire:loading.flex wire:target="{{ $memuat }}" class="absolute inset-0 items-center justify-center bg-white/70 dark:bg-navy-900/70">
            <x-ui.spinner class="size-7 text-primary-500" />
        </div>
    @endif
</div>
