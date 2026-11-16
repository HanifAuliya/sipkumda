{{--
    Nota dinas Kepala Bagian Hukum untuk Perangkat Daerah. Isi dan format diatur di menu Format Dokumen,
    bawaannya mengikuti Nota Dinas 100.3/437/ND/KUM/2026. Teks sudah diisi (isian {jenis} dst.) oleh CetakNaskah.
--}}
@php
    $m = $f['margin'];
    $huruf = $f['huruf']['nama'] === 'Arial' ? "'Arial', Helvetica, sans-serif" : "'{$f['huruf']['nama']}', 'Times New Roman', Times, serif";
    $ttd = $nota->tandaTangan;
    // Tanda panah pembuka dan penutup; DejaVu Sans dipakai karena huruf bawaan PDF tidak punya glifnya.
    $panah = '<span class="panah">&#10146;</span>';
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Nota Dinas {{ $nota->nomor_nota }}</title>
    <style>
        @page { margin: {{ $m['atas'] }}cm {{ $m['kanan'] }}cm {{ $m['bawah'] }}cm {{ $m['kiri'] }}cm; }
        body { font-family: {!! $huruf !!}; font-size: {{ $f['huruf']['ukuran'] }}pt; line-height: {{ round((float) $f['huruf']['spasi'] * 1.15, 3) }}; color: #000; }
        p { margin: 0; }
        table { border-collapse: collapse; }
        td { vertical-align: top; padding: 0; }
        /* Garis ganda di bawah kop; DomPDF menggambarnya pada sel, bukan pada tabel. */
        .kop { width: 100%; margin-bottom: 8pt; }
        .kop td { vertical-align: middle; padding-bottom: 6pt; border-bottom: 3px double #000; }
        .kop .logo { width: {{ (float) $f['kop']['lebar_logo'] + 0.3 }}cm; }
        .kop .logo img { width: {{ $f['kop']['lebar_logo'] }}cm; }
        .kop .teks { text-align: center; line-height: 1.15; }
        h1 { text-align: center; font-size: {{ $f['judul']['ukuran'] }}pt; font-weight: bold; text-decoration: underline; margin: 10pt 0 10pt; }
        .info { width: 100%; margin-bottom: 12pt; }
        .info td { padding: 1pt 0; text-align: justify; }
        .info .label { width: 3.2cm; }
        .info .titik { width: .35cm; }
        .isi { width: 100%; text-align: justify; }
        .isi td { padding-bottom: 6pt; text-align: justify; }
        .isi .tanda { width: .75cm; }
        .isi .nomor { width: .7cm; }
        .ketentuan { width: 100%; margin-top: 3pt; }
        .ketentuan td { padding: 0 0 2pt; text-align: justify; }
        .ketentuan .huruf { width: .7cm; }
        .panah { font-family: 'DejaVu Sans', sans-serif; font-size: .85em; }
        .ttd { margin-left: {{ $f['ttd']['kiri'] }}cm; margin-top: 16pt; text-align: center; page-break-inside: avoid; }
        .ttd .ruang { height: 2.2cm; }
        .ttd .gambar { height: 2.2cm; }
    </style>
</head>

<body>
    @if ($kop || $logo)
        <table class="kop">
            <tr>
                @if ($logo)
                    <td class="logo"><img src="{{ $logo }}" alt=""></td>
                @endif
                <td class="teks">
                    @foreach ($kop as $baris)
                        <p style="font-size: {{ $baris['ukuran'] }}pt; {{ ! empty($baris['tebal']) ? 'font-weight: bold;' : '' }}">{{ $baris['teks'] }}</p>
                    @endforeach
                </td>
            </tr>
        </table>
    @endif

    <h1>{{ $f['judul']['teks'] }}</h1>

    <table class="info">
        <tr><td class="label">Kepada Yth.</td><td class="titik">:</td><td>{{ $nota->kepada }}</td></tr>
        <tr><td class="label">Dari</td><td class="titik">:</td><td>{{ $f['dari'] }}</td></tr>
        <tr><td class="label">Tanggal</td><td class="titik">:</td><td>{{ $nota->tanggal_nota ? \Illuminate\Support\Carbon::parse($nota->tanggal_nota)->translatedFormat('j F Y') : '...' }}</td></tr>
        <tr><td class="label">Nomor</td><td class="titik">:</td><td>{{ $nota->nomor_nota }}</td></tr>
        <tr><td class="label">Perihal</td><td class="titik">:</td><td>{{ $perihal }}</td></tr>
    </table>

    <table class="isi">
        @if ($pembuka !== '')
            <tr>
                <td class="tanda">{!! $panah !!}</td>
                <td colspan="2">{{ $pembuka }}</td>
            </tr>
        @endif
        @foreach ($butir as $i => $teks)
            <tr>
                <td class="tanda"></td>
                <td class="nomor">{{ $i + 1 }}.</td>
                <td>
                    {{ $teks }}
                    @if ($loop->last && $ketentuan)
                        <table class="ketentuan">
                            @foreach ($ketentuan as $j => $baris)
                                <tr>
                                    <td class="huruf">{{ chr(97 + $j % 26) }}.</td>
                                    <td>{{ $baris }}</td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                </td>
            </tr>
        @endforeach
        @if ($penutup !== '')
            <tr>
                <td class="tanda">{!! $panah !!}</td>
                <td colspan="2">{{ $penutup }}</td>
            </tr>
        @endif
    </table>

    <div class="ttd">
        <p>{{ $f['ttd']['jabatan'] }}</p>
        @if ($gambarTtd)
            <img src="{{ $gambarTtd }}" alt="Tanda Tangan" class="gambar">
        @else
            <div class="ruang"></div>
        @endif
        <p>{{ $ttd?->nama_ttd }}</p>
        @if (filled($ttd?->jabatan))
            <p>{{ $ttd->jabatan }}</p>
        @endif
        @if (filled($ttd?->nip_ttd))
            <p>NIP {{ $ttd->nip_ttd }}</p>
        @endif
    </div>
</body>

</html>
