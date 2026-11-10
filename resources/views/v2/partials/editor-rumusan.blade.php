{{--
    Editor rumusan pasal (resources/js/alpine/editor-rumusan.js). Teks diketik seperti biasa, tabel tampil
    sebagai tabel yang selnya bisa diisi. Hasilnya tetap disimpan sebagai teks "| a | b |" di `teks` milik baris-kerja.
    wire:ignore: isinya dibangun Alpine, jadi tidak boleh ditimpa saat Livewire merender ulang.
    Variabel: $barisMinimal (tinggi awal kotak teks), $label (nama untuk pembaca layar), $serif (kelas huruf naskah).
--}}
@php
    $tombolAlat = 'inline-flex h-8 items-center gap-1.5 rounded-md px-2 text-sm font-medium text-slate-600 transition hover:bg-white hover:text-navy-900 hover:shadow-xs dark:text-slate-300 dark:hover:bg-navy-700 dark:hover:text-white';
@endphp

<div x-data="editorRumusan({{ (int) $barisMinimal }})" wire:ignore x-on:sisip-tabel="sisipTabel()" role="group" aria-label="{{ $label }}"
    class="rounded-lg border border-slate-200 bg-white shadow-xs transition focus-within:border-primary-400 focus-within:ring-3 focus-within:ring-primary-100 dark:border-navy-700 dark:bg-navy-950 dark:focus-within:border-primary-500 dark:focus-within:ring-primary-500/20">
    <template x-for="(p, i) in potong" :key="p.id">
        <div>
            <template x-if="p.jenis === 'teks'">
                <textarea data-teks x-bind:value="p.isi" x-on:input="ubahTeks(i, $event.target.value)"
                    x-on:blur="kursor = { i, posisi: $event.target.selectionStart }" x-on:paste="tempelDiTeks($event, i)"
                    x-bind:placeholder="adaTabel ? 'Ketik teks di sini…' : ''"
                    x-bind:style="`min-height: calc(${tinggiTeks(p)}lh + 1.25rem)`"
                    aria-label="{{ $label }}"
                    class="{{ $serif }} block w-full resize-y border-0 bg-transparent px-3.5 py-2.5 field-sizing-content placeholder:font-sans placeholder:text-sm placeholder:text-slate-400 focus:ring-0"></textarea>
            </template>

            <template x-if="p.jenis === 'tabel'">
                <div class="mx-3 my-1 overflow-hidden rounded-lg ring-1 ring-slate-300 dark:ring-navy-600">
                    <div class="flex flex-wrap items-center gap-1 border-b border-slate-200 bg-slate-50 px-2 py-1 dark:border-navy-700 dark:bg-navy-800/70">
                        <span class="mr-auto inline-flex items-center gap-1.5 px-1 text-sm font-semibold text-slate-600 dark:text-slate-300">
                            <i class="bi bi-table" aria-hidden="true"></i>
                            Tabel <span class="font-normal text-slate-400" x-text="`${p.sel.length} × ${p.sel[0].length}`"></span>
                        </span>
                        <button type="button" class="{{ $tombolAlat }}" x-on:click="tambahBaris(i)" title="Tambah baris di bawah sel yang dipilih"><i class="bi bi-plus-lg" aria-hidden="true"></i>Baris</button>
                        <button type="button" class="{{ $tombolAlat }}" x-on:click="tambahKolom(i)" title="Tambah kolom di kanan sel yang dipilih"><i class="bi bi-plus-lg" aria-hidden="true"></i>Kolom</button>
                        <span class="mx-0.5 h-5 w-px bg-slate-200 dark:bg-navy-700" aria-hidden="true"></span>
                        <button type="button" class="{{ $tombolAlat }}" x-on:click="hapusBaris(i)" title="Hapus baris sel yang dipilih"><i class="bi bi-dash-lg" aria-hidden="true"></i>Baris</button>
                        <button type="button" class="{{ $tombolAlat }}" x-on:click="hapusKolom(i)" title="Hapus kolom sel yang dipilih"><i class="bi bi-dash-lg" aria-hidden="true"></i>Kolom</button>
                        <span class="mx-0.5 h-5 w-px bg-slate-200 dark:bg-navy-700" aria-hidden="true"></span>
                        <button type="button" class="{{ $tombolAlat }} hover:text-rose-600! dark:hover:text-rose-300!" x-on:click="hapusTabel(i)" title="Hapus tabel" aria-label="Hapus tabel"><i class="bi bi-trash" aria-hidden="true"></i></button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-sm">
                            <tbody>
                                <template x-for="(baris, r) in p.sel" :key="r">
                                    <tr class="[&:last-child>td]:border-b-0">
                                        <template x-for="(isi, c) in baris" :key="c">
                                            <td class="min-w-32 border-r border-b border-slate-200 p-0 align-top last:border-r-0 dark:border-navy-700"
                                                x-bind:class="sel?.i === i && (sel.r === r || sel.c === c) && 'bg-primary-50/50 dark:bg-primary-500/5'">
                                                <textarea rows="1" x-bind:data-sel="`${p.id}-${r}-${c}`" x-bind:value="isi"
                                                    x-on:input="ubahSel(i, r, c, $event.target.value)"
                                                    x-on:focus="sel = { i, r, c }"
                                                    x-on:keydown.enter.prevent="turun(i, r, c)"
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
