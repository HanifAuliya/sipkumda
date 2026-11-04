{{--
    Hasil pemeriksaan kelengkapan berkas oleh AI pada item daftar (Admin).
    <x-rancangan.ai-status :rancangan="$item" :can-check="true" />
    - canCheck : tampilkan tombol "Periksa Cepat" (memanggil jalankanPrediksi(id)) bila belum diperiksa
--}}
@props(['rancangan', 'canCheck' => false])

@php
    $hasil = $rancangan->hasil_prediksi_kelengkapan;
    $catatan = collect(explode('; ', (string) $rancangan->catatan_berkas))->map(fn ($c) => trim($c))->filter();
@endphp

<div x-data="{ open: true }" class="space-y-2">
    <div class="flex flex-wrap items-center gap-2">
        @if ($hasil === 'Lengkap')
            <x-ui.badge color="success" icon="robot">Berkas lengkap</x-ui.badge>
            <span class="text-xs text-amber-700 dark:text-amber-400">
                Tetap perlu verifikasi admin (tanda tangan, dasar hukum, dan isi).
            </span>
        @elseif ($hasil === 'Tidak Lengkap')
            <x-ui.badge color="warning" icon="robot">Berkas tidak lengkap</x-ui.badge>
            @if ($catatan->isNotEmpty())
                <button type="button" x-on:click="open = !open" class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 hover:underline dark:text-amber-400">
                    <i class="bi" x-bind:class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                    <span x-text="open ? 'Tutup catatan' : 'Lihat catatan'"></span>
                </button>
            @endif
        @else
            <x-ui.badge color="slate" icon="robot">Belum diperiksa</x-ui.badge>
            @if ($canCheck)
                <x-ui.button variant="outline-primary" size="xs" icon="cpu"
                    wire:click="jalankanPrediksi({{ $rancangan->id_rancangan }})"
                    loading="jalankanPrediksi({{ $rancangan->id_rancangan }})">Periksa cepat</x-ui.button>
            @endif
        @endif
    </div>

    @if ($hasil === 'Tidak Lengkap' && $catatan->isNotEmpty())
        <div x-show="open" x-collapse class="rounded-lg bg-amber-50 px-3.5 py-2.5 text-xs ring-1 ring-amber-200 dark:bg-amber-500/10 dark:ring-amber-500/30">
            <p class="mb-1 font-semibold text-amber-800 dark:text-amber-300">
                <i class="bi bi-exclamation-triangle-fill"></i> Catatan AI — indikator belum terpenuhi:
            </p>
            <ul class="list-disc space-y-0.5 pl-5 text-amber-900 dark:text-amber-200">
                @foreach ($catatan as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
            <p class="mt-1.5 text-amber-700 italic dark:text-amber-400">* Hasil deteksi otomatis AI. Verifikasi manual tetap diperlukan.</p>
        </div>
    @endif
</div>
