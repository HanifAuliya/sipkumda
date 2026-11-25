{{--
    Isi lampiran di layar kerja: HTML dari editor (sudah dibersihkan saat disimpan, jadi aman ditampilkan apa adanya),
    atau berkas PDF dengan penampil di dalam kartu. $baris (NaskahBaris), $nilai (isi kolom 3 atau 4).
--}}
@if ($baris->berkas())
    @php $pdf = $baris->berkasLampiran($nilai); @endphp
    @if ($pdf)
        @php $url = route('naskah.berkas', [$baris->naskah, $pdf, 'lihat' => 1]); @endphp
        <div class="font-sans">
            <div class="mb-2 flex flex-wrap items-center justify-between gap-2 text-sm">
                <span class="min-w-0 truncate text-slate-600 dark:text-slate-300">
                    <i class="bi bi-file-earmark-pdf text-rose-500" aria-hidden="true"></i>
                    <strong class="font-semibold">{{ $pdf->nama_asli }}</strong>
                    · {{ $pdf->created_at->translatedFormat('d M Y H:i') }}
                </span>
                <span class="flex gap-1.5">
                    <x-ui.button size="rapat" variant="outline" icon="arrows-fullscreen" :data-url="$url" :data-judul="$pdf->nama_asli"
                        x-on:click="$dispatch('lihat-pdf', { url: $el.dataset.url, judul: $el.dataset.judul })">Perbesar</x-ui.button>
                    <x-ui.button size="rapat" variant="outline" icon="download" :href="route('naskah.berkas', [$baris->naskah, $pdf])">Unduh</x-ui.button>
                </span>
            </div>
            <iframe src="{{ $url }}#view=FitH" title="Lampiran {{ $pdf->nama_asli }}" loading="lazy"
                class="h-[32rem] w-full rounded-lg bg-white ring-1 ring-slate-200 dark:ring-navy-700"></iframe>
        </div>
    @else
        <span class="font-sans text-sm text-slate-400">PDF lampiran belum diunggah.</span>
    @endif
@else
    <div class="lampiran-html">{!! $nilai !!}</div>
@endif
