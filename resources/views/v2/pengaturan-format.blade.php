@php
    $judulTab = ['kepbup' => 'Keputusan Bupati', 'perbup' => 'Peraturan Bupati', 'nota' => 'Nota Dinas'][$tab] ?? 'Dokumen';
@endphp

{{--
    Formulir di kiri, pratinjau PDF di kanan (layar lebar). Pratinjau dibuat dari isian yang belum disimpan,
    dengan mesin cetak yang sama dengan dokumen sungguhan, dan diperbarui sebentar sesudah isian berubah.
--}}
<div class="mx-auto max-w-[1700px]"
    x-data="{
        lebar: window.matchMedia('(min-width: 1280px)'),
        segarkan() { if (this.lebar.matches) $wire.pratinjau($wire.tab) },
    }"
    x-init="segarkan(); $watch('$wire.tab', () => segarkan())"
    x-on:input.debounce.900ms="segarkan()"
    x-on:change.debounce.900ms="segarkan()"
    x-on:livewire-upload-finish.debounce.300ms="segarkan()"
    x-on:segarkan-pratinjau.window="segarkan()">
    <x-ui.page-header title="Format Dokumen" description="Kertas, margin, huruf, kop, dan isi dokumen yang dicetak. Berlaku untuk dokumen yang dicetak sesudah disimpan." icon="file-earmark-text" :back="route('dashboard')" />

    <x-ui.card :padding="false" class="mb-6">
        <div class="p-3">
            <x-ui.tabs>
                <x-ui.tab :active="$tab === 'kepbup'" icon="file-earmark-text" wire:click="$set('tab', 'kepbup')">Keputusan Bupati</x-ui.tab>
                <x-ui.tab :active="$tab === 'perbup'" icon="file-earmark-text" wire:click="$set('tab', 'perbup')">Peraturan Bupati</x-ui.tab>
                <x-ui.tab :active="$tab === 'nota'" icon="file-earmark-text" wire:click="$set('tab', 'nota')">Nota Dinas</x-ui.tab>
            </x-ui.tabs>
        </div>
    </x-ui.card>

    <div class="grid items-start gap-6 xl:grid-cols-2">
        <div class="min-w-0">
            @if ($tab === 'nota')
                @include('v2.partials.format-nota')
            @else
                @include('v2.partials.format-naskah', ['b' => $tab])
            @endif
        </div>

        {{-- Di layar sempit pratinjau tidak ditampilkan; tombol Contoh PDF di bawah formulir tetap tersedia. --}}
        <aside class="sticky top-4 hidden xl:block" aria-label="Pratinjau {{ $judulTab }}">
            <div class="flex h-[calc(100dvh-7rem)] min-h-[32rem] flex-col overflow-hidden rounded-2xl bg-white shadow-card dark:bg-navy-900 dark:shadow-none dark:ring-1 dark:ring-white/5">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-3 dark:border-navy-800">
                    <div class="min-w-0">
                        <h2 class="font-bold text-navy-900 dark:text-white">Pratinjau {{ $judulTab }}</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            <span wire:loading.remove wire:target="pratinjau">
                                @if ($pratinjauGagal)
                                    <span class="text-orange-600 dark:text-orange-300">Ada isian yang belum sah, jadi pratinjau belum diperbarui.</span>
                                @else
                                    Dari isian di kiri, termasuk yang belum disimpan.
                                    @if ($sumberPratinjau !== '')
                                        Memakai naskah <strong class="font-semibold text-slate-700 dark:text-slate-200">{{ $sumberPratinjau }}</strong>.
                                    @else
                                        Belum ada naskah yang bisa dipakai, jadi datanya contoh.
                                    @endif
                                @endif
                            </span>
                            <span wire:loading wire:target="pratinjau">Memperbarui pratinjau…</span>
                        </p>
                    </div>
                    <x-ui.button size="sm" variant="outline" icon="arrow-clockwise" x-on:click="segarkan()" loading="pratinjau">Perbarui</x-ui.button>
                </div>

                <div class="relative flex-1 bg-slate-100 dark:bg-navy-950">
                    @if ($versiPratinjau > 0)
                        {{-- Alamatnya berganti tiap pembaruan, supaya browser memuat PDF yang baru. --}}
                        <iframe wire:key="pratinjau-{{ $tab }}-{{ $versiPratinjau }}" title="Pratinjau {{ $judulTab }}"
                            src="{{ route('format.pratinjau', ['bagian' => $tab, 'v' => $versiPratinjau]) }}#toolbar=0&navpanes=0&view=FitH"
                            class="absolute inset-0 size-full border-0 transition-opacity duration-150" wire:loading.class="opacity-40" wire:target="pratinjau"></iframe>
                    @else
                        <div class="absolute inset-0 grid place-items-center text-sm text-slate-500 dark:text-slate-400">
                            <div class="flex flex-col items-center gap-3">
                                <x-ui.spinner class="size-8 text-primary-500" />
                                Menyiapkan pratinjau…
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </aside>
    </div>
</div>
