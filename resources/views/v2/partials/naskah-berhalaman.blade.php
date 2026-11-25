{{--
    Naskah di tab Naskah, tampil seperti membuka Word: lembar seukuran kertas, nomor halaman, dan perbesaran
    (resources/js/alpine/halaman-naskah.js). Isinya dari isi-naskah; bila berubah, seluruhnya diganti (wire:key)
    karena pemenggal halaman menyisipkan ruang ke dalamnya.

    $blokNaskah, $naskah, $banding, $mode dari LayarKerja; $keterangan kalimat di bilahnya.
--}}
@php
    $px = fn (float $cm) => round($cm * 96 / 2.54, 2);
    ['f' => $fk, 'paraf' => $parafk] = App\Services\CetakNaskah::tampilanLayar($naskah->jenis, $blokNaskah);
    $ukuranKertas = [
        'lebar' => $px((float) $fk['kertas']['lebar']),
        'tinggi' => $px((float) $fk['kertas']['tinggi']),
        'atas' => $px((float) $fk['margin']['atas']),
        'bawah' => $px((float) $fk['margin']['bawah']),
        'kiri' => $px((float) $fk['margin']['kiri']),
        // Sama dengan ruang yang disisihkan PDF di bawah halaman pertama.
        'paraf' => $parafk ? $px((count($parafk) + 1) * App\Services\CetakNaskah::BARIS_PARAF + 0.4) : 0,
    ];
    $isiNaskah = view('v2.partials.isi-naskah', [
        'blok' => $blokNaskah,
        'jenis' => $naskah->jenis,
        'berhalaman' => true,
        'tandaiUbahan' => $banding,
        'tombolBaris' => $banding,
        'bisaUbah' => (bool) $mode,
    ])->render();
@endphp

<div wire:ignore wire:key="naskah-{{ md5($isiNaskah) }}" x-data="halamanNaskah(@js($ukuranKertas))" class="rounded-2xl bg-slate-200/80 dark:bg-navy-950 dark:ring-1 dark:ring-white/5">
    <div class="flex flex-wrap items-center justify-between gap-x-4 sm:sticky sm:top-0 sm:z-20 gap-y-1 rounded-t-2xl border-b border-slate-200 bg-white px-4 py-2 text-sm text-slate-600 dark:border-navy-800 dark:bg-navy-900 dark:text-slate-300">
        <p class="v2-diff min-w-0 flex-1 basis-72">
            {{ $keterangan }}
            @if ($banding)
                <del>Merah</del> dihapus, <ins>hijau</ins> ditambah. Tekan tombol di tepi kertas untuk membuka barisnya.
            @endif
        </p>
        <div class="flex items-center gap-1">
            <span class="mr-2 whitespace-nowrap tabular-nums" x-cloak x-show="siap" x-text="`Halaman ${halaman} dari ${lembar.length}`"></span>
            <x-ui.button size="rapat" square variant="ghost" icon="dash-lg" title="Perkecil" aria-label="Perkecil" x-on:click="perbesar(-0.1)" />
            <button type="button" class="min-h-9 min-w-14 rounded-lg px-2 text-center font-semibold tabular-nums transition hover:bg-slate-100 dark:hover:bg-navy-800"
                title="Kembali selebar layar" x-on:click="selebarLayar()" x-text="`${Math.round(skala * 100)}%`">100%</button>
            <x-ui.button size="rapat" square variant="ghost" icon="plus-lg" title="Perbesar" aria-label="Perbesar" x-on:click="perbesar(0.1)" />
        </div>
    </div>

    <div x-ref="meja" @class(['overflow-x-auto py-6', 'px-3 sm:px-6' => ! $banding, 'pr-12 pl-3 sm:px-16' => $banding])>
        <div class="relative mx-auto" style="width: {{ $ukuranKertas['lebar'] }}px"
            x-bind:style="siap ? { width: ({{ $ukuranKertas['lebar'] }} * skala) + 'px', height: (tinggiTotal * skala) + 'px' } : {}">
            {!! $isiNaskah !!}
        </div>
    </div>
</div>
