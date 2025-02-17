<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Dinas</title>
    <style>
        /* Pengaturan Halaman PDF */
        @page {
            size: A4;
            margin: 1cm 2cm 2cm 2cm;
            /* Kurangi margin atas menjadi 1cm */
        }


        /* Mengatur jarak antar baris agar lebih rapat */
        body {
            font-family: 'Arial', sans-serif;
            font-size: 11pt;
            text-align: justify;
            margin: 0 auto;
            padding-top: 100px;
            width: 100%;
            max-width: 18cm;
            line-height: 1.1;
            /* Lebih rapat dari default */
        }

        /* Menggunakan tabel agar lebih rapi */
        .table-info {
            width: 100%;
            border-collapse: collapse;
        }

        .table-info td {
            padding: 2px 0;
            /* Mengurangi jarak antar baris */
            vertical-align: top;
            /* Pastikan teks rata atas */
        }

        /* Mengatur jarak antar teks */
        .table-info td:first-child {
            width: 100px;
        }

        /* Header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            background-color: white;
            text-align: center;
            z-index: 1000;
        }

        .header img {
            position: absolute;
            left: 0;
            top: 0;
            width: 3cm;
            height: auto;
        }

        .header p {
            margin: 3px 0;
            font-size: 10pt;
        }

        .header .title {
            margin-left: 80px;
            font-size: 18pt;
            font-weight: bold;
        }

        .header .subtitle {
            margin-left: 80px;
            font-size: 10pt;
            font-weight: bold;
        }

        .header .gov-name {
            margin-left: 80px;
            font-size: 14pt;
            font-weight: bold;
        }

        .header .no-telp {
            margin-left: 80px;
        }

        .double-line {
            border-top: 2px solid black;
            border-bottom: 1px solid black;
            margin: 10px 0;
            padding-bottom: 5px;
        }

        /* Judul */
        h1 {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            text-decoration: underline;
            margin-top: 20px;
        }

        /* Konten */
        .content {
            font-size: 11pt;
            margin-top: 15px;
        }

        .content p {
            margin: 5px 0;
        }

        .align-labels {
            display: grid;
            grid-template-columns: 100px auto;
            gap: 5px;
        }

        .single-line {
            border-top: 1px solid black;
            margin: 10px 0;
        }

        ol,
        li {
            margin-left: 20px;
            padding-left: 5px;
        }

        /* Tanda Tangan */
        .signature {
            float: right;
            text-align: center;
            width: 40%;
            margin-top: 80px;
            position: relative;
            /* Pastikan konteks posisi */
        }

        .signature img.ttd {
            width: 5cm;
            /* Lebar tetap */
            height: 4cm;
            /* Tinggi tetap */
            object-fit: contain;
            /* Pastikan gambar tidak terdistorsi */
            position: absolute;
            top: -10px;
            /* Pindahkan ke atas agar tidak menimpa teks */
            left: 50%;
            transform: translateX(-50%);
            /* Pusatkan gambar */
            z-index: 10;
            /* Pastikan berada di atas teks */
        }

        .signature .name {
            font-weight: bold;
            margin-top: 60px;
            /* Tambahkan jarak setelah tanda tangan */
        }
    </style>
</head>

<body>
    <div class="header">
        <img src="{{ public_path('assets/img/brand/barabai.png') }}" alt="Logo Barabai">
        <p class="gov-name">PEMERINTAH KABUPATEN HULU SUNGAI TENGAH</p>
        <p class="title">SEKRETARIAT DAERAH</p>
        <p class="subtitle">Jalan Perwira No. 1 Telpon (0517) 41029, 43120</p>
        <p class="no-telp">No Telp : fax : (0517) 41052 - Kode Pos : 71311 - Email: setda@hulusungaitengah.go.id</p>
        <div class="double-line"></div>
    </div>

    <h1>NOTA DINAS</h1>

    <div class="content">
        <table class="table-info">
            <tr>
                <td>Kepada Yth.</td>
                <td>: </td>
                <td> &nbsp;Kepala
                    {{ $notaDinas->fasilitasi->rancangan->user->perangkatDaerah->nama_perangkat_daerah }}</td>
            </tr>
            <tr>
                <td>Dari</td>
                <td>: </td>
                <td> &nbsp;Kepala Bagian Hukum</td>
            </tr>
            <tr>
                <td>Tanggal</td>
                <td>: </td>
                <td> &nbsp;{{ \Carbon\Carbon::parse($notaDinas->tanggal_nota)->translatedFormat('j F Y') ?? '...' }}
                </td>
            </tr>
            <tr>
                <td>Nomor</td>
                <td>: </td>
                <td> &nbsp;{{ $notaDinas->nomor_nota ?? '...' }}</td>
            </tr>
            <tr>
                <td>Perihal</td>
                <td>: &nbsp;</td>
                <td style="padding-left: 4px"> Koreksi atas Keputusan Bupati Hulu Sungai Tengah tentang
                    {{ $notaDinas->fasilitasi->rancangan->tentang }} </td>
            </tr>
        </table>
        <div class="single-line"></div>

        <p style="margin-left: 115px; text-indent: -10;">
            - Sehubungan dengan Nota Dinas Kepala
            {{ $notaDinas->fasilitasi->rancangan->user->perangkatDaerah->nama_perangkat_daerah }} Nomor:
            {{ $notaDinas->fasilitasi->rancangan->nomor_nota }}
            tanggal {{ $notaDinas->fasilitasi->rancangan->tanggal_nota }}
            perihal sebagaimana tersebut di
            atas, dengan ini disampaikan beberapa hal:
        </p>

        <ol style="margin-left: 110px;margin-top : -3px ">
            <li>
                Konsep Keputusan Bupati Hulu Sungai Tengah sebagaimana dimaksud telah
                diteliti dan dikoreksi oleh Bagian Hukum sesuai dengan bentuk dan
                format penyusunan Peraturan Perundang-Undangan yang berlaku dengan
                beberapa perbaikan sebagaimana tertulis dalam konsep keputusan tersebut;
            </li>
            <li>
                Berkenaan dengan substansi/materi dari Keputusan Bupati Hulu Sungai
                Tengah ini menjadi tanggung jawab Perangkat Daerah pengusul;
            </li>
            <li>
                Untuk proses Penandatanganan Keputusan Bupati Hulu Sungai Tengah
                dimaksud haruslah mengikuti mekanisme dan prosedur yang berlaku:
                <ol type="a" style="margin-rightt: 50px;">
                    <li>Konsep Keputusan Bupati dibuat dalam rangkap 3 (tiga);</li>
                    <li>
                        Sebelum dilakukan pemarafan secara hirarki agar dimintakan paraf
                        koordinasi Bagian Hukum;
                    </li>
                    <li>Jenis huruf menggunakan "Bookman Old Style".</li>
                </ol>
            </li>
        </ol>

        <p style="margin-left: 125px; text-indent: -20px">
            - Demikian disampaikan, untuk bahan selanjutnya.
        </p>
    </div>

    <div class="signature">
        <p>Kepala Bagian Hukum,</p>
        <img src="file://{{ storage_path('app/private/tanda_tangan/ttd/' . basename($notaDinas->tandaTangan->file_ttd)) }}"
            alt="Tanda Tangan" class="ttd">
        <p class="name" style="margin-top: 55px">TAUFIK RAHMAN, SH.</p>
        <p style="margin-top: -10px">Pembina Tk. I</p>
        <p style="margin-top: -10px">NIP 19731002 199903 1 005</p>
    </div>
</body>

</html>
