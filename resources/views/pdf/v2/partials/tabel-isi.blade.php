{{--
    Tabel di dalam isi naskah atau matriks pada PDF. $tabel: ['tabel' => baris sel, 'rata' => rata kolom, 'judul' => bool].
    Baris judul diulang di tiap halaman; baris yang hanya berisi sel pertama (judul kelompok) dibentangkan selebar tabel.
    $kelas: kelas CSS tabelnya (bawaan tabel-isi).
--}}
@php
    $susunan = App\Support\TataLetakNaskah::susunTabel($tabel);
    $kepala = array_filter($susunan, fn (array $b) => $b['judul']);
    $badan = array_filter($susunan, fn (array $b) => ! $b['judul']);
@endphp
<table class="{{ $kelas ?? 'tabel-isi' }}">
    @if ($kepala)
        <thead>
            @foreach ($kepala as $b)
                <tr>
                    @foreach ($b['sel'] as $s)
                        <td colspan="{{ $s['bentang'] }}" style="text-align: {{ $s['rata'] }}">{{ $s['isi'] }}</td>
                    @endforeach
                </tr>
            @endforeach
        </thead>
    @endif
    <tbody>
        @foreach ($badan as $b)
            <tr>
                @foreach ($b['sel'] as $s)
                    <td colspan="{{ $s['bentang'] }}" style="text-align: {{ $s['rata'] }};{{ $s['tebal'] ? ' font-weight: bold;' : '' }}">{{ $s['isi'] }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
