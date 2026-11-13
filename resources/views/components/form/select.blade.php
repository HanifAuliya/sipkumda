{{--
    Select. <x-form.select wire:model.live="tahun" icon="calendar3" class="md:col-span-3"> <option>..</option> </x-form.select>
    - "class" diterapkan ke pembungkus (untuk tata letak grid), "id" dan "aria-label" ke tombolnya,
      sisanya (wire:model, name, required, disabled, x-on:change, ...) ke <select> asli.
    - <select> asli tetap memegang nilai dan disembunyikan; tampilannya panel seragam dari
      resources/js/alpine/pilihan.js. Opsi bernilai kosong yang "hidden" menjadi teks placeholder,
      <optgroup> menjadi judul grup, dan daftar panjang (lebih dari 20 pilihan) mendapat kotak cari.
--}}
@props(['icon' => null, 'error' => null])

@php
    $field = $error ?? $attributes->wire('model')->value() ?: $attributes->get('name');
    $invalid = $field && $errors->has($field);
    // Tetap di setiap render (bukan acak), supaya Livewire tidak menganggap panelnya berubah.
    $idPanel = 'pilihan-' . substr(md5($attributes->get('id') ?? ($attributes->wire('model')->value() ?: $attributes->get('name') ?: $attributes->get('aria-label', ''))), 0, 10);
@endphp

<div {{ $attributes->only('class')->class('relative') }} x-data="pilihan">
    <select x-ref="select" tabindex="-1" aria-hidden="true" class="pointer-events-none absolute inset-0 h-full w-full opacity-0"
        {{ $attributes->except(['class', 'id', 'aria-label']) }}>
        {{ $slot }}
    </select>

    @if ($icon)
        <span class="pointer-events-none absolute inset-y-0 left-0 z-10 flex items-center pl-3.5 text-slate-400 max-sm:hidden">
            <i class="bi bi-{{ $icon }}"></i>
        </span>
    @endif

    <button type="button" x-ref="tombol" role="combobox" aria-haspopup="listbox" aria-controls="{{ $idPanel }}"
        @if ($attributes->has('id')) id="{{ $attributes->get('id') }}" @endif
        @if ($attributes->has('aria-label')) aria-label="{{ $attributes->get('aria-label') }}" @endif
        x-bind:aria-expanded="buka" x-bind:disabled="nonaktif"
        x-on:click="buka ? tutup() : bukaPanel()" x-on:keydown="tombolPemicu($event)"
        @class(['form-control relative flex min-h-[2.625rem] items-center pr-10 text-left', 'sm:pl-10' => $icon, 'form-control-invalid' => $invalid])>
        <span class="min-w-0 flex-1 truncate" x-bind:class="! label && 'text-slate-400 dark:text-slate-500'" x-text="label || placeholder || 'Pilih'">&nbsp;</span>
    </button>
    <i class="bi bi-chevron-down pointer-events-none absolute top-1/2 right-3.5 -translate-y-1/2 text-xs text-slate-400 transition-transform duration-150"
        x-bind:class="buka && 'rotate-180'" aria-hidden="true"></i>

    {{--
        Posisi fixed (x-anchor .fixed): tidak terpotong kartu atau modal yang overflow-nya disembunyikan, dan
        tetap di dalam modal sehingga x-trap.inert modal tidak menonaktifkannya. wire:ignore: isinya dibuat Alpine.
    --}}
        <div x-ref="panel" id="{{ $idPanel }}" x-show="buka" x-cloak wire:ignore
            x-anchor.bottom-start.offset.6.fixed="$refs.tombol"
            x-on:click.outside="if (! $refs.tombol.contains($event.target)) tutup(false)"
            x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="-translate-y-1 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition duration-100 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="z-[95] max-w-[min(28rem,calc(100vw-2rem))] rounded-xl bg-white p-1.5 shadow-xl shadow-navy-900/10 ring-1 ring-slate-900/5 dark:bg-navy-800 dark:shadow-black/40 dark:ring-white/10">

            <template x-if="bisaCari">
                <div class="relative mb-1.5">
                    <i class="bi bi-search pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-slate-400" aria-hidden="true"></i>
                    <input type="text" x-ref="kotakCari" x-model="cari" x-on:input="aktif = 0" x-on:keydown="tombolPanel($event)"
                        placeholder="Cari…" aria-label="Cari pilihan" autocomplete="off"
                        class="block w-full rounded-lg border-0 bg-slate-50 py-2 pr-3 pl-9 text-sm text-slate-700 ring-1 ring-slate-200 focus:ring-2 focus:ring-primary-300 max-sm:text-base dark:bg-navy-900 dark:text-slate-200 dark:ring-navy-700">
                </div>
            </template>

            <ul x-ref="daftar" role="listbox" tabindex="-1" x-on:keydown="tombolPanel($event)" class="max-h-72 overflow-y-auto overscroll-contain outline-none">
                <template x-for="(o, i) in tampil" :key="o.jenis + ':' + (o.value ?? o.label) + ':' + i">
                    <li>
                        <template x-if="o.jenis === 'grup'">
                            <p class="px-3 pt-2.5 pb-1 text-xs font-semibold tracking-wide text-slate-400 uppercase dark:text-slate-500" x-text="o.label"></p>
                        </template>
                        <template x-if="o.jenis === 'opsi'">
                            <div role="option" x-bind:aria-selected="o.value === nilai" x-bind:aria-disabled="o.disabled"
                                x-bind:data-aktif="bisaDipilih[aktif]?.value === o.value"
                                x-on:click="pilih(o)" x-on:mousemove="if (! o.disabled) aktif = bisaDipilih.indexOf(o)"
                                class="flex min-h-10 cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-slate-700 dark:text-slate-200"
                                x-bind:class="{
                                    'bg-primary-50 text-primary-700 dark:bg-primary-500/15 dark:text-white': bisaDipilih[aktif]?.value === o.value,
                                    'font-semibold text-primary-700 dark:text-primary-200': o.value === nilai,
                                    'cursor-not-allowed opacity-50': o.disabled,
                                }">
                                <span class="min-w-0 flex-1" x-text="o.label"></span>
                                <i class="bi bi-check2 text-base text-primary-600 dark:text-primary-300" x-show="o.value === nilai" aria-hidden="true"></i>
                            </div>
                        </template>
                    </li>
                </template>
                <li x-show="! tampil.length" class="px-3 py-3 text-sm text-slate-400">Tidak ada yang cocok.</li>
            </ul>
        </div>
</div>
