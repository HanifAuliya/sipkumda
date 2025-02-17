<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Rancangan Produk Hukum</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            padding: 5px 10px;
            font-size: 10px;
            border-radius: 4px;
            color: #fff;
        }

        .badge-primary {
            background-color: #007bff;
        }

        .badge-success {
            background-color: #28a745;
        }

        .badge-danger {
            background-color: #dc3545;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #000;
        }

        .wrap-text {
            word-wrap: break-word;
            max-width: 300px;
        }
    </style>
</head>

<body>

    <h2 class="text-center">Data Rancangan Produk Hukum</h2>
    <p class="text-center">Dihasilkan pada: {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nomor Rancangan</th>
                <th>Jenis</th>
                <th>Tentang</th>
                <th>Tanggal Pengajuan</th>
                <th>User Pengaju</th>
                <th>Perangkat Daerah</th>
                <th>Status Rancangan</th>
                <th>Status Berkas</th>
                <th>Tanggal Disetujui</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rancanganList as $index => $rancangan)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $rancangan->no_rancangan ?? 'N/A' }}</td>
                    <td class="text-center">
                        <span>{{ $rancangan->jenis_rancangan ?? 'N/A' }}</span>
                    </td>
                    <td class="wrap-text">{{ $rancangan->tentang ?? 'N/A' }}</td>
                    <td>{{ $rancangan->tanggal_pengajuan ? \Carbon\Carbon::parse($rancangan->tanggal_pengajuan)->translatedFormat('d F Y') : 'N/A' }}
                    </td>
                    <td>{{ $rancangan->user->nama_user ?? 'N/A' }}</td>
                    <td>{{ $rancangan->user->perangkatDaerah->nama_perangkat_daerah ?? 'N/A' }}</td>
                    <td class="text-center">
                        <span>
                            {{ $rancangan->status_rancangan ?? 'N/A' }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span>
                            {{ $rancangan->status_berkas ?? 'N/A' }}
                        </span>
                    </td>
                    <td>{{ $rancangan->tanggal_rancangan_disetujui ? \Carbon\Carbon::parse($rancangan->tanggal_rancangan_disetujui)->translatedFormat('d F Y') : 'N/A' }}
                    </td>

                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>
