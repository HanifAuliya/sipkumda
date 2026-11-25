{{--
    Editor lampiran seperti Word (resources/js/alpine/editor-lampiran.js, Tiptap). Isinya terikat ke `teks` milik
    baris-kerja. Dibungkus elemen ber-x-ref supaya baris-kerja bisa memfokuskannya; wire:ignore karena isinya dibangun
    Tiptap. mousedown.prevent pada tombol menjaga pilihan teks di editor. Bilah alat menempel di atas saat digulir.
    $label: nama untuk pembaca layar, $kertas: bekal kertas dari BarisKerja::kertasLampiran().
--}}
@php
    $tombol = 'inline-flex h-8 min-w-8 items-center justify-center gap-1 rounded-md px-1.5 text-sm font-medium text-slate-600 transition hover:bg-white hover:text-navy-900 hover:shadow-xs disabled:pointer-events-none disabled:opacity-40 aria-expanded:bg-white aria-expanded:text-primary-700 aria-expanded:shadow-xs aria-pressed:bg-white aria-pressed:text-primary-700 aria-pressed:shadow-xs aria-pressed:ring-1 aria-pressed:ring-primary-200 dark:text-slate-300 dark:hover:bg-navy-700 dark:hover:text-white dark:aria-expanded:bg-navy-700 dark:aria-expanded:text-primary-300 dark:aria-pressed:bg-navy-700 dark:aria-pressed:text-primary-300 dark:aria-pressed:ring-primary-500/40';
    $pilihan = 'h-8 leading-[1.875rem] rounded-md border-slate-200 bg-white py-0 pr-8 pl-2 text-sm text-slate-600 dark:border-navy-700 dark:bg-navy-900 dark:text-slate-300';
    $angka = 'h-8 w-16 rounded-md border-slate-200 bg-white px-2 py-0 text-sm text-slate-700 tabular-nums dark:border-navy-700 dark:bg-navy-900 dark:text-slate-200';
    $pemisah = 'mx-0.5 h-5 w-px bg-slate-200 dark:bg-navy-700';
    $panel = 'absolute top-full left-0 z-30 mt-1 min-w-48 rounded-lg border border-slate-200 bg-white p-1.5 text-left shadow-lg dark:border-navy-700 dark:bg-navy-900';
    $butir = 'flex w-full items-center gap-2.5 rounded-md px-2.5 py-1.5 text-left text-sm whitespace-nowrap text-slate-700 hover:bg-slate-100 disabled:pointer-events-none disabled:opacity-40 dark:text-slate-200 dark:hover:bg-navy-800';
    $garisMenu = 'my-1 border-t border-slate-100 dark:border-navy-700';

    // Menu tabel: dipakai bilah alat dan klik kanan. [perintah, nilai, ikon, nama, syarat nonaktif]
    $sisipkan = [
        ['sisipBaris', '-1', 'rows-plus-top', 'Baris di atas'], ['sisipBaris', '1', 'rows-plus-bottom', 'Baris di bawah'],
        ['sisipKolom', '-1', 'columns-plus-left', 'Kolom di kiri'], ['sisipKolom', '1', 'columns-plus-right', 'Kolom di kanan'],
    ];
    $hapus = [['hapusIsi', null, 'eraser', 'Isi sel'], ['hapusBaris', null, 'rows', 'Baris'], ['hapusKolom', null, 'columns', 'Kolom'], ['hapusTabel', null, 'trash', 'Tabel']];
    $memilih = [['pilih', "'sel'", 'square', 'Sel'], ['pilih', "'baris'", 'rows', 'Baris'], ['pilih', "'kolom'", 'columns', 'Kolom'], ['pilih', "'tabel'", 'table', 'Seluruh tabel']];
    $tegak = [['top', 'align-top', 'Rata atas'], ['middle', 'align-middle', 'Rata tengah (atas-bawah)'], ['bottom', 'align-bottom', 'Rata bawah']];
    $ikonGaris = [
        'semua' => ['Semua garis', 'border-2 border-current after:absolute after:inset-x-0 after:top-1/2 after:border-t-2 after:-translate-y-1/2 before:absolute before:inset-y-0 before:left-1/2 before:border-l-2 before:-translate-x-1/2'],
        'luar' => ['Garis luar', 'border-2 border-current'],
        'dalam' => ['Garis dalam', 'border border-dashed border-slate-300 after:absolute after:inset-x-0 after:top-1/2 after:border-t-2 after:border-slate-700 dark:after:border-slate-200 after:-translate-y-1/2 before:absolute before:inset-y-0 before:left-1/2 before:border-l-2 before:border-slate-700 dark:before:border-slate-200 before:-translate-x-1/2'],
        'tanpa' => ['Tanpa garis', 'border border-dashed border-slate-300'],
        'atas' => ['Garis atas', 'border border-dashed border-slate-300 border-t-2 border-t-current [border-top-style:solid]'],
        'bawah' => ['Garis bawah', 'border border-dashed border-slate-300 border-b-2 border-b-current [border-bottom-style:solid]'],
        'kiri' => ['Garis kiri', 'border border-dashed border-slate-300 border-l-2 border-l-current [border-left-style:solid]'],
        'kanan' => ['Garis kanan', 'border border-dashed border-slate-300 border-r-2 border-r-current [border-right-style:solid]'],
    ];
    $warna = ['' => 'Tanpa arsiran', '#f2f2f2' => 'Abu-abu sangat muda', '#d9d9d9' => 'Abu-abu muda', '#bfbfbf' => 'Abu-abu', '#deeaf6' => 'Biru muda', '#e2efd9' => 'Hijau muda', '#fff2cc' => 'Kuning muda', '#fbe4d5' => 'Jingga muda'];
@endphp

<div x-data="editorLampiran" role="group" aria-label="{{ $label ?? 'Lampiran' }}" class="editor-lampiran relative"
    x-on:contextmenu="bukaKlikKanan($event)" x-on:keydown.escape="menu && ($event.stopPropagation(), menu = null)">
    <p x-show="! siap && ! gagal" class="py-6 text-center text-sm text-slate-500 dark:text-slate-400"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Memuat editor…</p>
    <p x-show="gagal" x-cloak class="rounded-lg bg-rose-50 px-3.5 py-3 text-sm text-rose-700">Editor gagal dimuat. Muat ulang halaman.</p>

    <div x-show="siap" x-cloak wire:ignore class="sticky top-0 z-20 mx-auto mb-4 max-w-6xl rounded-lg border border-slate-200 bg-slate-50 shadow-card dark:border-navy-700 dark:bg-navy-800">
    <div role="toolbar" aria-label="Format lampiran" class="flex flex-wrap items-center justify-center gap-0.5 px-2 py-1">
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
            @foreach ([7, 8, 9, 10, 11, 14, 16] as $ukuran)
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
        <div class="relative" x-on:click.outside="menu === 'tabel' && (menu = null)">
            <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="sorot = { baris: 0, kolom: 0 }; bukaMenu('tabel')" x-bind:aria-expanded="(menu === 'tabel').toString()" x-bind:disabled="!! st.tabel" title="Sisipkan tabel"><i class="bi bi-table" aria-hidden="true"></i>Tabel<i class="bi bi-chevron-down text-xs" aria-hidden="true"></i></button>
            <div x-show="menu === 'tabel'" x-cloak class="{{ $panel }} right-0 left-auto" x-on:mousedown.prevent>
                <p class="px-1 pb-1.5 text-sm text-slate-600 dark:text-slate-300" x-text="sorot.kolom ? `${sorot.kolom} kolom x ${sorot.baris} baris` : 'Pilih ukuran tabel'"></p>
                <div class="grid w-max grid-cols-[repeat(10,1.25rem)] gap-0.5" x-on:mouseleave="sorot = { baris: 0, kolom: 0 }">
                    @foreach (range(1, 8) as $b)
                        @foreach (range(1, 10) as $k)
                            <button type="button" class="size-5 rounded-xs border" x-on:mouseenter="sorot = { baris: {{ $b }}, kolom: {{ $k }} }" x-on:focus="sorot = { baris: {{ $b }}, kolom: {{ $k }} }" x-on:click="sorot = { baris: {{ $b }}, kolom: {{ $k }} }; sisipTabel()"
                                x-bind:class="sorot.baris >= {{ $b }} && sorot.kolom >= {{ $k }} ? 'border-primary-400 bg-primary-100' : 'border-slate-300 bg-white'" aria-label="Tabel {{ $k }} kolom {{ $b }} baris"></button>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
        <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="jalankan('setHorizontalRule')" title="Garis mendatar" aria-label="Garis mendatar"><i class="bi bi-dash-lg" aria-hidden="true"></i></button>
    </div>

    {{-- Alat tabel hanya tampil saat kursor di dalam tabel. --}}
    <div x-show="st.tabel" x-cloak role="toolbar" aria-label="Alat tabel" class="flex flex-wrap items-center justify-center gap-0.5 border-t border-slate-200 px-2 py-1 dark:border-navy-700">
        @foreach (['sisipkan' => ['Sisipkan', $sisipkan], 'hapus' => ['Hapus', $hapus], 'memilih' => ['Pilih', $memilih]] as $kunci => [$nama, $isi])
            <div class="relative" x-on:click.outside="menu === '{{ $kunci }}' && (menu = null)">
                <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="bukaMenu('{{ $kunci }}')" x-bind:aria-expanded="(menu === '{{ $kunci }}').toString()">{{ $nama }}<i class="bi bi-chevron-down text-xs" aria-hidden="true"></i></button>
                <div x-show="menu === '{{ $kunci }}'" x-cloak class="{{ $panel }}" x-on:mousedown.prevent>
                    @foreach ($isi as [$perintah, $nilai, $ikon, $teksButir])
                        <button type="button" class="{{ $butir }}" x-on:click="tabel('{{ $perintah }}'{{ $nilai === null ? '' : ", {$nilai}" }})"><i class="bi bi-{{ $ikon }} text-base text-slate-400" aria-hidden="true"></i>{{ $teksButir }}</button>
                    @endforeach
                </div>
            </div>
        @endforeach
        <span class="{{ $pemisah }}" aria-hidden="true"></span>
        <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="tabel('gabung')" x-bind:disabled="! st.tabel?.bisaGabung" title="Seret beberapa sel dulu, lalu gabungkan">Gabung</button>
        <div class="relative" x-on:click.outside="menu === 'bagi' && (menu = null)">
            <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="bukaMenu('bagi')" x-bind:aria-expanded="(menu === 'bagi').toString()" title="Bagi sel menjadi beberapa kolom atau baris">Bagi<i class="bi bi-chevron-down text-xs" aria-hidden="true"></i></button>
            <form x-show="menu === 'bagi'" x-cloak class="{{ $panel }} w-56 space-y-2 p-3" x-on:submit.prevent="tabel('bagi', bagi.kolom, bagi.baris)">
                @foreach (['kolom' => 'Jumlah kolom', 'baris' => 'Jumlah baris'] as $kunci => $nama)
                    <label class="flex items-center justify-between gap-3 text-sm text-slate-700 dark:text-slate-200">{{ $nama }}<input type="number" min="1" max="20" x-model.number="bagi.{{ $kunci }}" class="{{ $angka }}"></label>
                @endforeach
                <div class="flex justify-end gap-2 pt-1">
                    <x-ui.button size="xs" variant="outline" x-show="st.tabel?.bisaPisah" x-on:click="tabel('pisah')" title="Mengembalikan sel gabungan menjadi sel-sel asalnya">Pisahkan</x-ui.button>
                    <x-ui.button size="xs" type="submit">Bagi</x-ui.button>
                </div>
            </form>
        </div>
        <span class="{{ $pemisah }}" aria-hidden="true"></span>
        @foreach (['lebarKolom' => ['Lebar', 'lebar', 'Lebar kolom dalam cm'], 'tinggiBaris' => ['Tinggi', 'tinggi', 'Tinggi baris dalam cm; 0 = mengikuti isi']] as $perintah => [$nama, $kunci, $judulIsian])
            <label class="inline-flex items-center gap-1.5 px-1 text-sm text-slate-600 dark:text-slate-300" title="{{ $judulIsian }}">{{ $nama }}
                <input type="number" min="0" max="33" step="0.1" class="{{ $angka }}" x-bind:value="st.tabel?.{{ $kunci }}" x-on:change="ukuranTabel('{{ $perintah }}', $el)" x-on:keydown.enter.prevent="$el.blur()">
            </label>
        @endforeach
        <div class="relative" x-on:click.outside="menu === 'ukuran' && (menu = null)">
            <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="bukaMenu('ukuran')" x-bind:aria-expanded="(menu === 'ukuran').toString()">Ratakan<i class="bi bi-chevron-down text-xs" aria-hidden="true"></i></button>
            <div x-show="menu === 'ukuran'" x-cloak class="{{ $panel }}" x-on:mousedown.prevent>
                @foreach ([['samaKolom', 'columns', 'Kolom sama lebar'], ['samaBaris', 'rows', 'Baris sama tinggi'], ['selebarKertas', 'arrows-horizontal', 'Tabel selebar kertas'], ['sesuaiIsi', 'arrows-in-line', 'Kolom sesuai isinya']] as [$perintah, $ikon, $nama])
                    <button type="button" class="{{ $butir }}" x-on:click="tabel('{{ $perintah }}')"><i class="bi bi-{{ $ikon }} text-base text-slate-400" aria-hidden="true"></i>{{ $nama }}</button>
                @endforeach
            </div>
        </div>
        <span class="{{ $pemisah }}" aria-hidden="true"></span>
        @foreach ($tegak as [$nilai, $ikon, $nama])
            <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="tabel('tegak', '{{ $nilai }}')" x-bind:aria-pressed="(st.tabel?.tegak === '{{ $nilai }}').toString()" title="{{ $nama }}" aria-label="{{ $nama }}"><i class="bi bi-{{ $ikon }}" aria-hidden="true"></i></button>
        @endforeach
        <span class="{{ $pemisah }}" aria-hidden="true"></span>
        <div class="relative" x-on:click.outside="menu === 'garis' && (menu = null)">
            <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="bukaMenu('garis')" x-bind:aria-expanded="(menu === 'garis').toString()" title="Garis sel yang dipilih">Garis<i class="bi bi-chevron-down text-xs" aria-hidden="true"></i></button>
            <div x-show="menu === 'garis'" x-cloak class="{{ $panel }} max-sm:right-0 max-sm:left-auto">
                <div class="grid grid-cols-4 gap-1" x-on:mousedown.prevent>
                    @foreach ($ikonGaris as $mana => [$nama, $bentuk])
                        <button type="button" class="{{ $tombol }} size-10 text-slate-700 hover:bg-slate-100" x-on:click="garis('{{ $mana }}')" title="{{ $nama }}" aria-label="{{ $nama }}"><span class="relative size-4.5 {{ $bentuk }}" aria-hidden="true"></span></button>
                    @endforeach
                </div>
                <div class="mt-1.5 flex gap-1.5 border-t border-slate-100 pt-2 dark:border-navy-700">
                    <select class="{{ $pilihan }} flex-1" aria-label="Jenis garis" x-model="jenisGaris">
                        @foreach (['solid' => 'Tunggal', 'double' => 'Ganda', 'dashed' => 'Putus-putus', 'dotted' => 'Titik-titik'] as $nilai => $nama)
                            <option value="{{ $nilai }}">{{ $nama }}</option>
                        @endforeach
                    </select>
                    <select class="{{ $pilihan }}" aria-label="Tebal garis" x-model="tebalGaris">
                        @foreach (['0.5' => '½ pt', '1.5' => '1½ pt', '2.25' => '2¼ pt', '3' => '3 pt'] as $nilai => $nama)
                            <option value="{{ $nilai }}">{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>
                <p class="mt-1.5 max-w-52 px-1 text-sm text-slate-500 dark:text-slate-400">Pilih jenis dan tebalnya, lalu sisi garisnya.</p>
            </div>
        </div>
        <div class="relative" x-on:click.outside="menu === 'arsiran' && (menu = null)">
            <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="bukaMenu('arsiran')" x-bind:aria-expanded="(menu === 'arsiran').toString()" title="Warna latar sel"><span class="size-4 rounded-xs border border-slate-400" x-bind:style="{ backgroundColor: st.tabel?.latar || '#fff' }" aria-hidden="true"></span>Arsiran<i class="bi bi-chevron-down text-xs" aria-hidden="true"></i></button>
            <div x-show="menu === 'arsiran'" x-cloak class="{{ $panel }} right-0 left-auto" x-on:mousedown.prevent>
                <div class="grid grid-cols-4 gap-1.5 p-1">
                    @foreach ($warna as $nilai => $nama)
                        <button type="button" class="size-9 rounded-md border border-slate-300 hover:ring-2 hover:ring-primary-300 {{ $nilai === '' ? 'bg-[linear-gradient(to_top_right,#fff_47%,#f43f5e_48%,#f43f5e_52%,#fff_53%)]' : '' }}" @if ($nilai !== '') style="background-color: {{ $nilai }}" @endif x-on:click="tabel('latar', '{{ $nilai }}')" title="{{ $nama }}" aria-label="{{ $nama }}"></button>
                    @endforeach
                </div>
            </div>
        </div>
        <span class="{{ $pemisah }}" aria-hidden="true"></span>
        <div class="relative" x-on:click.outside="menu === 'lain' && (menu = null)">
            <button type="button" class="{{ $tombol }}" x-on:mousedown.prevent x-on:click="bukaMenu('lain')" x-bind:aria-expanded="(menu === 'lain').toString()">Tabel<i class="bi bi-chevron-down text-xs" aria-hidden="true"></i></button>
            <div x-show="menu === 'lain'" x-cloak class="{{ $panel }} right-0 left-auto" x-on:mousedown.prevent>
                <button type="button" class="{{ $butir }}" x-on:click="tabel('barisJudul')" title="Ditebalkan dan diulang di tiap halaman"><i class="bi text-base text-primary-600" x-bind:class="st.tabel?.judul ? 'bi-check2' : 'bi-card-heading text-slate-400'" aria-hidden="true"></i>Baris judul, diulang tiap halaman</button>
                <div class="{{ $garisMenu }}"></div>
                @foreach ([['', 'text-left', 'Tabel di kiri'], ['center', 'text-center', 'Tabel di tengah'], ['right', 'text-right', 'Tabel di kanan']] as [$nilai, $ikon, $nama])
                    <button type="button" class="{{ $butir }}" x-on:click="tabel('letak', '{{ $nilai }}')"><i class="bi text-base" x-bind:class="st.tabel?.letak === '{{ $nilai }}' ? 'bi-check2 text-primary-600' : 'bi-{{ $ikon }} text-slate-400'" aria-hidden="true"></i>{{ $nama }}</button>
                @endforeach
                <div class="{{ $garisMenu }}"></div>
                @foreach ([['pindahBaris', '-1', 'arrow-up', 'Pindah baris ke atas'], ['pindahBaris', '1', 'arrow-down', 'Pindah baris ke bawah'], ['pindahKolom', '-1', 'arrow-left', 'Pindah kolom ke kiri'], ['pindahKolom', '1', 'arrow-right', 'Pindah kolom ke kanan']] as [$perintah, $nilai, $ikon, $nama])
                    <button type="button" class="{{ $butir }}" x-on:click="tabel('{{ $perintah }}', {{ $nilai }})"><i class="bi bi-{{ $ikon }} text-base text-slate-400" aria-hidden="true"></i>{{ $nama }}</button>
                @endforeach
                <div class="{{ $garisMenu }}"></div>
                <button type="button" class="{{ $butir }}" x-on:click="tabel('pisahTabel')" x-bind:disabled="! st.tabel?.bisaPisahTabel"><i class="bi bi-layout-split text-base text-slate-400" aria-hidden="true"></i>Pisah tabel di atas baris ini</button>
            </div>
        </div>
    </div>

    </div>

    <div wire:ignore>
        <div x-show="siap" x-cloak class="mb-4 flex justify-center">
            <label class="inline-flex min-h-9 cursor-pointer items-center gap-2 rounded-lg bg-white px-3.5 py-2 text-sm font-semibold text-primary-600 ring-1 ring-primary-200 transition ring-inset hover:bg-primary-50 focus-within:ring-3 dark:bg-transparent dark:text-primary-300 dark:ring-primary-500/40 dark:hover:bg-primary-500/10"
                x-bind:class="membaca && 'pointer-events-none opacity-50'" title="Isi file Word atau PDF masuk ke editor ini dan bisa diubah">
                <i class="bi bi-file-earmark-arrow-up text-base" aria-hidden="true"></i>Ambil dari Word / PDF
                <input type="file" class="sr-only" accept=".docx,.pdf,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" x-on:change="ambilFile($event.target)">
            </label>
        </div>
        <p x-show="membaca" x-cloak class="mx-auto mb-3 max-w-3xl text-center text-sm text-slate-600 dark:text-slate-300"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Membaca <span x-text="membaca"></span>…</p>
        <p x-show="galatBaca" x-cloak x-text="galatBaca" role="alert" class="mx-auto mb-3 max-w-3xl rounded-lg bg-rose-50 px-4 py-2.5 text-center text-sm font-medium text-rose-700 dark:bg-rose-500/10 dark:text-rose-200"></p>
        <p x-show="catatanBaca" x-cloak role="status" class="mx-auto mb-3 flex max-w-3xl items-start justify-center gap-3 rounded-lg bg-sky-50 px-4 py-2.5 text-sm text-sky-800 dark:bg-sky-500/10 dark:text-sky-200">
            <span x-text="catatanBaca"></span>
            <button type="button" class="shrink-0" x-on:click="catatanBaca = ''" title="Tutup" aria-label="Tutup"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </p>
    </div>

    <x-naskah.kertas-lampiran :kertas="$kertas">
        <div data-wadah="{{ round($kertas['lebarIsi']) }}" wire:ignore></div>
    </x-naskah.kertas-lampiran>

    {{-- Menu klik kanan di dalam tabel. --}}
    <div data-klik-kanan x-show="menu === 'klik-kanan'" x-cloak wire:ignore x-on:click.outside="menu === 'klik-kanan' && (menu = null)" x-on:mousedown.prevent x-on:contextmenu.prevent.stop
        x-bind:style="{ left: klikKanan.x + 'px', top: klikKanan.y + 'px' }" class="absolute z-40 w-60 rounded-lg border border-slate-200 bg-white p-1.5 shadow-lg dark:border-navy-700 dark:bg-navy-900">
        @foreach ($sisipkan as [$perintah, $nilai, $ikon, $nama])
            <button type="button" class="{{ $butir }}" x-on:click="tabel('{{ $perintah }}', {{ $nilai }})"><i class="bi bi-{{ $ikon }} text-base text-slate-400" aria-hidden="true"></i>Sisipkan {{ lcfirst($nama) }}</button>
        @endforeach
        <div class="{{ $garisMenu }}"></div>
        @foreach ($hapus as [$perintah, $nilai, $ikon, $nama])
            <button type="button" class="{{ $butir }}" x-on:click="tabel('{{ $perintah }}')"><i class="bi bi-{{ $ikon }} text-base text-slate-400" aria-hidden="true"></i>Hapus {{ lcfirst($nama) }}</button>
        @endforeach
        <div class="{{ $garisMenu }}"></div>
        <button type="button" class="{{ $butir }}" x-show="st.tabel?.bisaGabung" x-on:click="tabel('gabung')"><i class="bi bi-intersect text-base text-slate-400" aria-hidden="true"></i>Gabung sel</button>
        <button type="button" class="{{ $butir }}" x-on:click="tabel('bagi', 2, 1)"><i class="bi bi-layout-split text-base text-slate-400" aria-hidden="true"></i>Bagi sel menjadi 2 kolom</button>
        <button type="button" class="{{ $butir }}" x-on:click="tabel('bagi', 1, 2)"><i class="bi bi-rows text-base text-slate-400" aria-hidden="true"></i>Bagi sel menjadi 2 baris</button>
        <div class="flex gap-0.5 px-1 pt-1">
            @foreach ($tegak as [$nilai, $ikon, $nama])
                <button type="button" class="{{ $tombol }} hover:bg-slate-100" x-on:click="tabel('tegak', '{{ $nilai }}')" title="{{ $nama }}" aria-label="{{ $nama }}"><i class="bi bi-{{ $ikon }}" aria-hidden="true"></i></button>
            @endforeach
        </div>
        <p class="px-2.5 pt-1.5 text-sm text-slate-500 dark:text-slate-400">Menempel: Ctrl+V</p>
    </div>

    <p class="mx-auto mt-3 max-w-3xl text-center text-sm text-slate-500 dark:text-slate-400">
        Salin dari Word atau Excel lalu tempel (Ctrl+V), atau ambil langsung dari filenya. Di tabel: seret garis untuk mengatur lebar dan tinggi, klik tepi atas atau kiri untuk memilih kolom atau baris, klik kanan untuk menu lainnya. Garis abu-abu di kertas memperkirakan batas halaman.
    </p>
</div>
