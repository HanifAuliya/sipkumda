@php
    use App\Models\NaskahBaris as B;
    use App\Services\AlurNaskah as A;

    $awal = (string) $baris->rumusan_awal;
    $sempurna = (string) $baris->rumusan_penyempurnaan;
    $status = $baris->status;
    $judul = $penanda ?: App\Support\TemplateNaskah::BAGIAN[$baris->bagian];
    $bisaDiatur = in_array($baris->bagian, B::BAGIAN_BERULANG, true);
    $pasal = $baris->bagian === 'batang_tubuh';
    // Pasal dan lampiran memakai editor bernomor dan bertabel; lampiran biasanya berupa daftar dan tabel.
    $pakaiEditor = $pasal || $baris->bagian === 'lampiran';
    $gayaAwal = $pasal ? 'ayat' : 'angka';
    $bisaDibuang = $mode === A::MODE_TELITI && $baris->sisipan();
    $bisaDiberiPenanda = $pasal && $baris->sisipan() && in_array($mode, [A::MODE_TELITI, A::MODE_KOREKSI], true);
    // Baris yang baru disisipkan langsung siap diisi.
    $baruDisisipkan = $bisaDibuang && $status === B::DIUBAH && $sempurna === '';

    // Isi naskah memakai huruf yang sama dengan hasil cetak.
    $serif = "font-['Bookman_Old_Style',Georgia,'Times_New_Roman',serif] text-[0.9375rem] leading-relaxed";
    $bidang = 'w-full rounded-lg border-slate-200 bg-white text-sm shadow-xs focus:border-primary-400 focus:ring-3 focus:ring-primary-100 dark:border-navy-700 dark:bg-navy-950 dark:focus:border-primary-500 dark:focus:ring-primary-500/20';
    $kotak = 'mt-1.5 min-h-24 rounded-lg px-3.5 py-3';
    $judulKolom = 'block text-sm font-semibold text-slate-500 dark:text-slate-400';
    $petunjukTabel = 'Enter membuat butir berikutnya, Tab dan Shift+Tab mengatur tingkat. Tabel dari Word atau Excel bisa langsung ditempel (Ctrl+V).';
@endphp

<article id="baris-{{ $baris->id }}" data-baris="{{ $baris->id }}"
    x-data="{
        ubah: false,
        minta: false,
        ketSemula: '',
        sorotan: false,
        label: @js((string) $baris->label),
        teks: @js($sempurna !== '' ? $sempurna : $awal),
        semula: '',
        ket: @js((string) $baris->keterangan),
        tersimpan: true,
        jedaSimpan: null,
        init() {
            this.semula = this.teks;
            @if ($baruDisisipkan) this.buka(); @endif
        },
        {{-- x-show baru menampilkan editor setelah microtask, jadi $nextTick kadang masih terlalu awal. --}}
        buka() { this.ubah = true; setTimeout(() => this.fokusEditor(), 30); },
        {{-- Rumusan pasal dan lampiran memakai editor bernomor; editor-nya pembungkus, bukan kotak teks. --}}
        fokusEditor() {
            const editor = this.$refs.editor;
            (editor.matches('textarea') ? editor : editor.querySelector('textarea'))?.focus();
        },
        batal() { this.ubah = false; this.teks = this.semula; },
        bukaMinta() { this.ketSemula = this.ket; this.minta = true; setTimeout(() => this.$refs.arahan.focus(), 30); },
        batalMinta() { this.minta = false; this.ket = this.ketSemula; this.$wire.$refresh(); },
        selesai() { this.ubah = false; this.semula = this.teks; },
        kembalikan() { this.teks = this.semula = @js($awal); },
        {{-- Diberi event input supaya simpan otomatis saat menyusun ikut berjalan. --}}
        setel(nilai) { this.teks = nilai; this.$nextTick(() => this.$refs.editor.dispatchEvent(new Event('input'))); },
        rapikan() { this.$wire.rapikanNomor(this.teks).then((hasil) => this.setel(hasil)); },
        {{--
            Simpan otomatis saat menyusun: 0,8 detik setelah berhenti mengetik, atau langsung saat kursor keluar
            dari isian. Yang kedua mencegah ketikan terakhir hilang bila pengguna langsung pindah bagian.
            $wire diambil saat mengetik, karena saat waktunya tiba barisnya bisa sudah tidak tampil.
        --}}
        simpanNanti() {
            const wire = this.$wire;
            clearTimeout(this.jedaSimpan);
            this.jedaSimpan = setTimeout(() => this.simpanSekarang(wire), 800);
        },
        simpanSekarang(wire = this.$wire) {
            if (this.jedaSimpan === null) return;
            clearTimeout(this.jedaSimpan);
            this.jedaSimpan = null;
            this.tersimpan = false;
            wire.simpanSusun(this.label, this.teks).then(() => this.tersimpan = true);
        },
    }"
    x-on:click="$dispatch('pratinjau-fokus', { id: {{ $baris->id }} })"
    x-on:focusin="$dispatch('pratinjau-fokus', { id: {{ $baris->id }} })"
    x-on:buka-editor="@if ($mode) buka(); @endif sorotan = true; setTimeout(() => sorotan = false, 1800)"
    x-bind:class="sorotan && 'bg-primary-50 dark:bg-primary-500/10'"
    class="@container scroll-mt-4 px-5 py-6 transition-colors sm:px-6">

    @if ($mode === A::MODE_SUSUN)
        {{-- PD menyusun rumusan: penanda di kiri, isian di kanan. Tersimpan otomatis saat berhenti mengetik. --}}
        <div class="grid gap-3 @xl:grid-cols-[11rem_minmax(0,1fr)] @xl:gap-6">
            <div class="min-w-0">
                @if ($pasal)
                    <label class="{{ $judulKolom }}" for="penanda-{{ $baris->id }}">BAB / Pasal</label>
                    <input id="penanda-{{ $baris->id }}" type="text" x-model="label" placeholder="Contoh: Pasal 5"
                        x-on:input="simpanNanti()" x-on:blur="simpanSekarang()"
                        class="{{ $bidang }} mt-1.5 font-semibold">
                @else
                    <h3 class="text-base font-bold">{{ $judul }}</h3>
                @endif
                <p x-show="!tersimpan" x-cloak class="mt-2 text-sm text-slate-500"><i class="bi bi-cloud-arrow-up"></i> Menyimpan…</p>
            </div>

            <div class="min-w-0">
                <p class="{{ $judulKolom }}">Rumusan</p>
                @php $barisAwal = min(14, max(3, substr_count($awal, "\n") + 2 + (int) (mb_strlen($awal) / 90))); @endphp
                @if ($pakaiEditor)
                    <div x-ref="editor" class="mt-1.5" x-on:input="simpanNanti()" x-on:focusout="$el.contains($event.relatedTarget) || simpanSekarang()">
                        @include('v2.partials.editor-rumusan', ['barisMinimal' => $barisAwal, 'label' => "Rumusan {$judul}"])
                    </div>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $petunjukTabel }}</p>
                @else
                    <textarea x-ref="editor" rows="{{ $barisAwal }}" x-model="teks" aria-label="Rumusan {{ $judul }}"
                        x-on:input="simpanNanti()" x-on:blur="simpanSekarang()"
                        class="{{ $bidang }} {{ $serif }} mt-1.5"></textarea>
                @endif

                @if ($bisaDiatur)
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap gap-2">
                            @if ($pasal)
                                <x-ui.button size="rapat" variant="ghost" icon="list-ol" x-on:click="rapikan()">Rapikan Nomor</x-ui.button>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-ui.button size="rapat" variant="outline-primary" icon="plus-lg" wire:click="tambah">Tambah Baris</x-ui.button>
                            <x-ui.button size="rapat" square variant="outline" icon="arrow-up" title="Pindah ke atas" aria-label="Pindah ke atas" wire:click="geser(-1)" />
                            <x-ui.button size="rapat" square variant="outline" icon="arrow-down" title="Pindah ke bawah" aria-label="Pindah ke bawah" wire:click="geser(1)" />
                            <x-ui.button size="rapat" square variant="soft-danger" icon="trash" title="Hapus baris ini" aria-label="Hapus baris ini" wire:click="buang" wire:confirm="Hapus baris ini?" />
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @else
        <header class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-base font-bold">{{ $judul }}</h3>
            <x-ui.badge size="lg" :color="B::WARNA_STATUS[$status]">{{ B::LABEL_STATUS[$status] }}</x-ui.badge>
        </header>

        {{-- Mengikuti kolom matriks: rumusan awal, rumusan penyempurnaan, keterangan. --}}
        <div class="grid gap-4 @xl:grid-cols-2 @5xl:grid-cols-[minmax(0,5fr)_minmax(0,5fr)_minmax(0,3fr)]">
            <div class="min-w-0">
                <p class="{{ $judulKolom }}">Rumusan Awal</p>
                <div class="{{ $serif }} {{ $kotak }} bg-slate-50 dark:bg-navy-800/60">
                    @if ($awal !== '')
                        @include('v2.partials.rumusan', ['teks' => $awal, 'lama' => null])
                    @else
                        —
                    @endif
                </div>
            </div>

            <div class="min-w-0">
                <p class="{{ $judulKolom }}">Rumusan Penyempurnaan</p>

                <div x-show="!ubah" class="{{ $serif }} {{ $kotak }} v2-diff ring-1 ring-slate-200 ring-inset dark:ring-navy-700">
                    @if ($status === B::DIHAPUS)
                        <span class="font-sans font-semibold text-rose-600 dark:text-rose-300">Dihapus</span>
                    @elseif ($status === B::TETAP)
                        <span class="font-sans font-semibold text-emerald-600 dark:text-emerald-300">Tetap</span>
                    @elseif ($sempurna !== '')
                        @include('v2.partials.rumusan', ['teks' => $sempurna, 'lama' => $awal])
                    @elseif ($status === B::PERLU_PD)
                        <span class="font-sans text-sm font-semibold text-orange-600 dark:text-orange-300">Menunggu perbaikan PD</span>
                    @else
                        <span class="font-sans text-sm text-slate-400">Belum ada penyempurnaan</span>
                    @endif
                </div>

                <div x-show="ubah" x-cloak class="mt-1.5 space-y-2">
                    @if ($bisaDiberiPenanda)
                        <div>
                            <label class="{{ $judulKolom }}" for="penanda-{{ $baris->id }}">BAB / Pasal</label>
                            <input id="penanda-{{ $baris->id }}" type="text" x-model="label" placeholder="Contoh: Pasal 5" class="{{ $bidang }} mt-1.5 font-semibold">
                        </div>
                    @endif
                    @php $barisAwal = min(16, max(5, substr_count($awal, "\n") + 3 + (int) (mb_strlen($awal) / 50))); @endphp
                    @if ($pakaiEditor)
                        <div x-ref="editor">
                            @include('v2.partials.editor-rumusan', ['barisMinimal' => $barisAwal, 'label' => "Rumusan penyempurnaan {$judul}"])
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $petunjukTabel }}</p>
                    @else
                        <textarea x-ref="editor" rows="{{ $barisAwal }}" x-model="teks" aria-label="Rumusan penyempurnaan {{ $judul }}"
                            class="{{ $bidang }} {{ $serif }}"></textarea>
                    @endif
                </div>
                @error('rumusan')
                    <p class="mt-1 text-sm font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <div class="min-w-0 @xl:col-span-2 @5xl:col-span-1" x-show="!minta">
                @if ($mode && $mode !== A::MODE_SESUAIKAN)
                    <label class="{{ $judulKolom }}" for="ket-{{ $baris->id }}">Keterangan</label>
                    <textarea id="ket-{{ $baris->id }}" x-model="ket" rows="4" placeholder="Alasan atau arahan untuk Perangkat Daerah" class="{{ $bidang }} mt-1.5"></textarea>
                @else
                    <p class="{{ $judulKolom }}">Keterangan</p>
                    <div class="{{ $kotak }} text-sm leading-relaxed whitespace-pre-line ring-1 ring-slate-200 ring-inset dark:ring-navy-700">{{ filled($baris->keterangan) ? $baris->keterangan : '—' }}</div>
                @endif
                @error('keterangan')
                    <p class="mt-1 text-sm font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
                @enderror
            </div>
        </div>

        @if ($mode === A::MODE_TELITI)
            {{-- Dibuka tombol Minta PD Perbaiki: kapan dipakai, apa yang ditulis, dan apa yang terjadi sesudahnya. --}}
            <div x-show="minta" x-cloak class="mt-4 rounded-xl bg-primary-50 p-4 ring-1 ring-primary-100 ring-inset dark:bg-primary-500/10 dark:ring-primary-500/20">
                <p class="text-sm font-semibold text-navy-900 dark:text-white">Minta PD memperbaiki baris ini</p>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                    Pakai ini bila hanya PD yang tahu isinya, misalnya data, besaran, atau dasar kebijakan. Bila Anda bisa merumuskannya sendiri, pilih Batal lalu Ubah.
                </p>

                <label class="{{ $judulKolom }} mt-3" for="arahan-{{ $baris->id }}">Apa yang perlu diperbaiki PD?</label>
                <textarea id="arahan-{{ $baris->id }}" x-ref="arahan" x-model="ket" rows="3" placeholder="Contoh: Mohon lengkapi besaran tarif dan dasar perhitungannya." class="{{ $bidang }} mt-1.5"></textarea>
                @error('keterangan')
                    <p class="mt-1 text-sm font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
                @enderror

                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Belum langsung terkirim. Baris ini ditandai dulu, lalu semua baris bertanda dikirim sekaligus lewat tombol Kirim ke PD setelah seluruh baris diperiksa.
                </p>

                <div class="mt-3 flex flex-wrap justify-end gap-2">
                    <x-ui.button size="rapat" variant="outline" x-on:click="batalMinta()">Batal</x-ui.button>
                    <x-ui.button size="rapat" icon="check2" x-on:click="$wire.mintaPd(null, ket).then((berhasil) => { if (berhasil) minta = false; })">Tandai untuk PD</x-ui.button>
                </div>
            </div>
        @endif

        @if ($mode === A::MODE_TELITI || $mode === A::MODE_KOREKSI)
            <div class="mt-4 flex flex-wrap items-center justify-between gap-2" x-show="!ubah && !minta">
                <div class="flex flex-wrap gap-2">
                    @if ($mode === A::MODE_TELITI && $bisaDiatur)
                        <x-ui.button size="rapat" variant="ghost" icon="plus-lg" wire:click="tambah">Sisipkan Baris</x-ui.button>
                    @endif
                    @if ($bisaDibuang)
                        <x-ui.button size="rapat" variant="ghost" icon="trash" wire:click="buangSisipan" wire:confirm="Hapus baris sisipan ini?">Hapus Baris</x-ui.button>
                    @elseif ($bisaDiatur)
                        <x-ui.button size="rapat" variant="ghost" icon="trash" x-on:click="$wire.hapus(ket).then(() => kembalikan())">Hapus Rumusan</x-ui.button>
                    @endif
                </div>
                <div class="ml-auto flex flex-wrap justify-end gap-2">
                    @if ($mode === A::MODE_TELITI)
                        <x-ui.button size="rapat" variant="outline" icon="person-exclamation" title="Hanya PD yang tahu isinya, misalnya data atau besaran" x-on:click="bukaMinta()">{{ $status === B::PERLU_PD ? 'Ubah Arahan ke PD' : 'Minta PD Perbaiki' }}</x-ui.button>
                    @endif
                    <x-ui.button size="rapat" variant="outline-primary" icon="pencil" title="Anda perbaiki sendiri rumusannya" x-on:click="buka()">Ubah</x-ui.button>
                    @unless ($baris->sisipan())
                        <x-ui.button size="rapat" variant="primary" icon="check2" title="Rumusan sudah benar, tidak perlu diubah" x-on:click="$wire.tetap(ket).then(() => kembalikan())">Tetap</x-ui.button>
                    @endunless
                </div>
            </div>
        @elseif ($mode === A::MODE_SESUAIKAN)
            <div class="mt-4 flex flex-wrap justify-end gap-2" x-show="!ubah">
                @if ($status === B::PERLU_PD)
                    <x-ui.button size="rapat" variant="outline" icon="check2" x-on:click="$wire.simpanSesuaian(teks)">Sudah Disesuaikan</x-ui.button>
                @endif
                <x-ui.button size="rapat" icon="pencil" :variant="$status === B::PERLU_PD ? 'primary' : 'outline-primary'" x-on:click="buka()">Perbaiki Rumusan</x-ui.button>
            </div>
        @endif

        {{-- Saat mengubah, hanya tombol penyimpanan yang tampil. --}}
        @if (in_array($mode, [A::MODE_TELITI, A::MODE_KOREKSI, A::MODE_SESUAIKAN], true))
            <div class="mt-4 flex flex-wrap items-center justify-between gap-2" x-show="ubah" x-cloak>
                <div class="flex flex-wrap gap-2">
                    @if ($pakaiEditor)
                        <x-ui.button size="rapat" variant="ghost" icon="list-ol" x-on:click="rapikan()">Rapikan Nomor</x-ui.button>
                    @endif
                    @if ($bisaDibuang)
                        <x-ui.button size="rapat" variant="ghost" icon="trash" wire:click="buangSisipan" wire:confirm="Hapus baris sisipan ini?">Hapus Baris</x-ui.button>
                    @endif
                </div>
                <div class="ml-auto flex flex-wrap justify-end gap-2">
                    <x-ui.button size="rapat" variant="outline" x-on:click="batal()">Batal</x-ui.button>
                    @if ($mode === A::MODE_SESUAIKAN)
                        <x-ui.button size="rapat" icon="check2" x-on:click="$wire.simpanSesuaian(teks).then(() => selesai())">Simpan</x-ui.button>
                    @else
                        <x-ui.button size="rapat" icon="check2" x-on:click="$wire.simpanPerbaikan(teks, ket, label).then((berhasil) => berhasil && selesai())">Simpan</x-ui.button>
                    @endif
                </div>
            </div>
        @endif
    @endif
</article>
