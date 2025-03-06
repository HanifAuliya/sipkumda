<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Akun Anda</title>
</head>

<body
    style="font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 0; background-color: #f4f4f9; color: #333;">
    <div
        style="max-width: 600px; margin: 30px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); border: 1px solid #e3e3e3;">

        <div style="text-align: center; margin-bottom: 20px;">
            <img src="{{ url('assets/img/brand/sipkumda.svg') }}" alt="SIPKUMDA Logo"
                style="max-width: 120px; margin: 0 auto 10px auto; display: block;">
            <h1 style="font-size: 24px; margin: 0; color: #ff9900;">SIPKUMDA HST</h1>
            <p style="font-size: 16px; margin-top: 8px;">Sistem Informasi Produk Hukum Daerah - Kabupaten Hulu Sungai
                Tengah</p>
        </div>

        <div style="text-align: left;">
            <p>Halo,</p>
            <p>Akun Anda telah berhasil dibuat di sistem <strong>Sistem Informasi Produk Hukum Daerah
                    (SIPKUMDA)</strong>.</p>
            <p>Berikut adalah password sementara Anda untuk login:</p>
            <h2
                style="font-size: 20px; color: #ff9900; text-align: center; background: #f4f4f9; padding: 10px; border-radius: 4px; border: 1px solid #e3e3e3;">
                {{ $password }}
            </h2>
            <p><strong>Harap segera login dan mengganti password Anda</strong> untuk menjaga keamanan akun Anda.</p>
            <p>Jika Anda memiliki pertanyaan atau membutuhkan bantuan, silakan hubungi administrator sistem kami.</p>
        </div>

        <div style="text-align: center; margin-top: 30px; font-size: 14px; color: #777;">
            <p>Terima kasih,</p>
            <p><strong>Tim SIPKUMDA</strong></p>
            <p><small>Email ini dibuat secara otomatis. Mohon tidak membalas email ini.</small></p>
            <p><small>© {{ date('Y') }} SIPKUMDA Kabupaten Hulu Sungai Tengah. Semua Hak Dilindungi.</small></p>
        </div>

    </div>
</body>

</html>
