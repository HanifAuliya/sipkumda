{{--
    Isi halaman lampiran di luar penyusunan: kertas versi terbaru, dengan pilihan melihat rumusan awal dan perubahannya.
    Saat Ubah, kertasnya menjadi editor (atau unggahan PDF versi baru). Memakai variabel dan x-data baris-kerja (`lihat`, `ubah`).
--}}
@php
    $terbaru = $sempurna !== '' ? $sempurna : $awal;
    $adaVersi = $sempurna !== '' && $awal !== '' && ! $baris->terhapus();
    $tombolLihat = 'rounded-lg px-3.5 py-1.5 text-sm font-semibold transition aria-pressed:bg-white aria-pressed:text-primary-700 aria-pressed:shadow-soft dark:aria-pressed:bg-navy-900 dark:aria-pressed:text-primary-300';
@endphp

{{-- PDF tetap terlihat saat mengunggah versi baru; kertas diganti editornya. --}}
<div @unless ($baris->berkas()) x-show="! ubah" @endunless>
    @if ($baris->terhapus())
        <p class="mb-3 rounded-lg bg-rose-50 px-4 py-2.5 text-center text-sm font-semibold text-rose-700 dark:bg-rose-500/10 dark:text-rose-200">
            {{ $status === B::DIHAPUS ? 'Lampiran ini dihapus dan tidak ikut dicetak.' : 'Perangkat Daerah mengusulkan lampiran ini dihapus.' }}
        </p>
    @endif

    @if ($adaVersi)
        <div class="mb-4 flex justify-center">
            <div class="inline-flex gap-1 rounded-xl bg-slate-200/70 p-1 text-slate-600 dark:bg-navy-800 dark:text-slate-300" role="group" aria-label="Versi yang dilihat">
                @foreach (array_filter(['baru' => 'Versi Terbaru', 'awal' => 'Rumusan Awal', 'beda' => $baris->html() ? 'Perubahan' : null]) as $nilai => $nama)
                    <button type="button" class="{{ $tombolLihat }}" x-on:click="lihat = '{{ $nilai }}'" x-bind:aria-pressed="(lihat === '{{ $nilai }}').toString()">{{ $nama }}</button>
                @endforeach
            </div>
        </div>
    @endif

    <div @if ($adaVersi) x-show="lihat === 'baru'" @endif @class(['opacity-60' => $baris->terhapus()])>
        @if ($baris->berkas())
            @include('v2.partials.isi-lampiran', ['nilai' => $terbaru])
        @else
            <x-naskah.kertas-lampiran :kertas="$kertas">
                @if ($terbaru !== '')
                    @include('v2.partials.isi-lampiran', ['nilai' => $terbaru])
                @else
                    <p class="py-10 text-center text-slate-500">Lampiran ini kosong.</p>
                @endif
            </x-naskah.kertas-lampiran>
        @endif
    </div>

    @if ($adaVersi)
        <div x-show="lihat === 'awal'" x-cloak>
            @if ($baris->berkas())
                @include('v2.partials.isi-lampiran', ['nilai' => $awal])
            @else
                <x-naskah.kertas-lampiran :kertas="$kertas">
                    @include('v2.partials.isi-lampiran', ['nilai' => $awal])
                </x-naskah.kertas-lampiran>
            @endif
        </div>

        @if ($baris->html())
            {{-- Dibandingkan sebagai teks: tata letak tabel tidak bisa ditandai per kata. --}}
            <div x-show="lihat === 'beda'" x-cloak class="v2-diff v2-kertas mx-auto max-w-3xl rounded-lg bg-white p-6 text-black shadow-card">
                <p class="mb-3 text-sm text-slate-500"><del>Merah</del> dihapus, <ins>hijau</ins> ditambah. Hanya isinya yang dibandingkan, bukan tata letaknya.</p>
                <div class="{{ $serif }} whitespace-pre-line">{!! App\Support\DiffKata::html(App\Support\HtmlLampiran::teks($awal), App\Support\HtmlLampiran::teks($sempurna)) !!}</div>
            </div>
        @endif
    @endif
</div>

{{-- Yang tidak boleh mengubah tidak perlu memuat editornya. --}}
@if ($mode)
    <div x-show="ubah" x-cloak>
        @if ($baris->berkas())
            {{-- Perbaikan lampiran PDF: unggah versi baru; versi sebelumnya tetap di Rumusan Awal. --}}
            <div class="mt-4">@include('v2.partials.unggah-lampiran', ['teksTombol' => 'Unggah Versi Baru'])</div>
        @else
            <div x-ref="editor">
                @include('v2.partials.editor-lampiran', ['label' => "Rumusan penyempurnaan {$judul}"])
            </div>
        @endif
    </div>
@endif

@error('rumusan')
    <p class="mt-3 text-center text-sm font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
@enderror
