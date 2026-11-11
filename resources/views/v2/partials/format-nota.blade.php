{{-- Formulir isi dan format nota dinas Kepala Bagian Hukum untuk Perangkat Daerah. --}}
@php
    use App\Support\FormatDokumen;

    $f = $nota;
    $tahun = now()->year;
@endphp

<form wire:submit="simpan('nota')" class="space-y-6" wire:key="format-nota">
    <x-ui.alert type="warning" icon="exclamation-triangle">
        Perubahan berlaku untuk nota dinas yang terbit <strong>sesudah disimpan</strong>. Nota dinas yang sudah terbit tidak berubah;
        bila perlu ikut diperbarui, Admin atau Verifikator bisa menekan <strong>Perbaiki &amp; Terbitkan Ulang</strong> di naskahnya. Versi sebelumnya tetap tersimpan.
    </x-ui.alert>

    <x-ui.alert type="info" icon="info-circle">
        Teks di bawah boleh memuat isian otomatis:
        @foreach (FormatDokumen::ISIAN_NOTA as $isian => $arti)
            <code class="rounded bg-white/70 px-1 dark:bg-navy-900/60">{{ $isian }}</code> {{ lcfirst($arti) }}{{ $loop->last ? '.' : ',' }}
        @endforeach
    </x-ui.alert>

    <x-ui.card title="Kertas, Margin, dan Huruf" description="Dalam sentimeter. F4 = 21,5 × 33 cm.">
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach (['kertas.lebar' => 'Lebar kertas (cm)', 'kertas.tinggi' => 'Tinggi kertas (cm)', 'margin.atas' => 'Atas (cm)', 'margin.bawah' => 'Bawah (cm)', 'margin.kiri' => 'Kiri (cm)', 'margin.kanan' => 'Kanan (cm)'] as $kunci => $label)
                <x-form.field :label="$label" for="nota-{{ $kunci }}" :error="'nota.' . $kunci">
                    <x-form.input id="nota-{{ $kunci }}" wire:model="nota.{{ $kunci }}" type="number" step="0.01" inputmode="decimal" />
                </x-form.field>
            @endforeach
            <x-form.field label="Jenis huruf" for="nota-huruf" error="nota.huruf.nama">
                <x-form.select id="nota-huruf" wire:model="nota.huruf.nama">
                    @foreach (FormatDokumen::HURUF as $nama)
                        <option value="{{ $nama }}">{{ $nama }}</option>
                    @endforeach
                </x-form.select>
            </x-form.field>
            <x-form.field label="Ukuran (pt)" for="nota-ukuran" error="nota.huruf.ukuran">
                <x-form.input id="nota-ukuran" wire:model="nota.huruf.ukuran" type="number" step="0.5" inputmode="decimal" />
            </x-form.field>
            <x-form.field label="Spasi baris" for="nota-spasi" error="nota.huruf.spasi" hint="1 = satu spasi.">
                <x-form.input id="nota-spasi" wire:model="nota.huruf.spasi" type="number" step="0.05" inputmode="decimal" />
            </x-form.field>
        </div>
    </x-ui.card>

    <x-ui.card title="Kop">
        <label class="mb-5 flex items-start gap-3 text-sm">
            <input type="checkbox" wire:model.live="nota.kop.tampil" class="mt-0.5 rounded text-primary-600 focus:ring-primary-300">
            <span class="font-semibold text-navy-900 dark:text-white">Cetak kop</span>
        </label>

        <div @class(['grid gap-5 sm:grid-cols-[auto_1fr]', 'opacity-50' => ! $f['kop']['tampil']])>
            <div class="flex flex-col items-center gap-2">
                <div class="grid size-28 place-items-center rounded-xl bg-white p-2 ring-1 ring-slate-200 dark:ring-navy-700">
                    @if ($gambar['nota'] ?? null)
                        <img src="{{ $gambar['nota']->temporaryUrl() }}" alt="Logo baru" class="max-h-full max-w-full object-contain">
                    @else
                        <img src="{{ $urlGambar['nota'] }}" alt="Logo kop" class="max-h-full max-w-full object-contain">
                    @endif
                </div>
                <label class="cursor-pointer text-sm font-semibold text-primary-600 hover:underline dark:text-primary-300">
                    Ganti logo
                    <input type="file" wire:model="gambar.nota" accept="image/png,image/jpeg" class="sr-only">
                </label>
                @if ($unggahan['nota'] && ! ($gambar['nota'] ?? null))
                    <button type="button" class="text-sm text-slate-500 hover:text-rose-600" wire:click="hapusGambar('nota')" wire:confirm="Kembali ke logo bawaan?">Pakai bawaan</button>
                @endif
                @error('gambar.nota')
                    <p class="text-sm text-rose-600">{{ $message }}</p>
                @enderror
                <x-form.field label="Lebar logo (cm)" for="nota-logo" error="nota.kop.lebar_logo" class="w-32">
                    <x-form.input id="nota-logo" wire:model="nota.kop.lebar_logo" type="number" step="0.1" inputmode="decimal" />
                </x-form.field>
            </div>

            <div>
                <p class="mb-1.5 text-sm font-semibold text-slate-700 dark:text-slate-200">Baris kop (rata tengah, urut dari atas)</p>
                <div class="space-y-2">
                    @foreach ($f['kop']['baris'] as $i => $baris)
                        <div class="flex flex-wrap items-center gap-2 sm:flex-nowrap" wire:key="kop-baris-{{ $i }}">
                            <x-form.input wire:model="nota.kop.baris.{{ $i }}.teks" class="min-w-0 flex-1 max-sm:basis-full" aria-label="Teks baris {{ $i + 1 }}" />
                            <x-form.input wire:model="nota.kop.baris.{{ $i }}.ukuran" type="number" step="0.5" class="w-20" aria-label="Ukuran baris {{ $i + 1 }} (pt)" title="Ukuran (pt)" />
                            <label class="flex items-center gap-1.5 text-sm" title="Tebal">
                                <input type="checkbox" wire:model="nota.kop.baris.{{ $i }}.tebal" class="rounded text-primary-600 focus:ring-primary-300"> Tebal
                            </label>
                            <x-ui.button variant="ghost" square icon="trash" wire:click="hapusBaris('nota.kop.baris', {{ $i }})" aria-label="Hapus baris kop" />
                        </div>
                    @endforeach
                </div>
                <x-ui.button variant="ghost" size="sm" icon="plus-lg" class="mt-2" wire:click="tambahBaris('nota.kop.baris')">Tambah baris</x-ui.button>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="Kepala Nota">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.field label="Judul" for="nota-judul" error="nota.judul.teks">
                <x-form.input id="nota-judul" wire:model="nota.judul.teks" />
            </x-form.field>
            <x-form.field label="Ukuran judul (pt)" for="nota-judulukuran" error="nota.judul.ukuran">
                <x-form.input id="nota-judulukuran" wire:model="nota.judul.ukuran" type="number" step="0.5" inputmode="decimal" />
            </x-form.field>
            <x-form.field label="Pola nomor" for="nota-nomor" error="nota.nomor" class="sm:col-span-2"
                hint="{nomor} diisi saat menerbitkan, {tahun} tahun terbit, {kode} NDPB/NDSK. Tanpa {kode}, nomor urut berlaku untuk semua jenis.">
                <x-form.input id="nota-nomor" wire:model.live.debounce.400ms="nota.nomor" />
                <p class="text-sm text-slate-500 dark:text-slate-400">Contoh: <strong>{{ FormatDokumen::nomorNota((string) $f['nomor'], '437', FormatDokumen::KEPBUP, $tahun) }}</strong></p>
            </x-form.field>
            <x-form.field label="Dari" for="nota-dari" error="nota.dari">
                <x-form.input id="nota-dari" wire:model="nota.dari" />
            </x-form.field>
            <x-form.field label="Perihal" for="nota-perihal" error="nota.perihal">
                <x-form.input id="nota-perihal" wire:model="nota.perihal" />
            </x-form.field>
        </div>
        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">"Kepada" diisi saat menerbitkan nota dinas di layar kerja naskah.</p>
    </x-ui.card>

    <x-ui.card title="Isi" description="Paragraf pembuka dan penutup diawali tanda ➢. Butir bernomor 1, 2, 3; ketentuan bernomor a, b, c di bawah butir terakhir.">
        <div class="space-y-5">
            <x-form.field label="Paragraf pembuka" for="nota-pembuka" error="nota.pembuka">
                <x-form.textarea id="nota-pembuka" wire:model="nota.pembuka" rows="3" />
            </x-form.field>

            <div>
                <p class="mb-1.5 text-sm font-semibold text-slate-700 dark:text-slate-200">Butir</p>
                <div class="space-y-2">
                    @foreach ($f['butir'] as $i => $butir)
                        <div class="flex gap-2" wire:key="butir-{{ $i }}">
                            <span class="w-6 pt-2.5 text-right text-sm font-semibold text-slate-500">{{ $i + 1 }}.</span>
                            <x-form.textarea wire:model="nota.butir.{{ $i }}" rows="3" class="flex-1" aria-label="Butir {{ $i + 1 }}" />
                            <x-ui.button variant="ghost" square icon="trash" wire:click="hapusBaris('nota.butir', {{ $i }})" aria-label="Hapus butir" />
                        </div>
                    @endforeach
                </div>
                <x-ui.button variant="ghost" size="sm" icon="plus-lg" class="mt-2" wire:click="tambahBaris('nota.butir')">Tambah butir</x-ui.button>
            </div>

            <div>
                <p class="mb-1.5 text-sm font-semibold text-slate-700 dark:text-slate-200">Ketentuan penandatanganan</p>
                <div class="space-y-2">
                    @foreach ($f['ketentuan'] as $i => $ketentuan)
                        <div class="flex gap-2" wire:key="ketentuan-{{ $i }}">
                            <span class="w-6 pt-2.5 text-right text-sm font-semibold text-slate-500">{{ chr(97 + $i % 26) }}.</span>
                            <x-form.input wire:model="nota.ketentuan.{{ $i }}" class="flex-1" aria-label="Ketentuan {{ chr(97 + $i % 26) }}" />
                            <x-ui.button variant="ghost" square icon="trash" wire:click="hapusBaris('nota.ketentuan', {{ $i }})" aria-label="Hapus ketentuan" />
                        </div>
                    @endforeach
                </div>
                <x-ui.button variant="ghost" size="sm" icon="plus-lg" class="mt-2" wire:click="tambahBaris('nota.ketentuan')">Tambah ketentuan</x-ui.button>
            </div>

            <x-form.field label="Paragraf penutup" for="nota-penutup" error="nota.penutup">
                <x-form.input id="nota-penutup" wire:model="nota.penutup" />
            </x-form.field>
        </div>
    </x-ui.card>

    <x-ui.card title="Tanda Tangan" description="Nama, pangkat, dan NIP diambil dari tanda tangan yang dipilih saat menerbitkan (menu Kelola TTD).">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.field label="Jabatan" for="nota-jabatan" error="nota.ttd.jabatan">
                <x-form.input id="nota-jabatan" wire:model="nota.ttd.jabatan" />
            </x-form.field>
            <x-form.field label="Jarak dari kiri (cm)" for="nota-ttdkiri" error="nota.ttd.kiri">
                <x-form.input id="nota-ttdkiri" wire:model="nota.ttd.kiri" type="number" step="0.1" inputmode="decimal" />
            </x-form.field>
        </div>
        <label class="mt-4 flex items-start gap-3 text-sm">
            <input type="checkbox" wire:model="nota.ttd.gambar" class="mt-0.5 rounded text-primary-600 focus:ring-primary-300">
            <span class="font-semibold text-navy-900 dark:text-white">Cetak gambar tanda tangan</span>
        </label>
    </x-ui.card>

    <div class="sticky bottom-0 z-10 -mx-1 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white/95 p-4 shadow-card ring-1 ring-slate-900/5 backdrop-blur dark:bg-navy-900/95 dark:ring-white/10">
        <x-ui.button variant="ghost" icon="arrow-counterclockwise" wire:click="kembalikan('nota')"
            wire:confirm="Kembalikan semua pengaturan Nota Dinas ke bawaan? Logo unggahan ikut dihapus.">Kembalikan ke Bawaan</x-ui.button>
        <div class="flex flex-wrap gap-2">
            <x-ui.button variant="outline" icon="file-earmark-pdf" wire:click="contoh('nota')" loading="contoh" title="Dari isian di atas, sebelum disimpan">Contoh PDF</x-ui.button>
            <x-ui.button type="submit" icon="check2" loading="simpan">Simpan</x-ui.button>
        </div>
    </div>
</form>
