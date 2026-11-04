{{-- Isi satu kolom matriks: baris "| a | b |" tercetak sebagai tabel, sisanya teks biasa. --}}
@foreach (App\Support\TataLetakNaskah::potongan($teks) as $p)
    @isset($p['tabel'])
        @if ($loop->first && filled($penanda ?? null))
            <span class="penanda">{{ $penanda }}</span>
        @endif
        <table class="tabel-sel">
            @foreach ($p['tabel'] as $kolom)
                <tr>
                    @foreach ($kolom as $isi)
                        <td>{{ $isi }}</td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    @else
        <div class="teks">@if ($loop->first && filled($penanda ?? null))<span class="penanda">{{ $penanda }}</span> @endif{{ $p['teks'] }}</div>
    @endisset
@endforeach
