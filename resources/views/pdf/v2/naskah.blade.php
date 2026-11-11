{{--
    Naskah PDF. Kertas, tepi, huruf, kop, dan paraf mengikuti menu Format Dokumen (App\Support\FormatDokumen).
    $f format, $blok susunan TataLetakNaskah, $lambang path gambar atau null, $paraf daftar jabatan paraf hierarki.
--}}
@php
    $m = $f['margin'];
    $lebarIsi = (float) $f['kertas']['lebar'] - (float) $m['kiri'] - (float) $m['kanan'];
    // Halaman pertama: judul dimulai pada jarak atas_pertama dari tepi kertas; ruang di atasnya untuk kop.
    $ruangKop = max(0, (float) $m['atas_pertama'] - (float) $m['atas']);
    $kiriTtd = min((float) $f['penutup']['kiri'], max(0, $lebarIsi - 4));
    $huruf = $f['huruf']['nama'] === 'Arial' ? "'Arial', Helvetica, sans-serif" : "'{$f['huruf']['nama']}', 'Times New Roman', Times, serif";
    $teksKop = $f['kop']['tampil'] ? trim((string) $f['kop']['teks']) : '';
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Naskah</title>
    <style>
        @page { margin: {{ $m['atas'] }}cm {{ $m['kanan'] }}cm {{ $m['bawah'] }}cm {{ $m['kiri'] }}cm; }
        @if ($paraf && $sisihkanParaf)
            /* Ruang kotak paraf hierarki di bawah halaman pertama (digambar setelah halaman dihitung). */
            @page :first { margin-bottom: {{ (float) $m['bawah'] + $tinggiParaf + 0.4 }}cm; }
        @endif
        body { font-family: {!! $huruf !!}; font-size: {{ $f['huruf']['ukuran'] }}pt; line-height: {{ round((float) $f['huruf']['spasi'] * 1.2, 3) }}; color: #000; }
        .kop { text-align: center; font-weight: bold; font-size: {{ $f['kop']['ukuran'] }}pt; }
        .ruang-kop { height: {{ $ruangKop }}cm; overflow: hidden; }
        .kop-biasa { margin-bottom: 14pt; }
        .lambang { margin-bottom: 6pt; }
        .lambang img { width: {{ $f['kop']['lebar_lambang'] }}cm; }
        .tengah { text-align: center; margin: 12pt 0 6pt; }
        .ruang-kop + .tengah { margin-top: 0; }
        .tengah p { margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 0 0 3pt; text-align: justify; }
        td.label { width: {{ $f['konsiderans']['lebar_label'] }}cm; text-align: left; }
        td.titik { width: .5cm; text-align: left; }
        td.nomor { width: .8cm; text-align: left; }
        .isi { white-space: pre-line; }
        .paragraf { text-align: justify; margin: 0 0 3pt; }
        .daftar { margin-bottom: 8pt; }
        .tabel-isi { margin: 4pt 0 8pt; }
        .tabel-isi td { border: 1px solid #000; padding: 2pt 4pt; font-size: 11pt; text-align: left; vertical-align: middle; }
        .penutup { margin-top: 16pt; page-break-inside: avoid; }
        .penutup p { margin: 0; min-height: 14pt; }
        /* Selebar baris jabatan terpanjang, supaya jabatan dan nama rata tengah sesamanya. */
        .ditetapkan { margin-left: {{ $kiriTtd }}cm; width: {{ min(7, $lebarIsi - $kiriTtd) }}cm; }
        .diundangkan { margin-top: 14pt; width: 8.1cm; }
        .penutup .jabatan { text-align: center; }
        .penutup .berita { margin-top: 14pt; }
        .tembusan { margin-top: 14pt; }
        .paraf { width: 8cm; margin-top: 18pt; font-size: {{ min(11, (float) $f['huruf']['ukuran']) }}pt; line-height: 1.2; }
        .paraf td { border: 1px solid #000; height: {{ 0.75 - 0.2 }}cm; padding: .1cm .15cm; text-align: left; vertical-align: middle; }
        .paraf td.jabatan-paraf { width: 6.2cm; }
        /* Tanda tangan dan kotak paraf selalu sehalaman. */
        .akhir { page-break-inside: avoid; }
        .paraf td.kosong { width: 1.8cm; }
        /* Lampiran di halaman baru; kepalanya sejajar dengan blok tanda tangan. */
        .kepala-lampiran { page-break-before: always; margin-left: {{ $kiriTtd }}cm; margin-bottom: 18pt; }
        .kepala-lampiran p { margin: 0; }
        .paraf .judul-paraf { text-align: center; }
    </style>
</head>

<body>
    <div @class(['kop', 'ruang-kop' => $ruangKop > 0, 'kop-biasa' => $ruangKop <= 0])>
        @if ($lambang && $f['kop']['lebar_lambang'] > 0)
            <div class="lambang"><img src="{{ $lambang }}" alt=""></div>
        @endif
        @if ($teksKop !== '')
            {{ $teksKop }}
        @endif
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
                        @unless ($b['rapat'])
                            <td class="titik">{{ $i === 0 ? ':' : '' }}</td>
                        @endunless
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
                    <td class="titik">{{ $b['rapat'] ? '' : ':' }}</td>
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
        @elseif ($b['tipe'] === 'lampiran')
            <div class="kepala-lampiran">
                @foreach ($b['kepala'] as $baris)
                    <p>{{ $baris }}</p>
                @endforeach
            </div>
        @elseif ($b['tipe'] === 'ttd')
            <div class="penutup ditetapkan">
                @foreach ($b['baris'] as $baris)
                    <p class="jabatan">{{ $baris }}</p>
                @endforeach
            </div>
        @elseif ($b['tipe'] === 'penutup')
            <div class="akhir">
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

            @if ($paraf)
                <table class="paraf">
                    <tr><td class="jabatan-paraf judul-paraf">{{ $f['paraf']['judul'] }}</td><td class="kosong"></td></tr>
                    @foreach ($paraf as $jabatan)
                        <tr><td class="jabatan-paraf">{{ $jabatan }}</td><td class="kosong"></td></tr>
                    @endforeach
                </table>
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
