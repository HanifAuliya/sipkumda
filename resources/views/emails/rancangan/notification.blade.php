<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .email-container {
            width: 100%;
            padding: 20px 0;
            background-color: #f4f4f4;
        }

        .email-content {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .email-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .email-header img {
            height: 80px;
            margin-bottom: 10px;
        }

        .email-header h1 {
            font-size: 24px;
            color: #333;
            margin: 0;
        }

        .email-header p {
            font-size: 16px;
            color: #555;
            margin: 5px 0 20px;
        }

        .email-body {
            font-size: 16px;
            color: #444;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .email-footer {
            text-align: center;
            font-size: 14px;
            color: #666;
            margin-top: 20px;
        }

        .btn {
            display: inline-block;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
        }

        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="email-content">
            <!-- Header -->
            <div class="email-header">
                <img src="{{ asset('assets/img/brand/sipkumda.svg') }}" alt="SIPKUMDA Logo">
                <h1>SIPKUMDA HST</h1>
                <p>Sistem Informasi Produk Hukum Daerah - Kabupaten Hulu Sungai Tengah</p>
            </div>

            <!-- Body -->
            <div class="email-body">
                <h2>{{ $title }}</h2>
                <p>{{ $message }}</p>
                <a href="{{ $url }}" class="btn">Lihat Detail</a>
            </div>

            <!-- Footer -->
            <div class="email-footer">
                &copy; {{ date('Y') }} SIPKUMDA HST. Semua Hak Dilindungi.
            </div>
        </div>
    </div>
</body>

</html>
