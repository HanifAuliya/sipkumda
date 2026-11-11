{{-- Isi satu kolom matriks: baris "| a | b |" tercetak sebagai tabel, sisanya teks biasa. --}}
@foreach (App\Support\TataLetakNaskah::potongan($teks) as $p)
    @isset($p['tabel'])
        @if ($loop->first && filled($penanda ?? null))
            <span class="penanda">{{ $penanda }}</span>
        @endif
        @include('pdf.v2.partials.tabel-isi', ['tabel' => $p, 'kelas' => 'tabel-sel'])
    @else
        <div class="teks">@if ($loop->first && filled($penanda ?? null))<span class="penanda">{{ $penanda }}</span> @endif{{ $p['teks'] }}</div>
    @endisset
@endforeach
