<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi Akun SIFKUMDA</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 0; background-color: #f4f4f9; color: #333;">
    <div style="max-width: 600px; margin: 30px auto; background: #fff; padding: 24px; border-radius: 8px; border: 1px solid #e3e3e3;">
        <div style="text-align: center; margin-bottom: 20px;">
            <h1 style="font-size: 24px; margin: 0; color: #172b4d;">SIFKUMDA</h1>
            <p style="font-size: 14px; margin-top: 6px; color: #666;">Bagian Hukum Sekretariat Daerah Kabupaten Hulu Sungai Tengah</p>
        </div>

        <p>Halo {{ $user->nama_user }},</p>
        <p>Akun SIFKUMDA untuk NIP <strong>{{ $user->NIP }}</strong> sudah dibuat. Buat password Anda melalui tombol di bawah ini untuk mulai memakainya.</p>

        <p style="text-align: center; margin: 28px 0;">
            <a href="{{ $tautan }}" style="display: inline-block; background: #172b4d; color: #fff; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold;">Buat Password</a>
        </p>

        <p>Tautan ini berlaku {{ $berlakuHari }} hari dan hanya bisa dipakai sekali. Jika sudah tidak berlaku, minta admin mengirim ulang.</p>
        <p style="font-size: 13px; color: #666;">Jika tombol tidak berfungsi, salin alamat ini ke browser:<br><span style="word-break: break-all;">{{ $tautan }}</span></p>
        <p style="font-size: 13px; color: #666;">Abaikan email ini jika Anda merasa tidak memerlukan akun SIFKUMDA. Email ini dibuat otomatis, mohon tidak membalasnya.</p>
    </div>
</body>

</html>
