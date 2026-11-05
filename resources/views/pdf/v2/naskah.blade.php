<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ $naskah->nomor }}</title>
    <style>
        @page { margin: 2cm 2cm 2.5cm 3cm; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.35; color: #000; }
        .kop { text-align: center; font-weight: bold; font-size: 14pt; margin-bottom: 14pt; }
        .lambang { margin-bottom: 6pt; }
        .lambang img { width: 70px; }
        .tengah { text-align: center; margin: 12pt 0 6pt; }
        .tengah p { margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 0 0 3pt; text-align: justify; }
        td.label { width: 2.7cm; text-align: left; }
        td.titik { width: .5cm; text-align: left; }
        td.nomor { width: .8cm; text-align: left; }
        .isi { white-space: pre-line; }
        .paragraf { text-align: justify; margin: 0 0 3pt; }
        .daftar { margin-bottom: 8pt; }
        .tabel-isi { margin: 4pt 0 8pt; }
        .tabel-isi td { border: 1px solid #000; padding: 2pt 4pt; font-size: 11pt; text-align: left; }
        .penutup { margin-top: 16pt; page-break-inside: avoid; }
        .penutup p { margin: 0; min-height: 14pt; }
        /* Selebar baris jabatan terpanjang, supaya jabatan dan nama rata tengah sesamanya. */
        .ditetapkan { margin-left: 8cm; width: 6.6cm; }
        .diundangkan { margin-top: 14pt; width: 7.6cm; }
        .penutup .jabatan { text-align: center; }
        .penutup .berita { margin-top: 14pt; }
        .tembusan { margin-top: 14pt; }
    </style>
</head>

<body>
    <div class="kop">
        @if ($lambang)
            <div class="lambang"><img src="{{ $lambang }}" alt=""></div>
        @endif
        {{ App\Support\TataLetakNaskah::KOP }}
    </div>

    @foreach ($blok as $b)
        @if ($b['tipe'] === 'tengah')
            <div class="tengah">
                @foreach ($b['baris'] as $baris)
                    <p>{{ $baris }}</p>
                @endforeach
            </div>
        @elseif ($b['tipe'] === 'daftar')
            <table class="daftar">
                @foreach ($b['butir'] as $i => $butir)
                    <tr>
                        <td class="label">{{ $i === 0 ? $b['label'] : '' }}</td>
                        <td class="titik">{{ $i === 0 ? ':' : '' }}</td>
                        @if ($butir['nomor'])
                            <td class="nomor">{{ $butir['nomor'] }}</td>
                        @endif
                        <td class="isi">{{ $butir['isi'] }}</td>
                    </tr>
                @endforeach
            </table>
        @elseif ($b['tipe'] === 'diktum')
            {{-- Satu baris tabel per baris isi: DomPDF tidak bisa memenggal baris tabel yang melebihi halaman. --}}
            <table class="daftar">
                <tr>
                    <td class="label">{{ $b['pembuka'] }}</td>
                    <td class="titik">:</td>
                    <td></td>
                </tr>
                @foreach ($b['butir'] as $butir)
                    @foreach ($butir['baris'] as $j => $baris)
                        <tr>
                            <td class="label">{{ $j === 0 ? $butir['nomor'] : '' }}</td>
                            <td class="titik">{{ $j === 0 ? ':' : '' }}</td>
                            <td>@include('pdf.v2.partials.baris-isi', ['baris' => $baris])</td>
                        </tr>
                    @endforeach
                @endforeach
            </table>
        @elseif ($b['tipe'] === 'penutup')
            <div class="penutup">
                <div class="ditetapkan">
                    @foreach ($b['ditetapkan'] as $baris)
                        <p @class(['jabatan' => ! App\Support\TataLetakNaskah::barisTempatTanggal($baris)])>{{ $baris }}</p>
                    @endforeach
                </div>
                @if ($b['diundangkan'])
                    <div class="diundangkan">
                        @foreach ($b['diundangkan'] as $baris)
                            <p @class(['jabatan' => ! App\Support\TataLetakNaskah::barisTempatTanggal($baris)])>{{ $baris }}</p>
                        @endforeach
                    </div>
                @endif
                @if ($b['berita'])
                    <p class="berita">{{ $b['berita'] }}</p>
                @endif
                @if ($b['tembusan'])
                    <div class="tembusan">
                        @foreach ($b['tembusan'] as $baris)
                            <p>{{ $baris }}</p>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            @foreach ($b['baris'] as $baris)
                @include('pdf.v2.partials.baris-isi', ['baris' => $baris])
            @endforeach
        @endif
    @endforeach
</body>

</html>
