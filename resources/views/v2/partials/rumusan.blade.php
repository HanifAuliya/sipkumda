{{--
    Satu sisi rumusan di kartu baris: $potongan dari BandingRumusan::susun() ('awal' atau 'sempurna').
    Perubahannya sudah ditandai <del> atau <ins>, juga di dalam sel tabel.
--}}
@foreach ($potongan as $p)
    @isset($p['tabel'])
        @if ($p['tabel'])
            <div class="my-2 overflow-x-auto font-sans">
                @include('v2.partials.tabel-isi', ['tabel' => $p, 'garis' => 'border-slate-300 dark:border-navy-600'])
            </div>
        @endif
    @else
        <span class="block whitespace-pre-line">{{ $p['html'] }}</span>
    @endisset
@endforeach
