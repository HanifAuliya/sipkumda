<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Matrik {{ $naskah->nomor }}</title>
    <style>
        @page { margin: 1.5cm 1.5cm 1.5cm 1.5cm; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 10.5pt; line-height: 1.25; color: #000; }
        .lampiran { margin-left: 60%; font-size: 10pt; margin-bottom: 10pt; }
        .lampiran table { border-collapse: collapse; }
        .lampiran td { padding: 0 4pt 0 0; vertical-align: top; }
        .draf { text-align: center; font-size: 9pt; font-weight: bold; color: #b00020; margin-bottom: 6pt; }
        .judul { text-align: center; font-weight: bold; margin-bottom: 10pt; }
        .judul p { margin: 0; }
        .matriks { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .matriks th, .matriks td { border: 1px solid #000; padding: 3pt 4pt; vertical-align: top; }
        .matriks th { text-align: center; font-weight: bold; }
        .matriks thead { display: table-header-group; }
        .matriks tr { page-break-inside: auto; }
        .kolom-no { width: 4%; text-align: center; }
        .kolom-materi { width: 12%; }
        .kolom-rumusan { width: 40%; }
        .kolom-sempurna { width: 28%; }
        .kolom-ket { width: 16%; }
        .teks { white-space: pre-line; text-align: left; }
        .tabel-sel { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 2pt 0; font-size: 9pt; }
        .matriks .tabel-sel td { padding: 1pt 2pt; word-wrap: break-word; }
        .penanda { font-weight: bold; }
        /* Dompdf tidak mengenal flex: dua blok tanda tangan disusun dengan tabel tanpa garis. */
        .ttd { width: 100%; margin-top: 18pt; border-collapse: collapse; page-break-inside: avoid; }
        .ttd td { width: 35%; text-align: center; vertical-align: top; padding: 0; }
        .ttd td.sela { width: 30%; }
        .ttd p { margin: 0; }
        .ttd .ruang { height: 60pt; }
        .ttd .gambar-ttd { height: 60pt; }
        .ttd .nama { font-weight: bold; text-decoration: underline; }
    </style>
</head>

<body>
    <div class="lampiran">
        Lampiran Nota Dinas Kepala Bagian Hukum Setda Kabupaten Hulu Sungai Tengah
        <table>
            <tr><td>Nomor</td><td>:</td><td>{{ $nota?->nomor_nota ?? '............................' }}</td></tr>
            <tr><td>Tanggal</td><td>:</td><td>{{ $nota?->tanggal_nota->translatedFormat('j F Y') ?? '............................' }}</td></tr>
        </table>
    </div>

    @unless ($nota)
        <div class="draf">DRAF: belum ditandatangani Kepala Bagian Hukum</div>
    @endunless

    <div class="judul">
        <p>MATRIK RANCANGAN {{ $judul }} KABUPATEN HULU SUNGAI TENGAH</p>
        <p>TENTANG</p>
        <p>{{ mb_strtoupper($naskah->tentang) }}</p>
    </div>

    <table class="matriks">
        <thead>
            <tr>
                <th class="kolom-no">No.</th>
                <th class="kolom-materi">Materi {{ $singkatan }}</th>
                <th class="kolom-rumusan">Rumusan {{ $singkatan }}</th>
                <th class="kolom-sempurna">Rumusan Penyempurnaan</th>
                <th class="kolom-ket">Keterangan</th>
            </tr>
            <tr>
                <th>(1)</th><th>(2)</th><th>(3)</th><th>(4)</th><th>(5)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($isi as $baris)
                <tr>
                    <td class="kolom-no">{{ $baris['no'] }}</td>
                    <td class="kolom-materi">{{ $baris['materi'] }}</td>
                    <td class="kolom-rumusan">
                        @if ($baris['penanda'] && ! $baris['sebaris'])
                            <span class="penanda">{{ $baris['penanda'] }}</span><br>
                        @endif
                        @include('pdf.v2.partials.teks-matriks', ['teks' => $baris['rumusan'], 'penanda' => $baris['sebaris'] ? $baris['penanda'] : null])
                    </td>
                    <td class="kolom-sempurna">@include('pdf.v2.partials.teks-matriks', ['teks' => $baris['penyempurnaan'], 'penanda' => null])</td>
                    <td class="kolom-ket"><div class="teks">{{ $baris['keterangan'] }}</div></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="ttd">
        <tr>
            <td>
                @if ($peneliti)
                    <p>PENELITI</p>
                    @if ($ttdPeneliti)
                        <img src="{{ $ttdPeneliti }}" alt="Tanda Tangan Peneliti" class="gambar-ttd">
                    @else
                        <div class="ruang"></div>
                    @endif
                    <p class="nama">{{ $peneliti->nama_user }}</p>
                    @if (filled($peneliti->NIP))
                        <p>NIP {{ $peneliti->NIP }}</p>
                    @endif
                @endif
            </td>
            <td class="sela"></td>
            <td>
                <p>KEPALA BAGIAN HUKUM</p>
                @if ($nota?->pathTandaTangan())
                    <img src="{{ $nota->pathTandaTangan() }}" alt="Tanda Tangan" class="gambar-ttd">
                @else
                    <div class="ruang"></div>
                @endif
                @if ($nota)
                    <p class="nama">{{ $nota->tandaTangan->nama_ttd }}</p>
                    <p>{{ $nota->tandaTangan->jabatan }}</p>
                    <p>NIP {{ $nota->tandaTangan->nip_ttd }}</p>
                @else
                    <p>............................</p>
                @endif
            </td>
        </tr>
    </table>
</body>

</html>
