<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Verifikasi Akun WaliKelas Pro</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f0fdf4;
            color: #0f172a;
            margin: 0;
            padding: 24px;
        }
        .email-container {
            max-width: 520px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 20px;
            border: 1px solid #a7f3d0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        .email-header {
            background: linear-gradient(135deg, #059669, #047857);
            color: #ffffff;
            padding: 32px 24px;
            text-align: center;
        }
        .email-body {
            padding: 32px 28px;
        }
        .otp-box {
            background-color: #ecfdf5;
            border: 2px dashed #059669;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            margin: 24px 0;
        }
        .otp-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 38px;
            font-weight: 900;
            letter-spacing: 10px;
            color: #064e3b;
            margin: 0;
        }
        .email-footer {
            background-color: #f8fafc;
            border-top: 1px solid #f1f5f9;
            padding: 20px 24px;
            font-size: 11px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <h1 style="margin: 0; font-size: 22px; font-weight: 800; letter-spacing: -0.5px;">WaliKelas Pro</h1>
            <p style="margin: 6px 0 0; font-size: 13px; opacity: 0.9;">Platform Manajemen Administrasi &amp; Rapor Kelas Digital</p>
        </div>

        <!-- Body -->
        <div class="email-body">
            <p style="font-size: 15px; margin: 0 0 16px; line-height: 1.5;">
                Halo <strong>{{ $name }}</strong>,
            </p>
            <p style="font-size: 13.5px; color: #334155; margin: 0 0 16px; line-height: 1.6;">
                Terima kasih telah mendaftar di <strong>WaliKelas Pro</strong>. Untuk memastikan akun ini benar-benar milik Anda, silakan masukkan 6 digit kode verifikasi berikut pada layar pendaftaran:
            </p>

            <!-- OTP Box -->
            <div class="otp-box">
                <span style="font-size: 11px; font-weight: 700; color: #047857; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">KODE VERIFIKASI RESMI</span>
                <p class="otp-code">{{ $otp }}</p>
                <span style="font-size: 11px; color: #64748b; display: block; margin-top: 8px;">Berlaku selama {{ $expiryMinutes }} menit</span>
            </div>

            <p style="font-size: 12px; color: #64748b; margin: 20px 0 0; line-height: 1.5;">
                ⚠️ <strong>Penting:</strong> Jangan bagikan kode ini kepada siapa pun termasuk tim dukungan teknis. Jika Anda tidak merasa mendaftar di WaliKelas Pro, Anda dapat mengabaikan email ini dengan aman.
            </p>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p style="margin: 0;">&copy; {{ date('Y') }} WaliKelas Pro &middot; Solusi Cerdas Guru Indonesia</p>
            <p style="margin: 4px 0 0;">Email ini dikirim secara otomatis ke alamat email yang Anda daftarkan.</p>
        </div>
    </div>
</body>
</html>
