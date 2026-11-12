{{--
    Field nomor produk hukum + nomor berita daerah, dipakai form tambah dan edit dokumentasi.
    - tahunTetap   : tahun produk hukum (teks); null = tahun diisi lewat properti "tahun"
    - beritaWajib  : nomor/tahun berita daerah wajib diisi
    Keputusan Bupati memakai nomor berformat (trait IsiNomorKeputusan): [140] / [nomor] / [412.1.6] / TAHUN [tahun].
--}}
@php
    $beritaWajib ??= true;
    $potongan = $this->potonganNomor();
    $angka = fn (int $max) => "\$el.value = \$el.value.replace(/\\D/g, '').slice(0, {$max})";
    $segmen = 'flex items-center bg-slate-50 px-3 text-sm text-slate-500 dark:bg-navy-800';
    $isian = 'w-16 min-w-0 flex-1 border-0 bg-white px-3 py-2.5 text-center font-mono text-sm focus:ring-0 dark:bg-navy-900';
    $grup = 'flex items-stretch overflow-hidden rounded-lg ring-1 ring-slate-200 focus-within:ring-2 focus-within:ring-primary-400 dark:ring-navy-700';
    $kotak = 'rounded-lg border-0 py-2 text-center text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-primary-300 dark:bg-navy-800 dark:ring-navy-700';
@endphp

<div class="grid gap-5 sm:grid-cols-2">
    @if ($potongan)
        <x-form.field label="Nomor Keputusan Bupati" for="nomor_produk_hukum" :error="['nomor', 'tahun']" required class="sm:col-span-2"
            hint="Isi nomor urutnya. Bagian lain terisi dari menu Format Dokumen dan bisa diganti bila memakai kode lain.">
            <div class="flex flex-wrap items-center gap-1.5">
                @foreach ($potongan as $i => $p)
                    @if ($p['jenis'] === 'nomor')
                        <input id="nomor_produk_hukum" wire:model="nomor" inputmode="numeric" maxlength="3" placeholder="000" autocomplete="off"
                            x-on:input="{{ $angka(3) }}" class="{{ $kotak }} w-20 font-semibold ring-2 ring-primary-300 dark:ring-primary-500/50">
                    @elseif ($p['jenis'] === 'pemisah')
                        <span class="text-lg text-slate-400" aria-hidden="true">/</span>
                    @elseif ($p['jenis'] === 'tahun')
                        @php [$depan, $belakang] = array_pad(explode('{tahun}', $p['isi'], 2), 2, ''); @endphp
                        @if (trim($depan) !== '')
                            <span class="text-sm font-semibold text-slate-500 dark:text-slate-400">{{ trim($depan) }}</span>
                        @endif
                        @if ($tahunTetap)
                            <span class="rounded-lg bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-500 dark:bg-navy-800 dark:text-slate-400">{{ $tahunTetap }}</span>
                        @else
                            <input wire:model="tahun" inputmode="numeric" maxlength="4" placeholder="YYYY" aria-label="Tahun produk hukum"
                                x-on:input="{{ $angka(4) }}" class="{{ $kotak }} w-24">
                        @endif
                        @if (trim($belakang) !== '')
                            <span class="text-sm font-semibold text-slate-500 dark:text-slate-400">{{ trim($belakang) }}</span>
                        @endif
                    @else
                        <input wire:model="bagianNomor.{{ $i }}" maxlength="30" autocomplete="off" aria-label="Bagian nomor ke-{{ $loop->iteration }}"
                            size="{{ max(4, mb_strlen($bagianNomor[$i] ?? $p['isi']) + 1) }}" class="{{ $kotak }} text-slate-600 dark:text-slate-300">
                    @endif
                @endforeach
            </div>
        </x-form.field>
    @else
        <x-form.field label="Nomor Produk Hukum" for="nomor_produk_hukum" :error="['nomor', 'tahun']" required>
            <div class="{{ $grup }}">
                <span class="{{ $segmen }}">Nomor</span>
                <input id="nomor_produk_hukum" wire:model="nomor" inputmode="numeric" maxlength="3" placeholder="###"
                    x-on:input="{{ $angka(3) }}" class="{{ $isian }}">
                @if ($tahunTetap)
                    <span class="{{ $segmen }}">Tahun {{ $tahunTetap }}</span>
                @else
                    <span class="{{ $segmen }}">Tahun</span>
                    <input wire:model="tahun" inputmode="numeric" maxlength="4" placeholder="YYYY" aria-label="Tahun produk hukum"
                        x-on:input="{{ $angka(4) }}" class="{{ $isian }}">
                @endif
            </div>
        </x-form.field>
    @endif

    <x-form.field label="Nomor Berita Daerah" for="nomor_berita" :error="['nomor_berita', 'tahun_berita']"
        :required="$beritaWajib" :hint="$beritaWajib ? null : 'Opsional.'">
        <div class="{{ $grup }}">
            <input id="nomor_berita" wire:model="nomor_berita" inputmode="numeric" maxlength="3" placeholder="No"
                x-on:input="{{ $angka(3) }}" class="{{ $isian }}">
            <span class="{{ $segmen }}">/</span>
            <input wire:model="tahun_berita" inputmode="numeric" maxlength="4" placeholder="YYYY" aria-label="Tahun berita daerah"
                x-on:input="{{ $angka(4) }}" class="{{ $isian }}">
        </div>
    </x-form.field>
</div>
