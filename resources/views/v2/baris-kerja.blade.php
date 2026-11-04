@php
    use App\Models\NaskahBaris as B;
    use App\Services\AlurNaskah as A;

    $awal = (string) $baris->rumusan_awal;
    $sempurna = (string) $baris->rumusan_penyempurnaan;
    $status = $baris->status;
    $tema = [
        B::BELUM => 'border-slate-300 dark:border-navy-600',
        B::TETAP => 'border-emerald-400',
        B::DIUBAH => 'border-primary-500',
        B::DIHAPUS => 'border-rose-400',
        B::PERLU_PD => 'border-orange-400',
        B::DISESUAIKAN => 'border-cyan-400',
    ][$status];
    $serif = "font-['Bookman_Old_Style',Georgia,'Times_New_Roman',serif]";
    $bidang = "w-full rounded-lg border-0 bg-white text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-primary-300 dark:bg-navy-950 dark:ring-navy-700";
@endphp

<article id="baris-{{ $baris->id }}" data-baris="{{ $baris->id }}"
    x-data="{
        ubah: false,
        label: @js((string) $baris->label),
        teks: @js($sempurna !== '' ? $sempurna : $awal),
        semula: '',
        ket: @js((string) $baris->keterangan),
        tersimpan: true,
        init() { this.semula = this.teks; },
        {{-- x-show baru menampilkan editor setelah microtask, jadi $nextTick kadang masih terlalu awal. --}}
        buka() { this.ubah = true; setTimeout(() => this.$refs.editor.focus(), 30); },
        batal() { this.ubah = false; this.teks = this.semula; },
        selesai() { this.ubah = false; this.semula = this.teks; },
        kembalikan() { this.teks = this.semula = this.$refs.awal.dataset.kosong ? '' : this.$refs.awal.textContent; },
        {{-- Diberi event input supaya simpan otomatis saat menyusun ikut berjalan. --}}
        setel(nilai) { this.teks = nilai; this.$nextTick(() => this.$refs.editor.dispatchEvent(new Event('input'))); },
        rapikan() { this.$wire.rapikanNomor(this.teks).then((hasil) => this.setel(hasil)); },
        sisipTabel() {
            const posisi = this.$refs.editor.selectionStart ?? this.teks.length;
            const sebelum = this.teks.slice(0, posisi);
            const contoh = (sebelum && ! sebelum.endsWith('\n') ? '\n' : '') + '| Kolom 1 | Kolom 2 | Kolom 3 |\n| isi | isi | isi |\n';
            this.setel(sebelum + contoh + this.teks.slice(posisi));
        },
    }"
    @class(['scroll-mt-48 rounded-xl border-l-4 bg-white shadow-sm ring-1 ring-slate-200/70 dark:bg-navy-900 dark:ring-navy-800', $tema])>

    <header class="flex flex-wrap items-center justify-between gap-2 px-4 pt-3">
        <h3 class="text-sm font-bold text-navy-900 dark:text-white">
            {{ $penanda ?: App\Support\TemplateNaskah::BAGIAN[$baris->bagian] }}
            @if ($penanda && ! $baris->label)
                <span class="ml-1 text-xs font-medium text-slate-400">{{ App\Support\TemplateNaskah::BAGIAN[$baris->bagian] }}</span>
            @endif
        </h3>
        <div class="flex items-center gap-2">
            <span x-show="!tersimpan" x-cloak class="text-xs text-slate-400"><i class="bi bi-cloud-arrow-up"></i> Menyimpan…</span>
            @if ($mode !== A::MODE_SUSUN)
                <x-ui.badge :color="B::WARNA_STATUS[$status]">{{ B::LABEL_STATUS[$status] }}</x-ui.badge>
            @endif
        </div>
    </header>

    <div class="px-4 pt-2 pb-4">
        @if ($mode === A::MODE_SUSUN)
            {{-- PD menyusun kolom 3. Tersimpan otomatis saat berhenti mengetik. --}}
            <div class="space-y-2">
                @if (! in_array($baris->bagian, ['menimbang', 'mengingat', 'judul', 'penutup', 'lampiran'], true))
                    <input type="text" x-model="label" placeholder="Penanda (BAB I, Pasal 5)" aria-label="Penanda baris"
                        x-on:input.debounce.800ms="tersimpan = false; $wire.simpanSusun(label, teks).then(() => tersimpan = true)"
                        class="{{ $bidang }} max-w-56 py-1.5">
                @endif
                <textarea x-ref="editor" rows="{{ min(12, max(2, substr_count($awal, "\n") + 2 + (int) (mb_strlen($awal) / 90))) }}" x-model="teks" aria-label="Rumusan"
                    x-on:input.debounce.800ms="tersimpan = false; $wire.simpanSusun(label, teks).then(() => tersimpan = true)"
                    class="{{ $bidang }} {{ $serif }} leading-relaxed"></textarea>
            </div>
            <div class="mt-2 flex flex-wrap gap-1">
                @if ($baris->bagian === 'batang_tubuh')
                    <x-ui.button size="xs" variant="ghost" icon="list-ol" x-on:click="rapikan()">Rapikan nomor</x-ui.button>
                    <x-ui.button size="xs" variant="ghost" icon="table" x-on:click="sisipTabel()">Sisipkan tabel</x-ui.button>
                @endif
                <x-ui.button size="xs" variant="ghost" icon="plus-lg" wire:click="tambah">Tambah di bawah</x-ui.button>
                <x-ui.button size="xs" variant="ghost" icon="arrow-up" wire:click="geser(-1)">Naik</x-ui.button>
                <x-ui.button size="xs" variant="ghost" icon="arrow-down" wire:click="geser(1)">Turun</x-ui.button>
                <x-ui.button size="xs" variant="ghost" icon="trash" wire:click="buang"
                    wire:confirm="Buang baris ini?">Buang</x-ui.button>
            </div>
        @else
            {{-- Rumusan awal dan penyempurnaan berdampingan --}}
            <div class="grid gap-3 lg:grid-cols-2">
                <div class="min-w-0">
                    <p class="text-[0.7rem] font-semibold tracking-wide text-slate-400 uppercase">Rumusan awal</p>
                    <div x-ref="awal" @if ($awal === '') data-kosong="1" @endif class="{{ $serif }} mt-1 text-sm leading-relaxed whitespace-pre-line">{{ $awal !== '' ? $awal : '—' }}</div>
                </div>

                <div class="min-w-0">
                    <p class="text-[0.7rem] font-semibold tracking-wide text-slate-400 uppercase">Rumusan penyempurnaan</p>

                    <div x-show="!ubah" class="{{ $serif }} v2-diff mt-1 text-sm leading-relaxed">
                        @if ($status === B::DIHAPUS)
                            <span class="font-sans font-semibold text-rose-600 dark:text-rose-300">Dihapus</span>
                        @elseif ($status === B::TETAP)
                            <span class="font-sans font-semibold text-emerald-600 dark:text-emerald-300">Tetap;</span>
                        @elseif ($sempurna !== '')
                            <span class="whitespace-pre-line">{!! App\Support\DiffKata::html($awal, $sempurna) !!}</span>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </div>

                    <div x-show="ubah" x-cloak class="mt-1">
                        <textarea x-ref="editor" rows="{{ min(14, max(4, substr_count($awal, "\n") + 3 + (int) (mb_strlen($awal) / 60))) }}" x-model="teks" aria-label="Rumusan penyempurnaan"
                            class="{{ $bidang }} {{ $serif }} leading-relaxed"></textarea>
                        @if ($baris->bagian === 'batang_tubuh')
                            <div class="mt-1 flex flex-wrap gap-1">
                                <x-ui.button size="xs" variant="ghost" icon="list-ol" x-on:click="rapikan()">Rapikan nomor</x-ui.button>
                                <x-ui.button size="xs" variant="ghost" icon="table" x-on:click="sisipTabel()">Sisipkan tabel</x-ui.button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Keterangan: kolom 5 --}}
            @if ($mode && $mode !== A::MODE_SESUAIKAN)
                <label class="mt-3 block">
                    <span class="text-[0.7rem] font-semibold tracking-wide text-slate-400 uppercase">Keterangan</span>
                    <input type="text" x-model="ket" class="{{ $bidang }} mt-1 py-1.5" placeholder="Alasan atau arahan untuk PD">
                </label>
            @elseif (filled($baris->keterangan))
                <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
                    <span class="font-semibold">Keterangan:</span> {{ $baris->keterangan }}
                </p>
            @endif

            @error('keterangan')
                <p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
            @enderror

            @if ($mode === A::MODE_TELITI || $mode === A::MODE_KOREKSI)
                <div class="mt-3 flex flex-wrap gap-2" x-show="!ubah">
                    <x-ui.button size="sm" variant="success" icon="check2" x-on:click="$wire.tetap(ket).then(() => kembalikan())">Tetap</x-ui.button>
                    <x-ui.button size="sm" variant="outline" icon="pencil" x-on:click="buka()">Ubah</x-ui.button>
                    @if ($mode === A::MODE_TELITI)
                        <x-ui.button size="sm" variant="outline" icon="person-exclamation" x-on:click="$wire.mintaPd(null, ket)">Minta PD perbaiki</x-ui.button>
                        <x-ui.button size="sm" variant="ghost" icon="plus-lg" wire:click="tambah">Sisipkan baris</x-ui.button>
                    @endif
                    <x-ui.button size="sm" variant="ghost" icon="trash" x-on:click="$wire.hapus(ket).then(() => kembalikan())">Hapus</x-ui.button>
                </div>
                <div class="mt-3 flex flex-wrap gap-2" x-show="ubah" x-cloak>
                    <x-ui.button size="sm" icon="check2" x-on:click="$wire.simpanPerbaikan(teks, ket).then(() => selesai())">Simpan</x-ui.button>
                    <x-ui.button size="sm" variant="ghost" x-on:click="batal()">Batal</x-ui.button>
                </div>
            @elseif ($mode === A::MODE_SESUAIKAN)
                <div class="mt-3 flex flex-wrap gap-2" x-show="!ubah">
                    <x-ui.button size="sm" icon="pencil" :variant="$status === B::PERLU_PD ? 'primary' : 'outline'" x-on:click="buka()">Perbaiki rumusan</x-ui.button>
                    @if ($status === B::PERLU_PD)
                        <x-ui.button size="sm" variant="outline" icon="check2" x-on:click="$wire.simpanSesuaian(teks)">Sudah disesuaikan</x-ui.button>
                    @endif
                </div>
                <div class="mt-3 flex flex-wrap gap-2" x-show="ubah" x-cloak>
                    <x-ui.button size="sm" icon="check2" x-on:click="$wire.simpanSesuaian(teks).then(() => selesai())">Simpan</x-ui.button>
                    <x-ui.button size="sm" variant="ghost" x-on:click="batal()">Batal</x-ui.button>
                </div>
            @endif
        @endif
    </div>
</article>
