{{--
    Editor rumusan (resources/js/alpine/editor-rumusan.js), bekerja seperti daftar bernomor di Word:
    tiap ayat dan butir tampil dengan nomor dan jorok seperti hasil cetak, tabel tampil sebagai tabel yang selnya bisa diisi.
    Hasilnya tetap disimpan sebagai teks ("a. isi", "| a | b |") di `teks` milik baris-kerja.
    wire:ignore: isinya dibangun Alpine, jadi tidak boleh ditimpa saat Livewire merender ulang.
    Variabel: $barisMinimal (tinggi awal), $label (nama untuk pembaca layar), $serif (kelas huruf naskah),
    $gayaAwal (gaya nomor saat baris biasa diberi nomor: ayat untuk pasal, angka untuk lampiran).
--}}
@php
    $tombolAlat = 'inline-flex h-8 min-w-8 items-center justify-center gap-1.5 rounded-md px-2 text-sm font-medium text-slate-600 transition hover:bg-white hover:text-navy-900 hover:shadow-xs disabled:pointer-events-none disabled:opacity-40 aria-pressed:bg-white aria-pressed:text-primary-700 aria-pressed:shadow-xs aria-pressed:ring-1 aria-pressed:ring-primary-200 dark:text-slate-300 dark:hover:bg-navy-700 dark:hover:text-white dark:aria-pressed:bg-navy-700 dark:aria-pressed:text-primary-300 dark:aria-pressed:ring-primary-500/40';
    $pemisah = 'mx-0.5 h-5 w-px bg-slate-200 dark:bg-navy-700';
@endphp

<div x-data="editorRumusan({{ (int) $barisMinimal }}, @js($gayaAwal ?? 'ayat'))" wire:ignore role="group" aria-label="{{ $label }}"
    class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-xs transition focus-within:border-primary-400 focus-within:ring-3 focus-within:ring-primary-100 dark:border-navy-700 dark:bg-navy-950 dark:focus-within:border-primary-500 dark:focus-within:ring-primary-500/20">

    {{-- Bilah alat: berlaku untuk baris yang sedang disunting. mousedown.prevent menjaga kursor tetap di barisnya. --}}
    <div role="toolbar" aria-label="Format rumusan" class="flex flex-wrap items-center gap-0.5 border-b border-slate-200 bg-slate-50 px-2 py-1 dark:border-navy-700 dark:bg-navy-800/70">
        <span class="px-1 text-xs font-semibold text-slate-500 dark:text-slate-400">Nomor</span>
        <button type="button" class="{{ $tombolAlat }}" x-on:mousedown.prevent x-on:click="aturGaya(null)"
            x-bind:disabled="!barisAktif" x-bind:aria-pressed="(gayaAktif === null).toString()"
            title="Teks biasa tanpa nomor">Teks</button>
        <template x-for="g in GAYA" :key="g.nama">
            <button type="button" class="{{ $tombolAlat }} font-['Bookman_Old_Style',Georgia,serif]" x-on:mousedown.prevent x-on:click="aturGaya(g.nama)"
                x-bind:disabled="!barisAktif" x-bind:aria-pressed="(gayaAktif === g.nama).toString()"
                x-bind:title="`Jadikan butir ${g.contoh}`" x-text="g.contoh"></button>
        </template>
        <span class="{{ $pemisah }}" aria-hidden="true"></span>
        <button type="button" class="{{ $tombolAlat }}" x-on:mousedown.prevent x-on:click="naikAktif()" x-bind:disabled="!barisAktif || barisAktif.nomor === null"
            title="Naikkan tingkat (Shift+Tab)" aria-label="Naikkan tingkat"><i class="bi bi-text-indent-right" aria-hidden="true"></i></button>
        <button type="button" class="{{ $tombolAlat }}" x-on:mousedown.prevent x-on:click="turunAktif()" x-bind:disabled="!barisAktif"
            title="Turunkan tingkat (Tab)" aria-label="Turunkan tingkat"><i class="bi bi-text-indent-left" aria-hidden="true"></i></button>
        <span class="{{ $pemisah }}" aria-hidden="true"></span>
        <button type="button" class="{{ $tombolAlat }}" x-on:mousedown.prevent x-on:click="sisipTabel()" title="Sisipkan tabel di posisi kursor">
            <i class="bi bi-table" aria-hidden="true"></i>Tabel
        </button>
    </div>

    <div class="{{ $serif }} cursor-text px-3.5 py-2.5" x-on:click.self="fokusAkhir()"
        x-bind:style="`min-height: calc(${barisMinimal}lh + 1.25rem)`">
        <template x-for="(p, i) in potong" :key="p.id">
            <div x-on:click.self="fokusBaris(p.id, 'akhir')">
                {{-- Baris teks: kolom nomor 2rem + jarak 0,75rem per tingkat, sama dengan hasil cetak. --}}
                <template x-if="p.jenis === 'baris'">
                    <div class="flex items-start gap-3 rounded-sm py-0.5" x-bind:style="`margin-left: ${(tingkat[i] ?? 0) * 2.75}rem`">
                        <template x-if="p.nomor !== null">
                            <input type="text" x-bind:value="p.nomor" x-on:change="ubahNomor(i, $event)"
                                x-on:keydown.enter.prevent="$event.target.blur(); fokusBaris(p.id, 0)"
                                aria-label="Nomor butir" title="Nomor bisa diubah, misalnya (1a)"
                                class="min-w-8 shrink-0 rounded-sm border-0 bg-transparent p-0 font-[inherit] text-[length:inherit] leading-[inherit] field-sizing-content focus:bg-primary-50 focus:ring-0 dark:focus:bg-primary-500/10">
                        </template>
                        <textarea rows="1" x-bind:data-baris="p.id" x-bind:value="p.isi"
                            x-on:input="ubahBaris(i, $event)" x-on:keydown="tombol($event, i)" x-on:paste="tempelDiBaris($event, i)"
                            x-on:focus="catatKursor(p, $event.target)" x-on:keyup="catatKursor(p, $event.target)"
                            x-on:mouseup="catatKursor(p, $event.target)" x-on:blur="catatKursor(p, $event.target)"
                            x-bind:placeholder="kosong ? 'Ketik rumusan… Enter: butir baru · Tab: turun tingkat · Shift+Tab: naik tingkat' : ''"
                            aria-label="{{ $label }}"
                            class="block min-w-0 flex-1 resize-none border-0 bg-transparent p-0 field-sizing-content placeholder:font-sans placeholder:text-sm placeholder:text-slate-400 focus:ring-0"></textarea>
                    </div>
                </template>

                <template x-if="p.jenis === 'tabel'">
                    <div class="my-1.5 overflow-hidden rounded-lg font-sans ring-1 ring-slate-300 dark:ring-navy-600" x-bind:style="`margin-left: ${(tingkat[i] ?? 0) * 2.75}rem`">
                        <div class="flex flex-wrap items-center gap-0.5 border-b border-slate-200 bg-slate-50 px-2 py-1 dark:border-navy-700 dark:bg-navy-800/70">
                            <span class="mr-auto inline-flex items-center gap-1.5 px-1 text-sm font-semibold text-slate-600 dark:text-slate-300">
                                <i class="bi bi-table" aria-hidden="true"></i>
                                Tabel <span class="font-normal text-slate-400" x-text="`${p.sel.length} × ${p.sel[0].length}`"></span>
                            </span>
                            <button type="button" class="{{ $tombolAlat }}" x-on:click="aturJudul(i)" x-bind:aria-pressed="p.judul.toString()"
                                title="Baris pertama sebagai judul tabel: rata tengah dan diulang di tiap halaman"><i class="bi bi-card-heading" aria-hidden="true"></i>Judul</button>
                            <span class="{{ $pemisah }}" aria-hidden="true"></span>
                            @foreach (['left' => ['text-left', 'Rata kiri'], 'center' => ['text-center', 'Rata tengah'], 'right' => ['text-right', 'Rata kanan']] as $rata => [$ikon, $nama])
                                <button type="button" class="{{ $tombolAlat }}" x-on:click="aturRata(i, '{{ $rata }}')"
                                    x-bind:aria-pressed="((p.rata?.[posisiSel(i).c] ?? 'left') === '{{ $rata }}').toString()"
                                    title="{{ $nama }} untuk kolom sel yang dipilih" aria-label="{{ $nama }}"><i class="bi bi-{{ $ikon }}" aria-hidden="true"></i></button>
                            @endforeach
                            <span class="{{ $pemisah }}" aria-hidden="true"></span>
                            <button type="button" class="{{ $tombolAlat }}" x-on:click="tambahBaris(i)" title="Tambah baris di bawah sel yang dipilih"><i class="bi bi-plus-lg" aria-hidden="true"></i>Baris</button>
                            <button type="button" class="{{ $tombolAlat }}" x-on:click="tambahKolom(i)" title="Tambah kolom di kanan sel yang dipilih"><i class="bi bi-plus-lg" aria-hidden="true"></i>Kolom</button>
                            <span class="{{ $pemisah }}" aria-hidden="true"></span>
                            <button type="button" class="{{ $tombolAlat }}" x-on:click="hapusBaris(i)" title="Hapus baris sel yang dipilih"><i class="bi bi-dash-lg" aria-hidden="true"></i>Baris</button>
                            <button type="button" class="{{ $tombolAlat }}" x-on:click="hapusKolom(i)" title="Hapus kolom sel yang dipilih"><i class="bi bi-dash-lg" aria-hidden="true"></i>Kolom</button>
                            <span class="{{ $pemisah }}" aria-hidden="true"></span>
                            <button type="button" class="{{ $tombolAlat }} hover:text-rose-600! dark:hover:text-rose-300!" x-on:click="hapusTabel(i)" title="Hapus tabel" aria-label="Hapus tabel"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse text-sm">
                                <tbody>
                                    <template x-for="(baris, r) in p.sel" :key="r">
                                        <tr class="[&:last-child>td]:border-b-0" x-bind:class="p.judul && r === 0 && 'bg-slate-50 font-semibold dark:bg-navy-800/50'">
                                            <template x-for="(isi, c) in baris" :key="c">
                                                <td class="min-w-32 border-r border-b border-slate-200 p-0 align-top last:border-r-0 dark:border-navy-700"
                                                    x-bind:class="sel?.i === i && (sel.r === r || sel.c === c) && 'bg-primary-50/50 dark:bg-primary-500/5'">
                                                    <textarea rows="1" x-bind:data-sel="`${p.id}-${r}-${c}`" x-bind:value="isi"
                                                        x-bind:style="`text-align: ${rataSel(p, r, c)}`"
                                                        x-on:input="ubahSel(i, r, c, $event.target.value)"
                                                        x-on:focus="sel = { i, r, c }"
                                                        x-on:keydown.enter.prevent="selBawah(i, r, c)"
                                                        x-on:paste="tempelDiSel($event, i, r, c)"
                                                        x-bind:aria-label="`Tabel, baris ${r + 1} kolom ${c + 1}`"
                                                        class="block w-full resize-none border-0 bg-transparent px-2.5 py-2 text-sm leading-snug field-sizing-content focus:bg-white focus:ring-2 focus:ring-primary-300 focus:ring-inset dark:focus:bg-navy-900"></textarea>
                                                </td>
                                            </template>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>
