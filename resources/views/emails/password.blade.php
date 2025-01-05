<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Akun Anda</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 30px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid #e3e3e3;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header img {
            max-width: 120px;
            margin-bottom: 10px;
        }

        .header h1 {
            font-size: 24px;
            margin: 0;
            color: ##ff9900;
        }

        .content {
            text-align: left;
        }

        .content p {
            font-size: 16px;
            margin: 10px 0;
        }

        .content h2 {
            font-size: 20px;
            color: #ff9900;
            text-align: center;
            background: #f4f4f9;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #e3e3e3;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: #777;
        }

        .footer p {
            margin: 5px 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('assets/img/brand/sipkumda.svg') }}" alt="SIPKUMDA Logo">
            <h1>SIPKUMDA HST</h1>
            <p style="font-size: 16px; margin-top: 8px;"> Sistem Informasi Produk Hukum Daerah - Kabupaten Hulu Sungai
                Tengah</p>
        </div>

        <div class="content">
            <p>Halo,</p>
            <p>Akun Anda telah berhasil dibuat di sistem <strong>Sistem Informasi Produk Hukum Daerah
                    (SIPKUMDA)</strong>.</p>
            <p>Berikut adalah password sementara Anda untuk login:</p>
            <h2>{{ $password }}</h2>
            <p><strong>Harap segera login dan mengganti password Anda</strong> untuk menjaga keamanan akun Anda.</p>
            <p>Jika Anda memiliki pertanyaan atau membutuhkan bantuan, silakan hubungi administrator sistem kami.</p>
        </div>

        <div class="footer">
            <p>Terima kasih,</p>
            <p><strong>Tim SIPKUMDA</strong></p>
            <p><small>Email ini dibuat secara otomatis. Mohon tidak membalas email ini.</small></p>
            <p><small>© {{ date('Y') }} SIPKUMDA Kabupaten Hulu Sungai Tengah. Semua Hak Dilindungi.</small></p>
        </div>

    </div>
</body>

</html>
