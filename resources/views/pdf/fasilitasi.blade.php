<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Fasilitasi Produk Hukum</title>
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

    <div class="title">Daftar Fasilitasi Produk Hukum</div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nomor Rancangan</th>
                <th>Jenis Produk Hukum</th>
                <th class="wrap-text">Tentang</th>
                <th>Tanggal Fasilitasi</th>
                <th>Status Paraf Koordinasi</th>
                <th>Status Asisten</th>
                <th>Status Sekda</th>
                <th>Status Bupati</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($fasilitasiList as $index => $fasilitasi)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ optional($fasilitasi->rancangan)->no_rancangan }}</td>
                    <td>{{ optional($fasilitasi->rancangan)->jenis_rancangan }}</td>
                    <td class="wrap-text">{{ optional($fasilitasi->rancangan)->tentang }}</td>
                    <td>{{ \Carbon\Carbon::parse($fasilitasi->tanggal_fasilitasi)->translatedFormat('d F Y') }}</td>
                    {{-- Jika statusnya "Selesai", tampilkan tanggalnya. Jika "Belum", tampilkan "-" --}}
                    <td>{{ $fasilitasi->status_paraf_koordinasi === 'Selesai' ? \Carbon\Carbon::parse($fasilitasi->tanggal_paraf_koordinasi)->translatedFormat('d F Y') : '-' }}
                    </td>
                    <td>{{ $fasilitasi->status_asisten === 'Selesai' ? \Carbon\Carbon::parse($fasilitasi->tanggal_asisten)->translatedFormat('d F Y') : '-' }}
                    </td>
                    <td>{{ $fasilitasi->status_sekda === 'Selesai' ? \Carbon\Carbon::parse($fasilitasi->tanggal_sekda)->translatedFormat('d F Y') : '-' }}
                    </td>
                    <td>{{ $fasilitasi->status_bupati === 'Selesai' ? \Carbon\Carbon::parse($fasilitasi->tanggal_bupati)->translatedFormat('d F Y') : '-' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>
