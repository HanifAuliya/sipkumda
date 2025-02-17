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
            background-color: #BDBDBD;
            text-align: center;
        }

        .wrap-text {
            word-wrap: break-word;
            max-width: 200px;
        }
    </style>
</head>

<body>

    <div class="title">Dokumentasi Produk Hukum</div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nomor Fasilitasi Rancangan</th>
                <th>Jenis Produk Hukum</th>
                <th class="wrap-text">Tentang</th>
                <th>Nomor Produk Hukum</th>
                <th class="wrap-text">Perangkat Daerah</th>
                <th>Tanggal Pengarsipan</th>
                <th>Nomor Berita Daerah</th>
                <th>Tanggal Berita Daerah</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dokumentasiList as $index => $dokumentasi)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ optional($dokumentasi->rancangan)->no_rancangan }}</td>
                    <td>{{ optional($dokumentasi->rancangan)->jenis_rancangan }}</td>
                    <td class="wrap-text">{{ optional($dokumentasi->rancangan)->tentang }}</td>
                    <td>{{ $dokumentasi->nomor_formatted }}</td>
                    <td class="wrap-text">{{ optional($dokumentasi->perangkatDaerah)->nama_perangkat_daerah }}</td>
                    <td>{{ \Carbon\Carbon::parse($dokumentasi->tanggal)->translatedFormat('d F Y') }}</td>
                    <td>{{ $dokumentasi->nomor_berita_daerah }}</td>
                    <td>{{ $dokumentasi->tanggal_berita_daerah }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>
