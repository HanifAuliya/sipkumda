<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumentasi Produk Hukum</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid black;
            padding: 5px;
            text-align: left;
        }

        th {
            background-color: #B4C6E7;
            text-align: center;
        }

        .wrap-text {
            word-wrap: break-word;
            max-width: 200px;
        }

        .text-danger {
            color: red;
        }
    </style>
</head>

<body>

    <div class="title">Dokumentasi Produk Hukum</div>
    <p class="text-center">Dihasilkan pada: {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nomor Produk Hukum</th>
                <th>Nomor Fasilitasi Rancangan</th>
                <th>Jenis Produk Hukum</th>
                <th class="wrap-text">Tentang</th>
                <th class="wrap-text">Perangkat Daerah</th>
                <th>Tanggal Penetapan</th>
                <th>Nomor/Tahun Berita Daerah</th>
                <th>Tanggal Pengarsipan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dokumentasiList as $index => $dokumentasi)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $dokumentasi->nomor_formatted }}</td>
                    <td class="{{ empty($dokumentasi->rancangan->no_rancangan) ? 'text-danger' : '' }}">
                        {{ $dokumentasi->rancangan->no_rancangan ?? 'Dokumen sebelum ada sistem' }}
                    </td>
                    <td>{{ $dokumentasi->jenis_dokumentasi }}</td>
                    <td class="wrap-text">{{ $dokumentasi->tentang_dokumentasi }}</td>
                    <td class="wrap-text">{{ optional($dokumentasi->perangkatDaerah)->nama_perangkat_daerah }}</td>
                    <td>{{ $dokumentasi->tanggal_penetapan }}</td>
                    <td>{{ $dokumentasi->nomor_tahun_berita }}</td>
                    <td>{{ \Carbon\Carbon::parse($dokumentasi->tanggal_pengarsipan)->translatedFormat('d F Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>
