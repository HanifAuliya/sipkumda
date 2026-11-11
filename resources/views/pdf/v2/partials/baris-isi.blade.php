{{-- Satu baris isi pasal, diktum, atau lampiran pada PDF: tabel, paragraf, atau ayat dan butir bernomor. --}}
@if (isset($baris['tabel']))
    @php
        $tabel = view('pdf.v2.partials.tabel-isi', ['tabel' => $baris])->render();
    @endphp
    @if ($baris['tingkat'])
        {{-- Tabel menjorok sejajar dengan teks butir di atasnya (1 cm per tingkat, sama dengan kolom nomor). --}}
        <table>
            <tr>
                <td style="width: {{ $baris['tingkat'] }}cm"></td>
                <td>{!! $tabel !!}</td>
            </tr>
        </table>
    @else
        {!! $tabel !!}
    @endif
@elseif ($baris['tengah'] ?? false)
    <p class="paragraf" style="text-align: center">{{ $baris['isi'] }}</p>
@elseif ($baris['nomor'] === null)
    <p class="paragraf">{{ $baris['isi'] }}</p>
@else
    <table>
        <tr>
            @if ($baris['tingkat'])
                {{-- Selebar kolom nomor, supaya butir sejajar dengan teks ayat di atasnya. --}}
                <td style="width: {{ $baris['tingkat'] * 1 }}cm"></td>
            @endif
            <td class="nomor" style="width: 1cm">{{ $baris['nomor'] }}</td>
            <td>{{ $baris['isi'] }}</td>
        </tr>
    </table>
@endif
