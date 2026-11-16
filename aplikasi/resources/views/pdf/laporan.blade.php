{{-- Laporan & Pemantauan siap cetak (App\Livewire\V2\Laporan::unduhPdf). Kop memakai kop nota dinas di Format Dokumen. --}}
@php use App\Enums\TahapNaskah; @endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Fasilitasi Produk Hukum Daerah</title>
    <style>
        @page { margin: 1.5cm 1.8cm 1.6cm; }
        body { font-family: Arial, sans-serif; font-size: 9.5pt; color: #111; }
        p { margin: 0; }
        .kop { width: 100%; margin-bottom: 10pt; border-collapse: collapse; }
        .kop td { vertical-align: middle; padding-bottom: 6pt; border-bottom: 3px double #000; }
        .kop .logo { width: 2.3cm; }
        .kop .logo img { width: 2cm; }
        .kop .teks { text-align: center; line-height: 1.15; }
        h1 { font-size: 12.5pt; text-align: center; margin: 4pt 0 2pt; letter-spacing: .3pt; }
        .sub { text-align: center; font-size: 9.5pt; margin-bottom: 12pt; }
        h2 { font-size: 10.5pt; margin: 14pt 0 5pt; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #555; padding: 3.5pt 5pt; }
        table.data th { background: #e8ecf8; font-weight: bold; text-align: center; }
        .angka { text-align: right; }
        .tengah { text-align: center; }
        .redup { color: #888; }
        .ringkas td { border: 1px solid #555; padding: 4pt 6pt; }
        .ringkas .label { width: 80%; }
        .utuh { white-space: nowrap; }
        .ringkas .sub-baris td.label { padding-left: 16pt; color: #333; }
        .kaki { margin-top: 18pt; font-size: 8pt; color: #555; }
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

    <h1>LAPORAN FASILITASI PRODUK HUKUM DAERAH</h1>
    <p class="sub">
        Periode: {{ $periode }} &nbsp;·&nbsp; {{ $jenis ?? 'Semua jenis' }} &nbsp;·&nbsp; {{ $namaPd ?? 'Semua Perangkat Daerah' }}
    </p>

    <h2>I. Ringkasan</h2>
    <table class="data ringkas">
        <tr><td class="label">Naskah diajukan</td><td class="angka">{{ $ringkasan['diajukan'] }}</td></tr>
        <tr class="sub-baris"><td class="label">Peraturan Bupati</td><td class="angka">{{ $ringkasan['peraturan'] }}</td></tr>
        <tr class="sub-baris"><td class="label">Keputusan Bupati</td><td class="angka">{{ $ringkasan['keputusan'] }}</td></tr>
        <tr><td class="label">Sedang berjalan</td><td class="angka">{{ $ringkasan['berjalan'] }}</td></tr>
        <tr><td class="label">Selesai (diarsipkan)</td><td class="angka">{{ $ringkasan['selesai'] }}</td></tr>
        <tr><td class="label">Rata-rata lama proses, dari diajukan sampai diarsipkan</td><td class="angka">{{ $ringkasan['rata_proses'] !== null ? $ringkasan['rata_proses'] . ' hari' : '-' }}</td></tr>
        <tr><td class="label">Produk hukum ditetapkan</td><td class="angka">{{ $ditetapkan['total'] }}</td></tr>
        <tr class="sub-baris"><td class="label">Peraturan Bupati</td><td class="angka">{{ $ditetapkan['peraturan'] }}</td></tr>
        <tr class="sub-baris"><td class="label">Keputusan Bupati</td><td class="angka">{{ $ditetapkan['keputusan'] }}</td></tr>
        <tr class="sub-baris"><td class="label">Lewat SIFKUMDA</td><td class="angka">{{ $ditetapkan['dari_sistem'] }}</td></tr>
    </table>

    <h2>II. Sebaran per Perangkat Daerah</h2>
    <table class="data">
        <thead>
            <tr>
                <th style="text-align: left">Perangkat Daerah</th>
                @foreach (TahapNaskah::LANGKAH as $langkah)
                    <th>{{ $langkah }}</th>
                @endforeach
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($matriks as $b)
                <tr>
                    <td>{{ $b['nama'] }}</td>
                    @foreach ($b['jumlah'] as $j)
                        <td class="tengah {{ $j ? '' : 'redup' }}">{{ $j ?: '-' }}</td>
                    @endforeach
                    <td class="tengah"><strong>{{ $b['total'] }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="{{ count(TahapNaskah::LANGKAH) + 2 }}" class="tengah redup">Belum ada naskah yang diajukan pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>III. Naskah Tertahan Lebih dari {{ $batas }} Hari</h2>
    <table class="data">
        <thead>
            <tr><th>Nomor</th><th style="text-align: left">Tentang</th><th>Perangkat Daerah</th><th>Tahap</th><th>Lama</th></tr>
        </thead>
        <tbody>
            @forelse ($tertahan as $b)
                <tr>
                    <td class="tengah utuh">{{ $b['naskah']->nomor_tampil }}</td>
                    <td>{{ $b['naskah']->tentang }}</td>
                    <td>{{ $b['naskah']->pemrakarsa->perangkatDaerah->nama_perangkat_daerah ?? '-' }}</td>
                    <td class="tengah utuh">{{ $b['naskah']->tahap->label() }}</td>
                    <td class="angka utuh">{{ $b['lama_tahap'] }} hari</td>
                </tr>
            @empty
                <tr><td colspan="5" class="tengah redup">Tidak ada naskah yang tertahan.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>IV. Rata-rata Lama per Tahap</h2>
    <table class="data">
        <thead>
            <tr><th style="text-align: left">Tahap</th><th>Dikerjakan oleh</th><th>Naskah sekarang</th><th>Rata-rata lama</th></tr>
        </thead>
        <tbody>
            @foreach ($perTahap as $t)
                <tr>
                    <td>{{ $t['tahap']->label() }}</td>
                    <td class="tengah">{{ $t['tahap']->peran() }}</td>
                    <td class="tengah">{{ $t['sekarang'] }}</td>
                    <td class="angka">@if ($t['rata_hari'] === null) - @elseif ($t['rata_hari'] < 1) &lt; 1 hari @else {{ str_replace('.', ',', $t['rata_hari']) }} hari @endif</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="kaki">Dicetak dari SIFKUMDA pada {{ now()->translatedFormat('j F Y, H.i') }} oleh {{ rtrim($pencetak, '.') }}.</p>
</body>

</html>
