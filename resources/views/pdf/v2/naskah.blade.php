<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ $naskah->nomor }}</title>
    <style>
        @page { margin: 2cm 2cm 2cm 3cm; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.35; color: #000; }
        .kop { text-align: center; font-weight: bold; font-size: 14pt; margin-bottom: 14pt; }
        .kop img { width: 70px; display: block; margin: 0 auto 6pt; }
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
        .penutup { margin-top: 16pt; page-break-inside: avoid; }
        .penutup p { margin: 0; min-height: 14pt; }
        .ditetapkan { margin-left: 8cm; }
        .diundangkan { margin-top: 14pt; }
        .penutup .berita { margin-top: 14pt; }
    </style>
</head>

<body>
    <div class="kop">
        @if ($lambang)
            <img src="{{ $lambang }}" alt="">
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
            <table class="daftar">
                @foreach ($b['butir'] as $butir)
                    <tr>
                        <td class="label">{{ $butir['nomor'] }}</td>
                        <td class="titik">:</td>
                        <td class="isi">{{ $butir['isi'] }}</td>
                    </tr>
                @endforeach
            </table>
        @elseif ($b['tipe'] === 'penutup')
            <div class="penutup">
                <div class="ditetapkan">
                    @foreach ($b['ditetapkan'] as $baris)
                        <p>{{ $baris }}</p>
                    @endforeach
                </div>
                @if ($b['diundangkan'])
                    <div class="diundangkan">
                        @foreach ($b['diundangkan'] as $baris)
                            <p>{{ $baris }}</p>
                        @endforeach
                    </div>
                @endif
                @if ($b['berita'])
                    <p class="berita">{{ $b['berita'] }}</p>
                @endif
            </div>
        @else
            @foreach ($b['baris'] as $baris)
                @if ($baris['nomor'] === null)
                    <p class="paragraf">{{ $baris['isi'] }}</p>
                @else
                    <table>
                        <tr>
                            @if ($baris['tingkat'])
                                <td style="width: {{ $baris['tingkat'] * 0.8 }}cm"></td>
                            @endif
                            <td class="nomor" style="width: 1cm">{{ $baris['nomor'] }}</td>
                            <td>{{ $baris['isi'] }}</td>
                        </tr>
                    </table>
                @endif
            @endforeach
        @endif
    @endforeach
</body>

</html>
