{{-- Formulir format naskah (Keputusan atau Peraturan Bupati). $b = kepbup | perbup. --}}
@php
    use App\Support\FormatDokumen;

    $f = $this->{$b};
    $jenis = $b === 'kepbup' ? 'Keputusan Bupati' : 'Peraturan Bupati';
    $angka = fn (string $kunci) => "{$b}.{$kunci}";
@endphp

<form wire:submit="simpan('{{ $b }}')" class="space-y-6" wire:key="format-{{ $b }}">
    @if ($b === 'kepbup')
        <x-ui.alert type="info" icon="info-circle">
            Bawaannya mengikuti <strong>Pedoman Penyusunan Produk Hukum (SK)</strong> Bagian Hukum: kertas F4, Bookman Old Style 12,
            judul mulai 8 cm dari tepi atas halaman pertama, halaman berikutnya margin atas 3 cm, kiri, kanan, dan bawah 2,5 cm,
            serta kotak paraf hierarki di halaman awal dan halaman tanda tangan.
        </x-ui.alert>
    @endif

    <x-ui.card title="Kertas dan Margin" description="Dalam sentimeter. F4 = 21,5 × 33 cm.">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.field label="Lebar kertas (cm)" for="{{ $b }}-lebar" :error="$angka('kertas.lebar')">
                <x-form.input id="{{ $b }}-lebar" wire:model="{{ $b }}.kertas.lebar" type="number" step="0.01" inputmode="decimal" />
            </x-form.field>
            <x-form.field label="Tinggi kertas (cm)" for="{{ $b }}-tinggi" :error="$angka('kertas.tinggi')">
                <x-form.input id="{{ $b }}-tinggi" wire:model="{{ $b }}.kertas.tinggi" type="number" step="0.01" inputmode="decimal" />
            </x-form.field>
            <x-form.field label="Atas halaman pertama (cm)" for="{{ $b }}-atas1" :error="$angka('margin.atas_pertama')"
                hint="Jarak tepi atas kertas ke baris pertama judul. Kop berada di ruang ini.">
                <x-form.input id="{{ $b }}-atas1" wire:model="{{ $b }}.margin.atas_pertama" type="number" step="0.01" inputmode="decimal" />
            </x-form.field>
            <x-form.field label="Atas halaman berikutnya (cm)" for="{{ $b }}-atas" :error="$angka('margin.atas')">
                <x-form.input id="{{ $b }}-atas" wire:model="{{ $b }}.margin.atas" type="number" step="0.01" inputmode="decimal" />
            </x-form.field>
            <x-form.field label="Bawah (cm)" for="{{ $b }}-bawah" :error="$angka('margin.bawah')">
                <x-form.input id="{{ $b }}-bawah" wire:model="{{ $b }}.margin.bawah" type="number" step="0.01" inputmode="decimal" />
            </x-form.field>
            <x-form.field label="Kiri (cm)" for="{{ $b }}-kiri" :error="$angka('margin.kiri')">
                <x-form.input id="{{ $b }}-kiri" wire:model="{{ $b }}.margin.kiri" type="number" step="0.01" inputmode="decimal" />
            </x-form.field>
            <x-form.field label="Kanan (cm)" for="{{ $b }}-kanan" :error="$angka('margin.kanan')">
                <x-form.input id="{{ $b }}-kanan" wire:model="{{ $b }}.margin.kanan" type="number" step="0.01" inputmode="decimal" />
            </x-form.field>
        </div>
    </x-ui.card>

    <x-ui.card title="Huruf">
        <div class="grid gap-4 sm:grid-cols-3">
            <x-form.field label="Jenis huruf" for="{{ $b }}-huruf" :error="$angka('huruf.nama')">
                <x-form.select id="{{ $b }}-huruf" wire:model="{{ $b }}.huruf.nama">
                    @foreach (FormatDokumen::HURUF as $nama)
                        <option value="{{ $nama }}">{{ $nama }}</option>
                    @endforeach
                </x-form.select>
            </x-form.field>
            <x-form.field label="Ukuran (pt)" for="{{ $b }}-ukuran" :error="$angka('huruf.ukuran')">
                <x-form.input id="{{ $b }}-ukuran" wire:model="{{ $b }}.huruf.ukuran" type="number" step="0.5" inputmode="decimal" />
            </x-form.field>
            <x-form.field label="Spasi baris" for="{{ $b }}-spasi" :error="$angka('huruf.spasi')" hint="1 = satu spasi.">
                <x-form.input id="{{ $b }}-spasi" wire:model="{{ $b }}.huruf.spasi" type="number" step="0.05" inputmode="decimal" />
            </x-form.field>
        </div>
    </x-ui.card>

    <x-ui.card title="Kop" description="Lambang dan tulisan di atas judul halaman pertama.">
        <label class="mb-5 flex items-start gap-3 text-sm">
            <input type="checkbox" wire:model.live="{{ $b }}.kop.tampil" class="mt-0.5 rounded text-primary-600 focus:ring-primary-300">
            <span>
                <span class="font-semibold text-navy-900 dark:text-white">Cetak kop</span>
                <span class="block text-slate-500 dark:text-slate-400">Matikan bila naskah dicetak di kertas kop emas yang lambangnya sudah tercetak; ruang kopnya tetap dikosongkan.</span>
            </span>
        </label>

        <div @class(['grid gap-5 sm:grid-cols-[auto_1fr]', 'opacity-50' => ! $f['kop']['tampil']])>
            <div class="flex flex-col items-center gap-2">
                <div class="grid size-28 place-items-center rounded-xl bg-white p-2 ring-1 ring-slate-200 dark:ring-navy-700">
                    @if ($gambar[$b] ?? null)
                        <img src="{{ $gambar[$b]->temporaryUrl() }}" alt="Lambang baru" class="max-h-full max-w-full object-contain">
                    @else
                        <img src="{{ $urlGambar[$b] }}" alt="Lambang kop" class="max-h-full max-w-full object-contain">
                    @endif
                </div>
                <label class="cursor-pointer text-sm font-semibold text-primary-600 hover:underline dark:text-primary-300">
                    Ganti lambang
                    <input type="file" wire:model="gambar.{{ $b }}" accept="image/png,image/jpeg" class="sr-only">
                </label>
                @if ($unggahan[$b] && ! ($gambar[$b] ?? null))
                    <button type="button" class="text-sm text-slate-500 hover:text-rose-600" wire:click="hapusGambar('{{ $b }}')" wire:confirm="Kembali ke lambang Garuda bawaan?">Pakai bawaan</button>
                @endif
                @error("gambar.{$b}")
                    <p class="text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.field label="Teks kop" for="{{ $b }}-kop" :error="$angka('kop.teks')" class="sm:col-span-2">
                    <x-form.input id="{{ $b }}-kop" wire:model="{{ $b }}.kop.teks" />
                </x-form.field>
                <x-form.field label="Ukuran teks kop (pt)" for="{{ $b }}-kopukuran" :error="$angka('kop.ukuran')">
                    <x-form.input id="{{ $b }}-kopukuran" wire:model="{{ $b }}.kop.ukuran" type="number" step="0.5" inputmode="decimal" />
                </x-form.field>
                <x-form.field label="Lebar lambang (cm)" for="{{ $b }}-lambang" :error="$angka('kop.lebar_lambang')" hint="0 = tanpa lambang.">
                    <x-form.input id="{{ $b }}-lambang" wire:model="{{ $b }}.kop.lebar_lambang" type="number" step="0.1" inputmode="decimal" />
                </x-form.field>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="Konsiderans dan Penutup">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.field label="Titik dua Menimbang dan Mengingat" :error="$angka('konsiderans.titik_dua')" class="sm:col-span-2">
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach (['rapat' => ['Menimbang:', 'Rapat, seperti pedoman SK. Juga "MEMUTUSKAN:" dan "Menetapkan:".'], 'kolom' => ['Menimbang :', 'Titik dua sejajar dalam kolom sendiri.']] as $nilai => [$contoh, $ket])
                        <label @class([
                            'flex cursor-pointer gap-3 rounded-xl p-3 ring-1 ring-inset',
                            'bg-primary-50/60 ring-primary-300 dark:bg-primary-500/10 dark:ring-primary-500/40' => $f['konsiderans']['titik_dua'] === $nilai,
                            'ring-slate-200 hover:bg-slate-50 dark:ring-navy-700 dark:hover:bg-navy-800' => $f['konsiderans']['titik_dua'] !== $nilai,
                        ])>
                            <input type="radio" wire:model.live="{{ $b }}.konsiderans.titik_dua" value="{{ $nilai }}" class="mt-0.5 text-primary-600 focus:ring-primary-300">
                            <span class="text-sm">
                                <span class="block font-['Bookman_Old_Style',serif] font-semibold text-navy-900 dark:text-white">{{ $contoh }}</span>
                                <span class="text-slate-500 dark:text-slate-400">{{ $ket }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </x-form.field>
            <x-form.field label="Lebar kolom label (cm)" for="{{ $b }}-label" :error="$angka('konsiderans.lebar_label')" hint="Lebar kolom Menimbang, Mengingat, dan KESATU.">
                <x-form.input id="{{ $b }}-label" wire:model="{{ $b }}.konsiderans.lebar_label" type="number" step="0.05" inputmode="decimal" />
            </x-form.field>
            <x-form.field label="Jarak tanda tangan dari kiri (cm)" for="{{ $b }}-ttd" :error="$angka('penutup.kiri')" hint="Blok Ditetapkan di ... sampai nama Bupati.">
                <x-form.input id="{{ $b }}-ttd" wire:model="{{ $b }}.penutup.kiri" type="number" step="0.1" inputmode="decimal" />
            </x-form.field>
            <x-form.field label="Tempat penetapan" for="{{ $b }}-tempat" :error="$angka('penutup.tempat')" hint="Untuk naskah yang dibuat sesudah disimpan.">
                <x-form.input id="{{ $b }}-tempat" wire:model="{{ $b }}.penutup.tempat" />
            </x-form.field>
            @if ($b === 'kepbup')
                <x-form.field label="Pola nomor keputusan" for="{{ $b }}-nomor-arsip" :error="$angka('nomor_arsip')" class="sm:col-span-2"
                    hint="Dipakai saat naskah diarsipkan. {nomor} diisi nomor urut, {tahun} mengikuti tanggal penetapan. Bagian lain terisi di formulir arsip dan masih bisa diganti per naskah.">
                    <x-form.input id="{{ $b }}-nomor-arsip" wire:model="{{ $b }}.nomor_arsip" maxlength="80" placeholder="140/{nomor}/412.1.6/TAHUN {tahun}" />
                </x-form.field>
            @endif
        </div>
    </x-ui.card>

    <x-ui.card title="Paraf Hierarki" description="Kotak paraf di halaman awal dan di halaman tanda tangan.">
        <label class="mb-5 flex items-start gap-3 text-sm">
            <input type="checkbox" wire:model.live="{{ $b }}.paraf.tampil" class="mt-0.5 rounded text-primary-600 focus:ring-primary-300">
            <span class="font-semibold text-navy-900 dark:text-white">Cetak kotak paraf</span>
        </label>

        <div @class(['space-y-4', 'opacity-50' => ! $f['paraf']['tampil']])>
            <x-form.field label="Judul kotak" for="{{ $b }}-parafjudul" :error="$angka('paraf.judul')">
                <x-form.input id="{{ $b }}-parafjudul" wire:model="{{ $b }}.paraf.judul" />
            </x-form.field>

            <div>
                <p class="mb-1.5 text-sm font-semibold text-slate-700 dark:text-slate-200">Jabatan yang memaraf (urut dari atas)</p>
                <div class="space-y-2">
                    @foreach ($f['paraf']['jabatan'] as $i => $jabatan)
                        <div class="flex gap-2" wire:key="{{ $b }}-jabatan-{{ $i }}">
                            <x-form.input wire:model="{{ $b }}.paraf.jabatan.{{ $i }}" class="flex-1" aria-label="Jabatan {{ $i + 1 }}" />
                            <x-ui.button variant="ghost" square icon="trash" wire:click="hapusBaris('{{ $b }}.paraf.jabatan', {{ $i }})" aria-label="Hapus jabatan" />
                        </div>
                    @endforeach
                </div>
                <x-ui.button variant="ghost" size="sm" icon="plus-lg" class="mt-2" wire:click="tambahBaris('{{ $b }}.paraf.jabatan')">Tambah jabatan</x-ui.button>
            </div>
        </div>
    </x-ui.card>

    <div class="sticky bottom-0 z-10 -mx-1 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white/95 p-4 shadow-card ring-1 ring-slate-900/5 backdrop-blur dark:bg-navy-900/95 dark:ring-white/10">
        <x-ui.button variant="ghost" icon="arrow-counterclockwise" wire:click="kembalikan('{{ $b }}')"
            wire:confirm="Kembalikan semua pengaturan {{ $jenis }} ke bawaan? Lambang unggahan ikut dihapus.">Kembalikan ke Bawaan</x-ui.button>
        <div class="flex flex-wrap gap-2">
            <x-ui.button variant="outline" icon="file-earmark-pdf" wire:click="contoh('{{ $b }}', 'pdf')" loading="contoh" title="Dari isian di atas, sebelum disimpan">Contoh PDF</x-ui.button>
            <x-ui.button variant="outline" icon="file-earmark-word" wire:click="contoh('{{ $b }}', 'word')" loading="contoh" title="Dari isian di atas, sebelum disimpan">Contoh Word</x-ui.button>
            <x-ui.button type="submit" icon="check2" loading="simpan">Simpan</x-ui.button>
        </div>
    </div>
</form>
