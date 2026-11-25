{{--
    Editor lampiran seperti Word (resources/js/alpine/editor-lampiran.js, Tiptap). Isinya terikat ke `teks` milik
    baris-kerja. Dibungkus elemen ber-x-ref supaya baris-kerja bisa memfokuskannya; wire:ignore karena isinya dibangun
    Tiptap. mousedown.prevent pada tombol menjaga pilihan teks di editor. $label: nama untuk pembaca layar.
--}}
@php
    $tombol = 'inline-flex h-8 min-w-8 items-center justify-center gap-1.5 rounded-md px-2 text-sm font-medium text-slate-600 transition hover:bg-white hover:text-navy-900 hover:shadow-xs disabled:pointer-events-none disabled:opacity-40 aria-pressed:bg-white aria-pressed:text-primary-700 aria-pressed:shadow-xs aria-pressed:ring-1 aria-pressed:ring-primary-200 dark:text-slate-300 dark:hover:bg-navy-700 dark:hover:text-white dark:aria-pressed:bg-navy-700 dark:aria-pressed:text-primary-300 dark:aria-pressed:ring-primary-500/40';
    $pilihan = 'h-8 leading-none rounded-md border-slate-200 bg-white py-0 pr-8 pl-2 text-sm text-slate-600 dark:border-navy-700 dark:bg-navy-900 dark:text-slate-300';
    $pemisah = 'mx-0.5 h-5 w-px bg-slate-200 dark:bg-navy-700';
@endphp

<div x-data="editorLampiran(@js($label ?? 'Lampiran'))" wire:ignore role="group" aria-label="{{ $label ?? 'Lampiran' }}"
    class="editor-lampiran overflow-hidden rounded-lg border border-slate-200 bg-white shadow-xs transition focus-within:border-primary-400 focus-within:ring-3 focus-within:ring-primary-100 dark:border-navy-700 dark:focus-within:border-primary-500 dark:focus-within:ring-primary-500/20">
    <p x-show="! siap && ! gagal" class="px-3.5 py-6 text-center text-sm text-slate-500"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Memuat editor…</p>
    <p x-show="gagal" x-cloak class="bg-rose-50 px-3.5 py-3 text-sm text-rose-700">Editor gagal dimuat. Muat ulang halaman.</p>

    <div x-show="siap" x-cloak role="toolbar" aria-label="Format lampiran" class="flex flex-wrap items-center gap-0.5 border-b border-slate-200 bg-slate-50 px-2 py-1 dark:border-navy-700 dark:bg-navy-800">
        <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="jalankan('undo')" x-bind:disabled="! st.bisaUrung" title="Urungkan (Ctrl+Z)" aria-label="Urungkan"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i></button>
        <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="jalankan('redo')" x-bind:disabled="! st.bisaUlang" title="Ulangi (Ctrl+Y)" aria-label="Ulangi"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i></button>
        <span class="{{ $pemisah }}" aria-hidden="true"></span>
        <select class="{{ $pilihan }}" aria-label="Jenis paragraf" x-bind:value="st.judul" x-on:change="pilihJudul(Number($event.target.value))">
            <option value="0">Paragraf</option>
            <option value="2">Judul</option>
            <option value="3">Subjudul</option>
        </select>
        <select class="{{ $pilihan }}" aria-label="Ukuran huruf" x-bind:value="st.ukuran" x-on:change="pilihUkuran($event.target.value)">
            <option value="">Ukuran naskah</option>
            @foreach ([8, 9, 10, 11, 14, 16] as $ukuran)
                <option value="{{ $ukuran }}pt">{{ $ukuran }}</option>
            @endforeach
        </select>
        <span class="{{ $pemisah }}" aria-hidden="true"></span>
        @foreach ([
            ['toggleBold', 'tebal', 'type-bold', 'Tebal (Ctrl+B)'], ['toggleItalic', 'miring', 'type-italic', 'Miring (Ctrl+I)'],
            ['toggleUnderline', 'garisBawah', 'type-underline', 'Garis bawah (Ctrl+U)'], ['toggleStrike', 'coret', 'type-strikethrough', 'Coret'],
            ['toggleSuperscript', 'atas', 'superscript', 'Tulisan atas'], ['toggleSubscript', 'bawah', 'subscript', 'Tulisan bawah'],
        ] as [$perintah, $keadaan, $ikon, $nama])
            <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="jalankan('{{ $perintah }}')" x-bind:aria-pressed="(!! st.{{ $keadaan }}).toString()" title="{{ $nama }}" aria-label="{{ $nama }}"><i class="bi bi-{{ $ikon }}" aria-hidden="true"></i></button>
        @endforeach
        <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="jalankan('unsetAllMarks')" title="Hapus format huruf" aria-label="Hapus format huruf"><i class="bi bi-eraser" aria-hidden="true"></i></button>
        <span class="{{ $pemisah }}" aria-hidden="true"></span>
        @foreach ([['left', 'text-left', 'Rata kiri'], ['center', 'text-center', 'Rata tengah'], ['right', 'text-right', 'Rata kanan'], ['justify', 'justify', 'Rata kanan-kiri']] as [$rata, $ikon, $nama])
            <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="jalankan('setTextAlign', '{{ $rata }}')" x-bind:aria-pressed="(st.rata === '{{ $rata }}').toString()" title="{{ $nama }}" aria-label="{{ $nama }}"><i class="bi bi-{{ $ikon }}" aria-hidden="true"></i></button>
        @endforeach
        <span class="{{ $pemisah }}" aria-hidden="true"></span>
        <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="jalankan('toggleOrderedList')" x-bind:aria-pressed="(!! st.nomor).toString()" title="Daftar bernomor" aria-label="Daftar bernomor"><i class="bi bi-list-ol" aria-hidden="true"></i></button>
        <select x-show="st.nomor" x-cloak class="{{ $pilihan }}" aria-label="Jenis nomor" x-bind:value="st.jenisNomor" x-on:change="pilihJenisNomor($event.target.value)">
            @foreach (['1' => '1.', 'a' => 'a.', 'A' => 'A.', 'i' => 'i.', 'I' => 'I.'] as $nilai => $nama)
                <option value="{{ $nilai }}">{{ $nama }}</option>
            @endforeach
        </select>
        <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="jalankan('toggleBulletList')" x-bind:aria-pressed="(!! st.poin).toString()" title="Daftar poin" aria-label="Daftar poin"><i class="bi bi-list-ul" aria-hidden="true"></i></button>
        <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="geser(-1)" title="Kurangi jorok (Shift+Tab)" aria-label="Kurangi jorok"><i class="bi bi-text-indent-right" aria-hidden="true"></i></button>
        <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="geser(1)" title="Tambah jorok (Tab)" aria-label="Tambah jorok"><i class="bi bi-text-indent-left" aria-hidden="true"></i></button>
        <span class="{{ $pemisah }}" aria-hidden="true"></span>
        <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="jalankan('insertTable', { rows: 3, cols: 3, withHeaderRow: true })" x-show="! st.tabel" title="Sisipkan tabel"><i class="bi bi-table" aria-hidden="true"></i>Tabel</button>
        <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="jalankan('setHorizontalRule')" title="Garis mendatar" aria-label="Garis mendatar"><i class="bi bi-dash-lg" aria-hidden="true"></i></button>
    </div>

    {{-- Alat tabel hanya tampil saat kursor di dalam tabel. --}}
    <div x-show="siap && st.tabel" x-cloak role="toolbar" aria-label="Alat tabel" class="flex flex-wrap items-center gap-0.5 border-b border-slate-200 bg-slate-50 px-2 py-1 dark:border-navy-700 dark:bg-navy-800">
        @foreach ([
            ['addRowBefore', 'Baris di atas'], ['addRowAfter', 'Baris di bawah'], ['deleteRow', 'Hapus baris'],
            ['addColumnBefore', 'Kolom di kiri'], ['addColumnAfter', 'Kolom di kanan'], ['deleteColumn', 'Hapus kolom'],
        ] as [$perintah, $nama])
            <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="jalankan('{{ $perintah }}')">{{ $nama }}</button>
        @endforeach
        <span class="{{ $pemisah }}" aria-hidden="true"></span>
        <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="jalankan('mergeCells')" x-bind:disabled="! st.bisaGabung" title="Pilih beberapa sel dulu dengan menyeret">Gabung sel</button>
        <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="jalankan('splitCell')" x-bind:disabled="! st.bisaPisah">Pisah sel</button>
        <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="jalankan('toggleHeaderRow')">Baris judul</button>
        <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="garis()" title="Garis sel yang dipilih, atau seluruh tabel bila hanya ada kursor">Garis</button>
        <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="jalankan('deleteTable')">Hapus tabel</button>
    </div>

    <div x-ref="wadah"></div>
</div>
<p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
    Salin dari Word atau Excel lalu tempel (Ctrl+V); tabel dan formatnya ikut. Seret garis kolom untuk mengatur lebarnya, seret beberapa sel untuk menggabungkannya.
</p>
