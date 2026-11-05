{{-- Satu baris isi pasal atau diktum pada PDF: tabel, paragraf, atau ayat dan butir bernomor. --}}
@if (isset($baris['tabel']))
    <table class="tabel-isi">
        @foreach ($baris['tabel'] as $sel)
            <tr>
                @foreach ($sel as $isi)
                    <td>{{ $isi }}</td>
                @endforeach
            </tr>
        @endforeach
    </table>
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
